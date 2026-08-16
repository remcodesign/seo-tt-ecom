<?php

declare(strict_types=1);

namespace App\Models\HubSpot;

use Carbon\CarbonImmutable;
use Database\Factories\HubSpot\HubSpotOAuthConnectionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $hub_id
 * @property string $tenant_id
 * @property string $access_token
 * @property string $refresh_token
 * @property CarbonImmutable|null $expires_at
 * @property list<string>|null $scopes
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable([
    'hub_id',
    'tenant_id',
    'access_token',
    'refresh_token',
    'expires_at',
    'scopes',
])]
#[Table(name: 'hubspot_oauth_connections')]
class HubSpotOAuthConnection extends Model
{
    /** @use HasFactory<HubSpotOAuthConnectionFactory> */
    use HasFactory;

    use HasUlids;

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'expires_at' => 'immutable_datetime',
            'scopes'     => 'array',
        ];
    }
}
