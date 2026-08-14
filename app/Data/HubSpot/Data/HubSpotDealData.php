<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Data;

use Spatie\LaravelData\Data;

final class HubSpotDealData extends Data
{
    public function __construct(
        public string $deal_id,
        /** @var list<string> */
        public array $line_item_ids,
    ) {}
}
