<?php

declare(strict_types=1);

use App\Models\HubSpot\HubSpotOAuthConnection;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'hubspot.client_id'          => 'client-id',
        'hubspot.client_secret'      => 'client-secret',
        'hubspot.oauth.redirect_uri' => 'https://example.com/api/hubspot/oauth/callback',
        'hubspot.portal_tenants'     => [
            '1234567' => ['tenant_id' => 'tenant-test', 'enabled' => true],
        ],
    ]);
});

it('exchanges the authorization code and persists the connection', function (): void {
    Http::fake([
        'api.hubapi.com/oauth/v3/token' => Http::response([
            'token_type'    => 'bearer',
            'refresh_token' => 'refresh-token-001',
            'access_token'  => 'access-token-001',
            'hub_id'        => 1234567,
            'scopes'        => ['automation', 'crm.objects.deals.read'],
            'expires_in'    => 1800,
        ], 200),
    ]);

    $this->getJson('/api/hubspot/oauth/callback?code=authorization-code-001')
        ->assertOk()
        ->assertJsonPath('message', 'HubSpot OAuth connection established.')
        ->assertJsonPath('hub_id', '1234567');

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.hubapi.com/oauth/v3/token'
        && $request['grant_type'] === 'authorization_code'
        && $request['client_id'] === 'client-id'
        && $request['client_secret'] === 'client-secret'
        && $request['redirect_uri'] === 'https://example.com/api/hubspot/oauth/callback'
        && $request['code'] === 'authorization-code-001');

    $connection = HubSpotOAuthConnection::query()->first();

    expect($connection)->not->toBeNull()
        ->and($connection->hub_id)->toBe('1234567')
        ->and($connection->tenant_id)->toBe('tenant-test')
        ->and($connection->access_token)->toBe('access-token-001')
        ->and($connection->refresh_token)->toBe('refresh-token-001')
        ->and($connection->scopes)->toBe(['automation', 'crm.objects.deals.read']);
});

it('reuses the connection for a repeated hub_id install', function (): void {
    Http::fake([
        'api.hubapi.com/oauth/v3/token' => Http::response([
            'refresh_token' => 'refresh-token-002',
            'access_token'  => 'access-token-002',
            'hub_id'        => 1234567,
            'expires_in'    => 1800,
        ], 200),
    ]);

    $this->getJson('/api/hubspot/oauth/callback?code=code-001')->assertOk();
    $this->getJson('/api/hubspot/oauth/callback?code=code-002')->assertOk();

    expect(HubSpotOAuthConnection::query()->count())->toBe(1)
        ->and(HubSpotOAuthConnection::query()->first()->access_token)->toBe('access-token-002');
});

it('returns 400 when the authorization code is missing', function (): void {
    Http::fake();

    $this->getJson('/api/hubspot/oauth/callback')
        ->assertStatus(400)
        ->assertJsonPath('message', 'Missing HubSpot OAuth authorization code.');

    Http::assertNothingSent();
    expect(HubSpotOAuthConnection::query()->count())->toBe(0);
});

it('returns 502 when the token exchange fails', function (): void {
    Http::fake([
        'api.hubapi.com/oauth/v3/token' => Http::response([
            'error'             => 'invalid_grant',
            'error_description' => 'code is invalid, expired or revoked',
        ], 400),
    ]);

    $this->getJson('/api/hubspot/oauth/callback?code=bad-code')
        ->assertStatus(502)
        ->assertJsonPath('message', 'HubSpot OAuth installation failed.');

    expect(HubSpotOAuthConnection::query()->count())->toBe(0);
});
