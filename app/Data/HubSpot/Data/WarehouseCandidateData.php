<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Data;

use Spatie\LaravelData\Data;

final class WarehouseCandidateData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
        public int $available_quantity,
        public int $distance_km,
        public int $delivery_days,
        public float $review_score,
    ) {}
}
