<?php

declare(strict_types=1);

use App\Enums\WarehouseRecommendationTaskStatus;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Models\HubSpot\WarehouseRecommendationTask;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Testing\TestResponse;

beforeEach(function (): void {
    config([
        'app.url'                => 'http://localhost',
        'hubspot.client_secret'  => 'test-client-secret',
        'hubspot.portal_tenants' => [
            '12345' => ['tenant_id' => 'tenant-test', 'enabled' => true],
            '67890' => ['tenant_id' => 'tenant-disabled', 'enabled' => false],
        ],
    ]);
});

it('accepts a valid signed execution and returns a bounded BLOCK response', function (): void {
    Queue::fake();

    $testResponse = signedWarehouseWorkflowRequest('12345');

    $testResponse->assertOk()
        ->assertJsonPath('outputFields.hs_execution_state', 'BLOCK')
        ->assertJsonPath('outputFields.hs_expiration_duration', 'PT15M')
        ->assertJsonPath('outputFields.status', 'accepted')
        ->assertJsonMissingPath('outputFields.resultJson');

    $task = WarehouseRecommendationTask::query()->first();

    expect($task)->not->toBeNull()
        ->and($task->portal_id)->toBe('12345')
        ->and($task->tenant_id)->toBe('tenant-test')
        ->and($task->deal_id)->toBe('500005')
        ->and($task->callback_id)->toBe('callback-001')
        ->and($task->source)->toBe('WORKFLOWS')
        ->and($task->status)->toBe(WarehouseRecommendationTaskStatus::accepted);

    Queue::assertPushed(ProcessWarehouseRecommendation::class, fn (ProcessWarehouseRecommendation $processWarehouseRecommendation): bool => $processWarehouseRecommendation->taskId === $task->id);
});

it('reuses the existing task for a duplicate callback identity', function (): void {
    Queue::fake();

    $testResponse = signedWarehouseWorkflowRequest('12345');
    $firstTaskId = $testResponse->json('outputFields.taskId');

    $second = signedWarehouseWorkflowRequest('12345');

    $second->assertOk()
        ->assertJsonPath('outputFields.taskId', $firstTaskId)
        ->assertJsonPath('outputFields.status', 'accepted');

    expect(WarehouseRecommendationTask::query()->count())->toBe(1);

    Queue::assertPushed(ProcessWarehouseRecommendation::class, 1);
});

it('rejects an unknown portal before reaching workflow intake', function (): void {
    signedWarehouseWorkflowRequest('99999')
        ->assertForbidden()
        ->assertJsonPath('message', 'Unknown HubSpot portal.');

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('rejects a disabled portal before reaching workflow intake', function (): void {
    signedWarehouseWorkflowRequest('67890')
        ->assertForbidden()
        ->assertJsonPath('message', 'Unknown HubSpot portal.');

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('rejects a signed request without portal context', function (): void {
    signedWarehouseWorkflowRequest(null)
        ->assertForbidden()
        ->assertJsonPath('message', 'Unknown HubSpot portal.');

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('rejects a signed request with a missing Deal object', function (): void {
    signedWarehouseWorkflowRequest('12345', objectId: null)
        ->assertStatus(422);

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('rejects a signed request with a wrong source', function (): void {
    signedWarehouseWorkflowRequest('12345', source: 'WEBHOOK')
        ->assertStatus(422);

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('rejects a signed request with a malformed callback id', function (): void {
    signedWarehouseWorkflowRequest('12345', callbackId: '')
        ->assertStatus(422);

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('rejects an invalid signature before reaching workflow intake', function (): void {
    $payload = [
        'origin'  => ['portalId' => '12345'],
        'context' => [
            'workflowId' => 'workflow-001',
            'source'     => 'WORKFLOWS',
        ],
        'callbackId' => 'callback-001',
        'object'     => [
            'objectId'   => '500005',
            'objectType' => 'DEAL',
        ],
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) Carbon::now()->getTimestampMs();

    test()->call('POST', '/api/hubspot/warehouse-recommendation-v3', [], [], [], [
        'CONTENT_TYPE'                     => 'application/json',
        'HTTP_X_HUBSPOT_SIGNATURE_V3'      => 'invalid-signature',
        'HTTP_X_HUBSPOT_REQUEST_TIMESTAMP' => $timestamp,
    ], $body)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

function signedWarehouseWorkflowRequest(
    ?string $portalId,
    ?string $objectId = '500005',
    string $source = 'WORKFLOWS',
    string $callbackId = 'callback-001',
): TestResponse {
    $payload = [
        'origin' => array_filter([
            'portalId'                => $portalId,
            'actionDefinitionId'      => '400004',
            'actionDefinitionVersion' => '3',
        ]),
        'context' => array_filter([
            'workflowId' => 'workflow-001',
            'source'     => $source,
        ]),
        'callbackId' => $callbackId,
        'object'     => array_filter([
            'objectId'   => $objectId,
            'objectType' => 'DEAL',
        ]),
    ];
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) Carbon::now()->getTimestampMs();
    $uri = '/api/hubspot/warehouse-recommendation-v3';
    $url = url($uri);
    $signaturePayload = 'POST'.$url.$body.$timestamp;
    $signature = base64_encode(hash_hmac('sha256', $signaturePayload, 'test-client-secret', true));

    return test()->call('POST', $uri, [], [], [], [
        'CONTENT_TYPE'                     => 'application/json',
        'HTTP_X_HUBSPOT_SIGNATURE_V3'      => $signature,
        'HTTP_X_HUBSPOT_REQUEST_TIMESTAMP' => $timestamp,
    ], $body);
}
