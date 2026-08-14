<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Responses;

use Spatie\LaravelData\Data;

final class WarehouseSelectionData extends Data
{
    public function __construct(
        public string $id,
        public string $name,
    ) {}
}
