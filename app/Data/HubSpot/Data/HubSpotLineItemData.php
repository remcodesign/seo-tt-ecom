<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Data;

use Spatie\LaravelData\Data;

final class HubSpotLineItemData extends Data
{
    public function __construct(
        public string $line_item_id,
        /** @var array<string, string> */
        public array $properties,
    ) {}
}
