<?php

declare(strict_types=1);

use App\Exceptions\HubSpot\HubSpotCrmNotConfiguredException;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Models\HubSpot\HubSpotOAuthConnection;
use App\Services\HubSpot\OAuth\HubSpotOAuthTokenProvider;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'hubspot.client_id'          => 'client-id',
        'hubspot.client_secret'      => 'client-secret',
        'hubspot.oauth.redirect_uri' => 'https://example.com/oauth/callback',
        'hubspot.callback.timeout'   => 10,
    ]);
});

it('returns the stored access token when the connection is not expired', function (): void {
    HubSpotOAuthConnection::factory()->create([
        'tenant_id'     => 'tenant-test',
        'access_token'  => 'stored-access-token',
        'refresh_token' => 'stored-refresh-token',
        'expires_at'    => now()->addMinutes(30),
    ]);

    Http::fake();

    $token = (new HubSpotOAuthTokenProvider)->accessToken('tenant-test');

    expect($token)->toBe('stored-access-token');

    Http::assertNothingSent();
});

it('refreshes and persists a new access token when the connection is expired', function (): void {
    $connection = HubSpotOAuthConnection::factory()->create([
        'tenant_id'     => 'tenant-test',
        'access_token'  => 'expired-access-token',
        'refresh_token' => 'stored-refresh-token',
        'expires_at'    => now()->subMinute(),
    ]);

    Http::fake([
        'api.hubapi.com/oauth/v3/token' => Http::response([
            'access_token'  => 'fresh-access-token',
            'refresh_token' => 'rotated-refresh-token',
            'expires_in'    => 1800,
        ], 200),
    ]);

    $token = (new HubSpotOAuthTokenProvider)->accessToken('tenant-test');

    expect($token)->toBe('fresh-access-token');

    $connection->refresh();

    expect($connection->access_token)->toBe('fresh-access-token')
        ->and($connection->refresh_token)->toBe('rotated-refresh-token')
        ->and($connection->expires_at)->not->toBeNull();

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.hubapi.com/oauth/v3/token'
        && $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'stored-refresh-token');
});

it('falls back to the legacy service key when no connection exists', function (): void {
    config(['hubspot.crm.service_keys' => ['tenant-test' => 'service-key']]);

    Http::fake();

    $token = (new HubSpotOAuthTokenProvider)->accessToken('tenant-test');

    expect($token)->toBe('service-key');

    Http::assertNothingSent();
});

it('falls back to the legacy refresh-token config when no connection exists', function (): void {
    config([
        'hubspot.crm.service_keys'        => [],
        'hubspot.callback.refresh_tokens' => ['tenant-test' => 'legacy-refresh-token'],
    ]);

    Http::fake([
        'api.hubapi.com/oauth/v3/token' => Http::response([
            'access_token' => 'legacy-access-token',
        ], 200),
    ]);

    $token = (new HubSpotOAuthTokenProvider)->accessToken('tenant-test');

    expect($token)->toBe('legacy-access-token');

    Http::assertSent(fn ($request): bool => $request['grant_type'] === 'refresh_token'
        && $request['refresh_token'] === 'legacy-refresh-token');
});

it('throws a stable failure when no connection or legacy token is configured', function (): void {
    config([
        'hubspot.crm.service_keys'        => [],
        'hubspot.callback.refresh_tokens' => [],
    ]);

    Http::fake();

    expect(fn (): string => (new HubSpotOAuthTokenProvider)->accessToken('tenant-test'))
        ->toThrow(
            HubSpotCrmNotConfiguredException::class,
            'No HubSpot OAuth connection is configured for tenant [tenant-test].',
        );

    Http::assertNothingSent();
});

it('throws a stable failure when the token refresh fails', function (): void {
    HubSpotOAuthConnection::factory()->create([
        'tenant_id'     => 'tenant-test',
        'access_token'  => 'expired-access-token',
        'refresh_token' => 'stored-refresh-token',
        'expires_at'    => now()->subMinute(),
    ]);

    Http::fake([
        'api.hubapi.com/oauth/v3/token' => Http::response([
            'error'             => 'invalid_grant',
            'error_description' => 'refresh token is invalid, expired or revoked',
        ], 400),
    ]);

    expect(fn (): string => (new HubSpotOAuthTokenProvider)->accessToken('tenant-test'))
        ->toThrow(HubSpotCrmReadException::class, 'HubSpot OAuth token refresh failed with status 400.');
});
