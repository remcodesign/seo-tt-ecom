<?php

declare(strict_types=1);

namespace App\Services\HubSpot\Warehouse;

use App\Data\HubSpot\Data\WarehouseCandidateData;

/**
 * Exposes authoritative, tenant-scoped warehouse facts. The candidate set is
 * Laravel-owned data; the model sees only the bounded facts required to rank
 * options. Currently returns a deterministic fixture per tenant; the target
 * implementation reads tenant-scoped database or authorized inventory facts.
 */
final readonly class WarehouseInventoryService
{
    /**
     * @return list<WarehouseCandidateData>
     */
    public function candidates(): array
    {
        // todo: replace with real tenant-scoped warehouse candidates from the
        // database or an authorized inventory API. The fixture is deterministic
        // so tests and the POC remain stable.
        return [
            new WarehouseCandidateData('warehouse-local', 'Local City Warehouse', 12, 8, 1, 4.5),
            new WarehouseCandidateData('warehouse-premium', 'Premium Fulfillment Warehouse', 4, 15, 2, 4.9),
            new WarehouseCandidateData('warehouse-partial', 'Partial Stock Warehouse', 1, 5, 1, 4.4),
        ];
    }
}
