<?php

declare(strict_types=1);

namespace App\Jobs\HubSpot;

use App\Data\HubSpot\Data\NormalizedLineItemData;
use App\Data\HubSpot\Data\WarehouseCandidateData;
use App\Data\HubSpot\Data\WarehouseRecommendationItemData;
use App\Data\HubSpot\Requests\WarehouseRecommendationData;
use App\Data\HubSpot\Responses\WarehouseRecommendationResultData;
use App\Data\HubSpot\Responses\WarehouseSelectionData;
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
use Throwable;

class ProcessWarehouseRecommendation implements ShouldQueue
{
    use Queueable;

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
            return;
        }

        if ($task->status !== WarehouseRecommendationTaskStatus::accepted) {
            return;
        }

        if ($task->expires_at?->isPast()) {
            $task->update([
                'status'       => WarehouseRecommendationTaskStatus::expired,
                'failure_code' => WarehouseRecommendationFailureCode::TaskExpired->value,
                'completed_at' => now(),
            ]);

            return;
        }

        $task->update([
            'status'     => WarehouseRecommendationTaskStatus::processing,
            'started_at' => now(),
        ]);

        try {
            $warehouseInventoryService ??= app(WarehouseInventoryService::class);
            $warehouseRecommendationService ??= app(WarehouseRecommendationService::class);
            $hubSpotCrmClient = new HubSpotCrmClient($task->tenant_id);
            $recommendations = $this->recommendations(
                $task,
                $hubSpotCrmClient,
                $warehouseInventoryService,
                $warehouseRecommendationService,
            );
            $result = new WarehouseRecommendationResultData(
                items: $recommendations,
                summary: sprintf('Warehouse recommendations were produced for %d line items.', count($recommendations)),
            );
            $noteId = $this->storeResultAndNote($task, $hubSpotCrmClient, $result);

            $this->completeSuccessfully($task, $result, $noteId);
        } catch (Throwable $throwable) {
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
        $lineItems = (new DealLineItemReader($hubSpotCrmClient, new LineItemNormalizer))->read($warehouseRecommendationTask->deal_id);
        $candidates = $warehouseInventoryService->candidates();

        $recommendations = collect($lineItems)
            ->map(fn (NormalizedLineItemData $normalizedLineItemData): WarehouseRecommendationItemData => $this->recommendationSingle($normalizedLineItemData, $candidates, $warehouseRecommendationService))
            ->values()
            ->all();

        /** @var list<WarehouseRecommendationItemData> $recommendations */
        return $recommendations;
    }

    /**
     * @param  list<WarehouseCandidateData>  $candidates
     */
    private function recommendationSingle(
        NormalizedLineItemData $normalizedLineItemData,
        array $candidates,
        WarehouseRecommendationService $warehouseRecommendationService,
    ): WarehouseRecommendationItemData {
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

        return new WarehouseRecommendationItemData(
            line_item_id: $normalizedLineItemData->line_item_id,
            sku: $normalizedLineItemData->sku,
            quantity: $normalizedLineItemData->quantity,
            warehouse_id: $candidate->id,
            warehouse_name: $candidate->name,
            available_quantity: $candidate->available_quantity,
            delivery_days: $candidate->delivery_days,
            reason: mb_substr($warehouseRecommendationDataResponse->reason, 0, 240),
        );
    }

    private function storeResultAndNote(
        WarehouseRecommendationTask $warehouseRecommendationTask,
        HubSpotCrmClient $hubSpotCrmClient,
        WarehouseRecommendationResultData $warehouseRecommendationResultData,
    ): string {
        $marker = '[warehouse-recommendation-task:'.$warehouseRecommendationTask->id.']';

        $hubSpotDealNoteService = new HubSpotDealNoteService($hubSpotCrmClient);
        $noteId = $warehouseRecommendationTask->note_id ?? $hubSpotDealNoteService->createOnce($warehouseRecommendationTask->deal_id, $marker, [
            'hs_timestamp' => now()->toIso8601String(),
            'hs_note_body' => $this->noteBody($warehouseRecommendationResultData),
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
        $hubSpotWorkflowCallbackClient = new HubSpotWorkflowCallbackClient($warehouseRecommendationTask->tenant_id);
        $hubSpotWorkflowCallbackClient->complete($warehouseRecommendationTask->callback_id, $this->successFields($warehouseRecommendationTask, $warehouseRecommendationResultData));

        $warehouseRecommendationTask->update([
            'status'           => WarehouseRecommendationTaskStatus::succeeded,
            'result'           => $warehouseRecommendationResultData->toArray(),
            'note_id'          => $noteId,
            'callback_sent_at' => now(),
            'completed_at'     => now(),
        ]);
    }

    private function noteBody(WarehouseRecommendationResultData $warehouseRecommendationResultData): string
    {
        return $warehouseRecommendationResultData->summary.'\n'.json_encode($warehouseRecommendationResultData->items, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES);
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

        try {
            (new HubSpotWorkflowCallbackClient($warehouseRecommendationTask->tenant_id))->complete($warehouseRecommendationTask->callback_id, [
                'hs_execution_state' => HubSpotWorkflowExecutionState::FailContinue->value,
                'taskId'             => $warehouseRecommendationTask->id,
                'status'             => 'failed',
                'summary'            => 'Warehouse recommendation could not be completed.',
                'errorCode'          => $failureCode,
            ]);
        } catch (Throwable) {
        }
    }
}
