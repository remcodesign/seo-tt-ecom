<?php

declare(strict_types=1);

namespace App\Services\HubSpot;

use App\Data\HubSpot\Requests\WarehouseRecommendationIntakeData;
use App\Data\HubSpot\Responses\WarehouseRecommendationIntakeResponse;
use App\Enums\WarehouseRecommendationTaskStatus;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Models\HubSpot\WarehouseRecommendationTask;

final readonly class WarehouseRecommendationIntakeService
{
    public function __construct(
        private string $expirationDuration = 'PT15M',
    ) {}

    public function accept(WarehouseRecommendationIntakeData $warehouseRecommendationIntakeData): WarehouseRecommendationIntakeResponse
    {
        $inputHash = $this->inputHash($warehouseRecommendationIntakeData);

        $task = WarehouseRecommendationTask::query()
            ->where('portal_id', $warehouseRecommendationIntakeData->portal_id)
            ->where('action_definition_version', $warehouseRecommendationIntakeData->action_definition_version)
            ->where('callback_id', $warehouseRecommendationIntakeData->callback_id)
            ->first();

        if ($task instanceof WarehouseRecommendationTask) {
            return $this->response($task);
        }

        $task = WarehouseRecommendationTask::query()->create([
            'portal_id'                 => $warehouseRecommendationIntakeData->portal_id,
            'tenant_id'                 => $warehouseRecommendationIntakeData->tenant_id,
            'deal_id'                   => $warehouseRecommendationIntakeData->deal_id,
            'callback_id'               => $warehouseRecommendationIntakeData->callback_id,
            'workflow_id'               => $warehouseRecommendationIntakeData->workflow_id,
            'action_definition_id'      => $warehouseRecommendationIntakeData->action_definition_id,
            'action_definition_version' => $warehouseRecommendationIntakeData->action_definition_version,
            'source'                    => $warehouseRecommendationIntakeData->source,
            'input_hash'                => $inputHash,
            'status'                    => WarehouseRecommendationTaskStatus::accepted,
            'expires_at'                => now()->addMinutes(15),
        ]);

        ProcessWarehouseRecommendation::dispatch($task->id)->afterCommit();

        return $this->response($task);
    }

    private function response(WarehouseRecommendationTask $warehouseRecommendationTask): WarehouseRecommendationIntakeResponse
    {
        return new WarehouseRecommendationIntakeResponse(
            hs_execution_state: 'BLOCK',
            hs_expiration_duration: $this->expirationDuration,
            taskId: $warehouseRecommendationTask->id,
            status: $warehouseRecommendationTask->status->value,
        );
    }

    private function inputHash(WarehouseRecommendationIntakeData $warehouseRecommendationIntakeData): string
    {
        return hash('sha256', implode('|', [
            $warehouseRecommendationIntakeData->portal_id,
            $warehouseRecommendationIntakeData->deal_id,
            $warehouseRecommendationIntakeData->callback_id,
            $warehouseRecommendationIntakeData->workflow_id ?? '',
            $warehouseRecommendationIntakeData->action_definition_id ?? '',
            $warehouseRecommendationIntakeData->action_definition_version ?? '',
            $warehouseRecommendationIntakeData->source,
        ]));
    }
}
