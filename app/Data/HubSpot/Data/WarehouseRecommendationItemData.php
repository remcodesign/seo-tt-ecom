<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Data;

use Spatie\LaravelData\Data;

final class WarehouseRecommendationItemData extends Data
{
    public function __construct(
        public string $line_item_id,
        public string $sku,
        public int $quantity,
        public string $warehouse_id,
        public string $warehouse_name,
        public int $available_quantity,
        public int $delivery_days,
        public string $reason,
    ) {}
}
