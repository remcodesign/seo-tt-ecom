<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Responses;

use App\Data\HubSpot\Data\WarehouseRecommendationItemData;
use Spatie\LaravelData\Data;

final class WarehouseRecommendationResultData extends Data
{
    /**
     * @param  list<WarehouseRecommendationItemData>  $items
     */
    public function __construct(
        public array $items,
        public string $summary,
    ) {}
}
