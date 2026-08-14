<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests\WarehouseRecommendationWorkflow;

use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class HubSpotExecutionOriginData extends Data
{
    public function __construct(
        #[StringType, Max(64)]
        public string $portalId,

        #[StringType, Max(64)]
        public ?string $actionDefinitionId,

        #[StringType, Max(64)]
        public ?string $actionDefinitionVersion,
    ) {}
}
