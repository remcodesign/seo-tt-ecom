<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Responses;

use App\Data\HubSpot\Data\WarehouseCandidateData;
use Spatie\LaravelData\Data;

final class WarehouseRecommendationDataResponse extends Data
{
    /**
     * @param  list<WarehouseCandidateData>  $candidates
     */
    public function __construct(
        public string $sku,
        public int $requested_quantity,
        public string $destination_postal_code,
        public ?WarehouseSelectionData $selected_warehouse,
        public bool $ai_generated,
        public string $reason,
        public ?string $error,
        public ?string $raw_ai_output,
        public array $candidates,
    ) {}
}
