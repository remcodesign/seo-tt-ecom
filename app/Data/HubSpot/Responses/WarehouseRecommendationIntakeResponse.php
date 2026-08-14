<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Responses;

use Spatie\LaravelData\Data;

final class WarehouseRecommendationIntakeResponse extends Data
{
    public function __construct(
        public string $hs_execution_state,
        public string $hs_expiration_duration,
        public string $taskId,
        public string $status,
    ) {}
}
