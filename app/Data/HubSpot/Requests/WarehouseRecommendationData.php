<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests;

use Spatie\LaravelData\Data;

final class WarehouseRecommendationData extends Data
{
    public function __construct(
        public string $sku,
        public int $requested_quantity,
        public string $destination_postal_code,
    ) {}
}
