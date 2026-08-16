<?php

declare(strict_types=1);

namespace App\Jobs\HubSpot;

use App\Data\HubSpot\Data\HubSpotDealData;
use App\Data\HubSpot\Data\NormalizedLineItemData;
use App\Data\HubSpot\Data\WarehouseCandidateData;
use App\Data\HubSpot\Data\WarehouseRecommendationItemData;
use App\Data\HubSpot\Requests\WarehouseRecommendationData;
use App\Data\HubSpot\Responses\WarehouseRecommendationDataResponse;
use App\Data\HubSpot\Responses\WarehouseRecommendationResultData;
use App\Data\HubSpot\Responses\WarehouseSelectionData;
use App\Enums\HubSpot\DealLineItemReaderStage;
use App\Enums\HubSpot\HubSpotWorkflowExecutionState;
use App\Enums\HubSpot\WarehouseRecommendationFailureCode;
use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Exceptions\HubSpot\HubSpotCrmException;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Exceptions\HubSpot\LineItemDataInvalidException;
use App\Models\HubSpot\WarehouseRecommendationTask;
use App\Services\HubSpot\CRM\DealLineItemReader;
use App\Services\HubSpot\CRM\HubSpotCrmClient;
use App\Services\HubSpot\CRM\HubSpotDealNoteService;
use App\Services\HubSpot\CRM\LineItemNormalizer;
use App\Services\HubSpot\Warehouse\WarehouseInventoryService;
use App\Services\HubSpot\Warehouse\WarehouseRecommendationService;
use App\Services\HubSpot\Workflow\HubSpotWorkflowCallbackClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessWarehouseRecommendation implements ShouldQueue
{
    use Queueable;

    /** @var list<array<string, mixed>> */
    private array $debugTrace = [];

    public function __construct(
        public string $taskId,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ?WarehouseInventoryService $warehouseInventoryService = null,
        ?WarehouseRecommendationService $warehouseRecommendationService = null,
    ): void {
        $task = WarehouseRecommendationTask::find($this->taskId);

        if (! $task instanceof WarehouseRecommendationTask) {
            Log::channel('hubspot')->notice('Warehouse recommendation task was skipped because it was not found.', [
                'event'   => 'warehouse_task.skipped',
                'task_id' => $this->taskId,
                'reason'  => 'not_found',
            ]);

            return;
        }

        if ($task->status !== WarehouseRecommendationTaskStatus::accepted) {
            Log::channel('hubspot')->notice('Warehouse recommendation task was skipped because it was already claimed.', [
                'event'   => 'warehouse_task.skipped',
                'task_id' => $task->id,
                'status'  => $task->status->value,
            ]);

            return;
        }

        if ($task->expires_at?->isPast()) {
            $task->update([
                'status'       => WarehouseRecommendationTaskStatus::expired,
                'failure_code' => WarehouseRecommendationFailureCode::TaskExpired->value,
                'completed_at' => now(),
            ]);

            Log::channel('hubspot')->warning('Warehouse recommendation task expired before processing.', [
                'event'     => 'warehouse_task.expired',
                'task_id'   => $task->id,
                'tenant_id' => $task->tenant_id,
                'deal_id'   => $task->deal_id,
            ]);

            return;
        }

        $task->update([
            'status'     => WarehouseRecommendationTaskStatus::processing,
            'started_at' => now(),
            'attempts'   => $task->attempts + 1,
        ]);

        Log::channel('hubspot')->info('Warehouse recommendation task started.', [
            'event'     => 'warehouse_task.started',
            'task_id'   => $task->id,
            'tenant_id' => $task->tenant_id,
            'deal_id'   => $task->deal_id,
            'source'    => $task->source,
            'attempt'   => $task->attempts,
        ]);

        try {
            $this->trace($task, 'worker_started', 'Task claimed by the warehouse worker.', [
                'attempt' => $task->attempts,
            ]);

            $warehouseInventoryService ??= app(WarehouseInventoryService::class);
            $warehouseRecommendationService ??= app(WarehouseRecommendationService::class);
            $hubSpotCrmClient = new HubSpotCrmClient($task->tenant_id);

            $recommendations = $this->recommendations(
                $task,
                $hubSpotCrmClient,
                $warehouseInventoryService,
                $warehouseRecommendationService,
            );

            $this->trace($task, 'recommendations_completed', 'All line items received a validated warehouse recommendation.', [
                'item_count' => count($recommendations),
            ]);

            $warehouseRecommendationResultData = new WarehouseRecommendationResultData(
                items: $recommendations,
                summary: sprintf('Warehouse recommendations were produced for %d line items.', count($recommendations)),
            );

            $noteId = $this->storeResultAndNote($task, $hubSpotCrmClient, $warehouseRecommendationResultData);

            $this->completeSuccessfully($task, $warehouseRecommendationResultData, $noteId);
        } catch (Throwable $throwable) {
            $this->trace($task, 'failed', 'The worker stopped with a bounded error.', [
                'exception' => $throwable::class,
                'message'   => $this->safeText($throwable->getMessage(), 600),
            ]);

            $this->fail($task, $throwable);
        }
    }

    /**
     * @return list<WarehouseRecommendationItemData>
     */
    private function recommendations(
        WarehouseRecommendationTask $warehouseRecommendationTask,
        HubSpotCrmClient $hubSpotCrmClient,
        WarehouseInventoryService $warehouseInventoryService,
        WarehouseRecommendationService $warehouseRecommendationService,
    ): array {
        $lineItems = (new DealLineItemReader($hubSpotCrmClient, new LineItemNormalizer))->read(
            $warehouseRecommendationTask->deal_id,
            function (DealLineItemReaderStage $dealLineItemReaderStage, mixed $payload) use ($warehouseRecommendationTask): void {
                if ($dealLineItemReaderStage === DealLineItemReaderStage::DealRead && $payload instanceof HubSpotDealData) {
                    $this->trace($warehouseRecommendationTask, $dealLineItemReaderStage->value, 'Deal read and associated Line Item IDs discovered.', [
                        'deal_name'       => $this->maskText($payload->deal_name),
                        'line_item_count' => count($payload->line_item_ids),
                        'line_item_ids'   => array_map($this->maskId(...), $payload->line_item_ids),
                    ]);
                }

                if ($dealLineItemReaderStage === DealLineItemReaderStage::LineItemsRead && is_array($payload)) {
                    $this->trace($warehouseRecommendationTask, $dealLineItemReaderStage->value, 'Line Item records read from HubSpot.', [
                        'line_item_count' => count($payload),
                    ]);
                }

                if ($dealLineItemReaderStage === DealLineItemReaderStage::LineItemsNormalized && is_array($payload)) {
                    /** @var list<NormalizedLineItemData> $payload */
                    $this->trace($warehouseRecommendationTask, $dealLineItemReaderStage->value, 'Line Items passed SKU and quantity validation.', [
                        'line_item_count' => count($payload),
                        'items'           => $this->traceItems($payload),
                    ]);
                }
            },
        );

        $candidates = $warehouseInventoryService->candidates();

        $this->trace($warehouseRecommendationTask, 'inventory_loaded', 'Warehouse inventory candidates loaded.', [
            'candidate_count' => count($candidates),
            'warehouse_ids'   => array_map(
                $this->maskId(...),
                array_map(static fn (WarehouseCandidateData $warehouseCandidateData): string => $warehouseCandidateData->id, $candidates),
            ),
        ]);

        $recommendations = collect($lineItems)
            ->map(function (NormalizedLineItemData $normalizedLineItemData) use ($candidates, $warehouseRecommendationService, $warehouseRecommendationTask): WarehouseRecommendationItemData {
                $this->trace($warehouseRecommendationTask, 'ai_started', 'AI warehouse selection started for a validated Line Item.', [
                    'sku'           => $this->maskId($normalizedLineItemData->sku),
                    'requested_qty' => $normalizedLineItemData->quantity,
                ]);

                [$recommendation, $aiResponse] = $this->recommendationSingle($normalizedLineItemData, $candidates, $warehouseRecommendationService);

                $this->trace($warehouseRecommendationTask, 'ai_recommendation', 'AI recommendation validated against inventory facts.', [
                    'sku'            => $this->maskId($normalizedLineItemData->sku),
                    'requested_qty'  => $normalizedLineItemData->quantity,
                    'warehouse_id'   => $this->maskId($recommendation->warehouse_id),
                    'warehouse_name' => $recommendation->warehouse_name,
                    'available_qty'  => $recommendation->available_quantity,
                    'delivery_days'  => $recommendation->delivery_days,
                    'reason'         => $aiResponse->reason,
                    'ai_message'     => $aiResponse->raw_ai_output ?? $aiResponse->reason,
                    'warehouses'     => $this->traceCandidates($candidates, $normalizedLineItemData->quantity, $recommendation->warehouse_id),
                ]);

                return $recommendation;
            })
            ->values()
            ->all();

        /** @var list<WarehouseRecommendationItemData> $recommendations */
        return $recommendations;
    }

    /**
     * @param  list<WarehouseCandidateData>  $candidates
     * @return array{0: WarehouseRecommendationItemData, 1: WarehouseRecommendationDataResponse}
     */
    private function recommendationSingle(
        NormalizedLineItemData $normalizedLineItemData,
        array $candidates,
        WarehouseRecommendationService $warehouseRecommendationService,
    ): array {
        $warehouseRecommendationDataResponse = $warehouseRecommendationService->recommend(
            new WarehouseRecommendationData($normalizedLineItemData->sku, $normalizedLineItemData->quantity, ''),
            $candidates,
        );

        if (! $warehouseRecommendationDataResponse->selected_warehouse instanceof WarehouseSelectionData || $warehouseRecommendationDataResponse->error !== null) {
            throw new \RuntimeException(WarehouseRecommendationFailureCode::AiResultInvalid->value);
        }

        $candidate = collect($candidates)->firstWhere('id', $warehouseRecommendationDataResponse->selected_warehouse->id);

        if ($candidate === null) {
            throw new \RuntimeException(WarehouseRecommendationFailureCode::AiResultInvalid->value);
        }

        return [new WarehouseRecommendationItemData(
            line_item_id: $normalizedLineItemData->line_item_id,
            sku: $normalizedLineItemData->sku,
            quantity: $normalizedLineItemData->quantity,
            warehouse_id: $candidate->id,
            warehouse_name: $candidate->name,
            available_quantity: $candidate->available_quantity,
            delivery_days: $candidate->delivery_days,
            reason: mb_substr($warehouseRecommendationDataResponse->reason, 0, 240),
        ), $warehouseRecommendationDataResponse];
    }

    /**
     * @param  list<WarehouseCandidateData>  $candidates
     * @return list<array{id: string, name: string, available_quantity: int, distance_km: int, delivery_days: int, review_score: float, fulfils_quantity: bool, selected: bool}>
     */
    private function traceCandidates(array $candidates, int $requestedQuantity, string $selectedWarehouseId): array
    {
        return array_map(static fn (WarehouseCandidateData $warehouseCandidateData): array => [
            'id'                 => $warehouseCandidateData->id,
            'name'               => $warehouseCandidateData->name,
            'available_quantity' => $warehouseCandidateData->available_quantity,
            'distance_km'        => $warehouseCandidateData->distance_km,
            'delivery_days'      => $warehouseCandidateData->delivery_days,
            'review_score'       => $warehouseCandidateData->review_score,
            'fulfils_quantity'   => $warehouseCandidateData->available_quantity >= $requestedQuantity,
            'selected'           => $warehouseCandidateData->id === $selectedWarehouseId,
        ], $candidates);
    }

    private function storeResultAndNote(
        WarehouseRecommendationTask $warehouseRecommendationTask,
        HubSpotCrmClient $hubSpotCrmClient,
        WarehouseRecommendationResultData $warehouseRecommendationResultData,
    ): string {
        $marker = '[warehouse-recommendation-task:'.$warehouseRecommendationTask->id.']';

        $hubSpotDealNoteService = new HubSpotDealNoteService($hubSpotCrmClient);

        $noteDealId = $warehouseRecommendationTask->note_deal_id ?? $warehouseRecommendationTask->deal_id;

        $noteId = $warehouseRecommendationTask->note_id ?? $hubSpotDealNoteService->createOnce($noteDealId, $marker, [
            'hs_timestamp' => now()->toIso8601String(),
            'hs_note_body' => $this->noteBody($warehouseRecommendationResultData),
        ]);

        $this->trace($warehouseRecommendationTask, $warehouseRecommendationTask->note_id === null ? 'note_created' : 'note_reused', 'Idempotent Deal note stage completed.', [
            'note_id'        => $this->maskId($noteId),
            'target_deal_id' => $this->maskId($noteDealId),
        ]);

        Log::channel('hubspot')->info('Warehouse recommendation Deal note persisted.', [
            'event'   => $warehouseRecommendationTask->note_id === null ? 'crm.note_created' : 'crm.note_reused',
            'task_id' => $warehouseRecommendationTask->id,
            'deal_id' => $noteDealId,
            'note_id' => $noteId,
        ]);

        $warehouseRecommendationTask->update([
            'result'  => $warehouseRecommendationResultData->toArray(),
            'note_id' => $noteId,
        ]);

        return $noteId;
    }

    private function completeSuccessfully(
        WarehouseRecommendationTask $warehouseRecommendationTask,
        WarehouseRecommendationResultData $warehouseRecommendationResultData,
        string $noteId,
    ): void {
        if ($warehouseRecommendationTask->source !== 'ADMIN_CONSOLE_TEST') {
            $hubSpotWorkflowCallbackClient = new HubSpotWorkflowCallbackClient($warehouseRecommendationTask->tenant_id);
            $hubSpotWorkflowCallbackClient->complete(
                $warehouseRecommendationTask->callback_id,
                $this->successFields($warehouseRecommendationTask, $warehouseRecommendationResultData),
                $this->callbackRequestContext($warehouseRecommendationTask),
            );

            $this->trace($warehouseRecommendationTask, 'callback_completed', 'HubSpot workflow callback completed.', []);
        } else {
            $this->trace($warehouseRecommendationTask, 'callback_skipped', 'External callback skipped for the admin console test.', []);
        }

        $warehouseRecommendationTask->update([
            'status'           => WarehouseRecommendationTaskStatus::succeeded,
            'result'           => $warehouseRecommendationResultData->toArray(),
            'note_id'          => $noteId,
            'callback_sent_at' => $warehouseRecommendationTask->source === 'ADMIN_CONSOLE_TEST' ? null : now(),
            'completed_at'     => now(),
        ]);

        Log::channel('hubspot')->info('Warehouse recommendation task succeeded.', [
            'event'      => 'warehouse_task.succeeded',
            'task_id'    => $warehouseRecommendationTask->id,
            'tenant_id'  => $warehouseRecommendationTask->tenant_id,
            'deal_id'    => $warehouseRecommendationTask->deal_id,
            'item_count' => count($warehouseRecommendationResultData->items),
            'callback'   => $warehouseRecommendationTask->source === 'ADMIN_CONSOLE_TEST' ? 'synthetic_skipped' : 'completed',
        ]);
    }

    /** @param array<string, mixed> $data */
    private function trace(WarehouseRecommendationTask $warehouseRecommendationTask, string $step, string $message, array $data = []): void
    {
        $this->debugTrace[] = [
            'at'      => now()->toIso8601String(),
            'step'    => $step,
            'message' => $message,
            'data'    => $data,
        ];
        $warehouseRecommendationTask->update(['debug_trace' => $this->debugTrace]);
    }

    private function maskId(?string $value): string
    {
        return $value === null || $value === '' ? '[not provided]' : $value;
    }

    /**
     * @param  list<NormalizedLineItemData>  $items
     * @return list<array{line_item_id: string, sku: string, quantity: int}>
     */
    private function traceItems(array $items): array
    {
        $tracedItems = [];

        foreach ($items as $item) {
            $tracedItems[] = [
                'line_item_id' => $this->maskId($item->line_item_id),
                'sku'          => $this->maskId($item->sku),
                'quantity'     => $item->quantity,
            ];
        }

        return $tracedItems;
    }

    private function safeText(?string $value, int $length = 120): string
    {
        if ($value === null || $value === '') {
            return '[not provided]';
        }

        $redacted = preg_replace('/\b\d{5,}\b/', '[redacted-id]', $value) ?? $value;

        return mb_substr($redacted, 0, $length);
    }

    private function maskText(?string $value, int $length = 120): string
    {
        return $value === null || $value === '' ? '[not provided]' : mb_substr($value, 0, $length);
    }

    private function noteBody(WarehouseRecommendationResultData $warehouseRecommendationResultData): string
    {
        return $warehouseRecommendationResultData->summary."\n".json_encode($warehouseRecommendationResultData->items, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
    }

    /** @return array<string, string> */
    private function successFields(WarehouseRecommendationTask $warehouseRecommendationTask, WarehouseRecommendationResultData $warehouseRecommendationResultData): array
    {
        $first = $warehouseRecommendationResultData->items[0] ?? null;
        $warehouseName = $first instanceof WarehouseRecommendationItemData ? $first->warehouse_name : '';
        $availableQuantity = $first instanceof WarehouseRecommendationItemData ? (string) $first->available_quantity : '';
        $deliveryDays = $first instanceof WarehouseRecommendationItemData ? $first->delivery_days.' business days' : '';

        return [
            'hs_execution_state'   => HubSpotWorkflowExecutionState::Success->value,
            'taskId'               => $warehouseRecommendationTask->id,
            'status'               => 'success',
            'summary'              => $warehouseRecommendationResultData->summary,
            'recommendedWarehouse' => $warehouseName,
            'availableQuantity'    => $availableQuantity,
            'deliveryEstimate'     => $deliveryDays,
            'resultJson'           => json_encode($warehouseRecommendationResultData->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES),
            'errorCode'            => '',
        ];
    }

    private function fail(WarehouseRecommendationTask $warehouseRecommendationTask, Throwable $throwable): void
    {
        $failureCode = match ($throwable::class) {
            LineItemDataInvalidException::class => WarehouseRecommendationFailureCode::LineItemDataInvalid->value,
            HubSpotCrmException::class,
            HubSpotCrmReadException::class => WarehouseRecommendationFailureCode::CrmReadFailed->value,
            default                        => preg_match('/^[A-Z_]+$/', $throwable->getMessage()) === 1 ? $throwable->getMessage() : WarehouseRecommendationFailureCode::WorkflowFailed->value,
        };

        $warehouseRecommendationTask->update([
            'status'       => WarehouseRecommendationTaskStatus::failed,
            'failure_code' => $failureCode,
            'completed_at' => now(),
        ]);

        Log::channel('hubspot')->error('Warehouse recommendation task failed.', [
            'event'           => 'warehouse_task.failed',
            'task_id'         => $warehouseRecommendationTask->id,
            'tenant_id'       => $warehouseRecommendationTask->tenant_id,
            'deal_id'         => $warehouseRecommendationTask->deal_id,
            'failure_code'    => $failureCode,
            'exception_class' => $throwable::class,
            'failure_reason'  => mb_substr($throwable->getMessage(), 0, 240),
        ]);

        try {
            if ($warehouseRecommendationTask->source === 'ADMIN_CONSOLE_TEST') {
                return;
            }

            (new HubSpotWorkflowCallbackClient($warehouseRecommendationTask->tenant_id))->complete($warehouseRecommendationTask->callback_id, [
                'hs_execution_state' => HubSpotWorkflowExecutionState::FailContinue->value,
                'taskId'             => $warehouseRecommendationTask->id,
                'status'             => 'failed',
                'summary'            => 'Warehouse recommendation could not be completed.',
                'errorCode'          => $failureCode,
            ], $this->callbackRequestContext($warehouseRecommendationTask));
        } catch (Throwable) {
            // Ignore callback failures because the task has already been marked as failed and logged. The callback failure is not actionable for the worker.
        }
    }

    /** @return array<string, int|string> */
    private function callbackRequestContext(WarehouseRecommendationTask $warehouseRecommendationTask): array
    {
        $context = ['source' => $warehouseRecommendationTask->source];

        if (is_numeric($warehouseRecommendationTask->workflow_id)) {
            $context['workflowId'] = (int) $warehouseRecommendationTask->workflow_id;
        }

        if (is_numeric($warehouseRecommendationTask->action_definition_id)) {
            $context['actionId'] = (int) $warehouseRecommendationTask->action_definition_id;
        }

        return $context;
    }
}
