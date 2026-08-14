<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Data;

use Spatie\LaravelData\Data;

final class NormalizedLineItemData extends Data
{
    public function __construct(
        public string $line_item_id,
        public string $sku,
        public int $quantity,
    ) {}
}
