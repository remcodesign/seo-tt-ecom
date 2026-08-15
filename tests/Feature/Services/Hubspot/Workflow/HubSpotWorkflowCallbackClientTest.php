<?php

declare(strict_types=1);

use App\Enums\HubSpot\HubSpotWorkflowExecutionState;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Services\HubSpot\Workflow\HubSpotWorkflowCallbackClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

function workflowClientCallbackPath(string $callbackId): string
{
    $apiVersion = config('hubspot.callback.api_version');
    assert(is_string($apiVersion));

    return '/automation/actions/callbacks/'.$apiVersion.'/'.$callbackId.'/complete';
}

function workflowClientCallbackPattern(string $callbackId = '*'): string
{
    return 'api.hubapi.com'.workflowClientCallbackPath($callbackId);
}

function workflowClientOAuthTokenPattern(): string
{
    return 'api.hubapi.com/oauth/v3/token';
}

beforeEach(function (): void {
    config([
        'hubspot.callback.base_url'       => 'https://api.hubapi.com',
        'hubspot.callback.refresh_tokens' => ['tenant-test' => 'oauth-refresh-token'],
        'hubspot.callback.timeout'        => 10,
        'hubspot.client_id'               => 'client-id',
        'hubspot.client_secret'           => 'client-secret',
        'hubspot.oauth.redirect_uri'      => 'https://example.com/oauth/callback',
    ]);
});

it('completes a workflow callback with the tenant token and output fields', function (): void {
    Http::fake([
        workflowClientOAuthTokenPattern() => Http::response([
            'access_token' => 'oauth-access-token',
        ], 200),
        workflowClientCallbackPattern('callback-001') => Http::response([], 204),
    ]);

    (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
        'taskId'             => 'task-001',
    ]);

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === config('hubspot.callback.base_url').workflowClientCallbackPath('callback-001')
        && $request->hasHeader('Authorization', 'Bearer oauth-access-token')
        && $request['outputFields']['hs_execution_state'] === HubSpotWorkflowExecutionState::Success->value
        && $request['outputFields']['taskId'] === 'task-001'
        && $request['typedOutputs'] === []);
});

it('includes HubSpot response details when callback completion is rejected', function (): void {
    Http::fake([
        workflowClientOAuthTokenPattern() => Http::response([
            'access_token' => 'oauth-access-token',
        ], 200),
        workflowClientCallbackPattern('callback-001') => Http::response([
            'message'       => 'The access token is not authorized for this app.',
            'correlationId' => 'correlation-001',
        ], 403, [
            'X-HubSpot-Correlation-Id' => 'correlation-001',
        ]),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'HubSpot workflow callback failed with status 403. Details: {"response":{"message":"The access token is not authorized for this app.","correlationId":"correlation-001"},"x-hubspot-correlation-id":"correlation-001"}',
    );

    Http::assertSentCount(2);
});

it('throws a stable failure when no tenant OAuth refresh token is configured', function (): void {
    config(['hubspot.callback.refresh_tokens' => []]);
    Http::fake();

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'No HubSpot OAuth refresh token is configured for workflow callbacks.',
    );

    Http::assertNothingSent();
});

it('reads tenant OAuth refresh tokens from a configured JSON file', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'hubspot-refresh-tokens-');
    assert(is_string($path));
    file_put_contents($path, json_encode(['tenant-test' => 'file-refresh-token'], JSON_THROW_ON_ERROR));

    config([
        'hubspot.callback.refresh_tokens'      => [],
        'hubspot.callback.refresh_tokens_file' => $path,
    ]);

    Http::fake([
        workflowClientOAuthTokenPattern()             => Http::response(['access_token' => 'oauth-access-token'], 200),
        workflowClientCallbackPattern('callback-001') => Http::response([], 204),
    ]);

    try {
        (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
            'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
        ]);
    } finally {
        unlink($path);
    }

    Http::assertSent(fn ($request): bool => str_ends_with((string) $request->url(), '/oauth/v3/token')
        && $request['refresh_token'] === 'file-refresh-token');
});

it('retries a network failure and then rethrows the connection exception', function (): void {
    $attempts = 0;

    Http::fake([
        workflowClientOAuthTokenPattern() => Http::response([
            'access_token' => 'oauth-access-token',
        ], 200),
        workflowClientCallbackPattern('callback-001') => function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('Connection refused');
        },
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(ConnectionException::class, 'Connection refused');

    expect($attempts)->toBe(3);
});

it('retries server errors and rate limits before throwing the callback failure', function (int $status): void {
    Http::fake([
        workflowClientOAuthTokenPattern() => Http::response([
            'access_token' => 'oauth-access-token',
        ], 200),
        workflowClientCallbackPattern('callback-001') => Http::response([], $status),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        sprintf('HubSpot workflow callback failed with status %d.', $status),
    );

    Http::assertSentCount(4);
})->with([500, 429]);

it('does not retry an unexpected client error', function (): void {
    Http::fake([
        workflowClientOAuthTokenPattern() => Http::response([
            'access_token' => 'oauth-access-token',
        ], 200),
        workflowClientCallbackPattern('callback-001') => Http::response([], 404),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(HubSpotCrmReadException::class);

    Http::assertSentCount(2);
});
