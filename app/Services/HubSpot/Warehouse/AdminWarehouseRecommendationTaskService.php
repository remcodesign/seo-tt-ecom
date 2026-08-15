<?php

declare(strict_types=1);

namespace App\Services\HubSpot\Warehouse;

use App\Data\HubSpot\Requests\AdminWarehouseRecommendationData;
use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Models\HubSpot\WarehouseRecommendationTask;

final readonly class AdminWarehouseRecommendationTaskService
{
    public function create(AdminWarehouseRecommendationData $adminWarehouseRecommendationData): WarehouseRecommendationTask
    {
        $inputHash = hash('sha256', implode('|', [
            $adminWarehouseRecommendationData->portal_id,
            $adminWarehouseRecommendationData->tenant_id,
            $adminWarehouseRecommendationData->deal_id,
            $adminWarehouseRecommendationData->note_deal_id,
            $adminWarehouseRecommendationData->callback_id,
            $adminWarehouseRecommendationData->workflow_id ?? '',
            $adminWarehouseRecommendationData->action_definition_version,
            'ADMIN_CONSOLE_TEST',
        ]));

        $existingTask = WarehouseRecommendationTask::query()
            ->where('portal_id', $adminWarehouseRecommendationData->portal_id)
            ->where('action_definition_version', $adminWarehouseRecommendationData->action_definition_version)
            ->where('callback_id', $adminWarehouseRecommendationData->callback_id)
            ->first();

        if ($existingTask instanceof WarehouseRecommendationTask) {
            return $existingTask;
        }

        return WarehouseRecommendationTask::query()->create([
            'portal_id'                 => $adminWarehouseRecommendationData->portal_id,
            'tenant_id'                 => $adminWarehouseRecommendationData->tenant_id,
            'deal_id'                   => $adminWarehouseRecommendationData->deal_id,
            'note_deal_id'              => $adminWarehouseRecommendationData->note_deal_id,
            'callback_id'               => $adminWarehouseRecommendationData->callback_id,
            'workflow_id'               => $adminWarehouseRecommendationData->workflow_id,
            'action_definition_version' => $adminWarehouseRecommendationData->action_definition_version,
            'source'                    => 'ADMIN_CONSOLE_TEST',
            'input_hash'                => $inputHash,
            'status'                    => WarehouseRecommendationTaskStatus::accepted,
            'expires_at'                => now()->addMinutes(15),
        ]);
    }
}
