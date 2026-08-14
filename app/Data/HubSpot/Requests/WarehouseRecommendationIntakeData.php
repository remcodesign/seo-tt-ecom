<?php

declare(strict_types=1);

namespace App\Data\HubSpot\Requests;

use Spatie\LaravelData\Data;

final class WarehouseRecommendationIntakeData extends Data
{
    public function __construct(
        public string $portal_id,
        public string $tenant_id,
        public string $deal_id,
        public string $callback_id,
        public ?string $workflow_id,
        public ?string $action_definition_id,
        public ?string $action_definition_version,
        public string $source,
    ) {}
}
