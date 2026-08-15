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

beforeEach(function (): void {
    config([
        'hubspot.callback.base_url'      => 'https://api.hubapi.com',
        'hubspot.callback.access_tokens' => ['tenant-test' => 'oauth-access-token'],
        'hubspot.callback.timeout'       => 10,
    ]);
});

it('completes a workflow callback with the tenant token and output fields', function (): void {
    Http::fake([
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

it('throws a stable failure when callback completion returns a client error', function (): void {
    Http::fake([
        workflowClientCallbackPattern('callback-001') => Http::response([], 400),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'HubSpot workflow callback failed with status 400.',
    );

    Http::assertSentCount(1);
});

it('throws a stable failure when no tenant OAuth access token is configured', function (): void {
    config(['hubspot.callback.access_tokens' => []]);
    Http::fake();

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'No HubSpot OAuth access token with the automation scope is configured for workflow callbacks.',
    );

    Http::assertNothingSent();
});

it('retries a network failure and then rethrows the connection exception', function (): void {
    $attempts = 0;

    Http::fake([
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
        workflowClientCallbackPattern('callback-001') => Http::response([], $status),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        sprintf('HubSpot workflow callback failed with status %d.', $status),
    );

    Http::assertSentCount(3);
})->with([500, 429]);

it('does not retry an unexpected client error', function (): void {
    Http::fake([
        workflowClientCallbackPattern('callback-001') => Http::response([], 404),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(HubSpotCrmReadException::class);

    Http::assertSentCount(1);
});
