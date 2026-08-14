<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests\WarehouseRecommendationWorkflow;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\StringType;
use Spatie\LaravelData\Data;

final class HubSpotExecutionObjectData extends Data
{
    public function __construct(
        #[StringType, Max(64)]
        public string $objectId,

        #[StringType, In(['DEAL', 'deal'])]
        public string $objectType,
    ) {}
}
