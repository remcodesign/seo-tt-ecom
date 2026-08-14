<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\HubSpot;

use App\Data\HubSpot\Requests\WarehouseRecommendationIntakeData;
use App\Data\HubSpot\Requests\WarehouseRecommendationWorkflowRequestData;
use App\Services\HubSpot\Warehouse\WarehouseRecommendationIntakeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class WarehouseRecommendationWorkflowController
{
    public function __construct(
        private WarehouseRecommendationIntakeService $warehouseRecommendationIntakeService,
    ) {}

    public function __invoke(
        WarehouseRecommendationWorkflowRequestData $warehouseRecommendationWorkflowRequestData,
        Request $request,
    ): JsonResponse {
        $tenantId = $request->attributes->get('hubspot_tenant_id');
        assert(is_string($tenantId) && $tenantId !== '');

        $warehouseRecommendationIntakeData = new WarehouseRecommendationIntakeData(
            portal_id: $warehouseRecommendationWorkflowRequestData->origin->portalId,
            tenant_id: $tenantId,
            deal_id: $warehouseRecommendationWorkflowRequestData->object->objectId,
            callback_id: $warehouseRecommendationWorkflowRequestData->callbackId,
            workflow_id: $warehouseRecommendationWorkflowRequestData->context->workflowId,
            action_definition_id: $warehouseRecommendationWorkflowRequestData->origin->actionDefinitionId,
            action_definition_version: $warehouseRecommendationWorkflowRequestData->origin->actionDefinitionVersion,
            source: $warehouseRecommendationWorkflowRequestData->context->source,
        );

        $warehouseRecommendationIntakeResponse = $this->warehouseRecommendationIntakeService
            ->accept($warehouseRecommendationIntakeData);

        return response()->json([
            'outputFields' => $warehouseRecommendationIntakeResponse->toArray(),
        ]);
    }
}
