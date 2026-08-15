<?php

declare(strict_types=1);

use App\Enums\HubSpot\HubSpotWorkflowExecutionState;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Services\HubSpot\Workflow\HubSpotWorkflowCallbackClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'hubspot.callback.base_url' => 'https://api.hubapi.com',
        'hubspot.crm.service_keys'  => ['tenant-test' => 'service-key'],
        'hubspot.callback.timeout'  => 10,
    ]);
});

it('completes a workflow callback with the tenant token and output fields', function (): void {
    Http::fake([
        'api.hubapi.com/callbacks/callback-001/complete' => Http::response([]),
    ]);

    (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
        'taskId'             => 'task-001',
    ]);

    Http::assertSent(fn ($request): bool => $request->method() === 'POST'
        && $request->url() === 'https://api.hubapi.com/callbacks/callback-001/complete'
        && $request->hasHeader('Authorization', 'Bearer service-key')
        && $request['outputFields']['hs_execution_state'] === HubSpotWorkflowExecutionState::Success->value
        && $request['outputFields']['taskId'] === 'task-001');
});

it('throws a stable failure when callback completion returns a client error', function (): void {
    Http::fake([
        'api.hubapi.com/callbacks/callback-001/complete' => Http::response([], 400),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'HubSpot workflow callback failed with status 400.',
    );

    Http::assertSentCount(1);
});

it('throws a stable failure when no tenant Service Key is configured', function (): void {
    config(['hubspot.crm.service_keys' => []]);
    Http::fake();

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'No HubSpot Service Key is configured for workflow callbacks.',
    );

    Http::assertNothingSent();
});

it('retries a network failure and then rethrows the connection exception', function (): void {
    $attempts = 0;

    Http::fake([
        'api.hubapi.com/callbacks/callback-001/complete' => function () use (&$attempts): never {
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
        'api.hubapi.com/callbacks/callback-001/complete' => Http::response([], $status),
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
        'api.hubapi.com/callbacks/callback-001/complete' => Http::response([], 404),
    ]);

    expect(fn () => (new HubSpotWorkflowCallbackClient('tenant-test'))->complete('callback-001', [
        'hs_execution_state' => HubSpotWorkflowExecutionState::Success->value,
    ]))->toThrow(HubSpotCrmReadException::class);

    Http::assertSentCount(1);
});
