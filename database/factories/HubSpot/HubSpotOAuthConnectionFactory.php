<?php

declare(strict_types=1);

namespace Database\Factories\HubSpot;

use App\Models\HubSpot\HubSpotOAuthConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HubSpotOAuthConnection>
 */
class HubSpotOAuthConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'hub_id'        => (string) fake()->randomNumber(7),
            'tenant_id'     => 'tenant-test',
            'access_token'  => 'access-token-'.fake()->uuid(),
            'refresh_token' => 'refresh-token-'.fake()->uuid(),
            'expires_at'    => now()->addMinutes(30),
            'scopes'        => ['automation', 'crm.objects.deals.read'],
        ];
    }
}
