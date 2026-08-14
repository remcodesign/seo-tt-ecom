<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests;

use App\Data\HubSpot\Requests\WarehouseRecommendationWorkflow\HubSpotExecutionContextData;
use App\Data\HubSpot\Requests\WarehouseRecommendationWorkflow\HubSpotExecutionObjectData;
use App\Data\HubSpot\Requests\WarehouseRecommendationWorkflow\HubSpotExecutionOriginData;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class WarehouseRecommendationWorkflowRequestData extends Data
{
    public function __construct(
        #[StringType, Max(255)]
        public string $callbackId,

        public HubSpotExecutionOriginData $origin,

        public HubSpotExecutionContextData $context,

        public HubSpotExecutionObjectData $object,
    ) {}
}
