<?php

declare(strict_types=1);

use App\Data\HubSpot\Data\WarehouseCandidateData;
use App\Services\HubSpot\Warehouse\WarehouseInventoryService;

it('returns tenant-scoped warehouse candidates', function (): void {
    $candidates = app(WarehouseInventoryService::class)->candidates();

    expect($candidates)->toHaveCount(3)
        ->and($candidates[0])->toBeInstanceOf(WarehouseCandidateData::class)
        ->and($candidates[0]->id)->toBe('warehouse-local')
        ->and($candidates[0]->available_quantity)->toBe(12)
        ->and($candidates[0]->delivery_days)->toBe(1);
});
