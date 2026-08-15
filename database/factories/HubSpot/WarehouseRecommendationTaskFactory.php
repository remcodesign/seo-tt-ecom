<?php

declare(strict_types=1);

namespace Database\Factories\HubSpot;

use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Models\HubSpot\WarehouseRecommendationTask;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<WarehouseRecommendationTask>
 */
class WarehouseRecommendationTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'portal_id'                 => '12345',
            'tenant_id'                 => 'tenant-test',
            'deal_id'                   => (string) fake()->randomNumber(6),
            'note_deal_id'              => null,
            'callback_id'               => (string) Str::ulid(),
            'workflow_id'               => 'workflow-001',
            'action_definition_id'      => '400004',
            'action_definition_version' => '3',
            'source'                    => 'WORKFLOWS',
            'input_hash'                => Str::ulid()->toBase32(),
            'status'                    => WarehouseRecommendationTaskStatus::accepted,
            'result'                    => null,
            'failure_code'              => null,
            'attempts'                  => 0,
            'started_at'                => null,
            'completed_at'              => null,
            'expires_at'                => now()->addMinutes(15),
            'callback_sent_at'          => null,
        ];
    }
}
