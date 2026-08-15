<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\WarehouseRecommendationAgent;
use App\Data\HubSpot\Requests\WarehouseRecommendationData;
use App\Data\HubSpot\Responses\WarehouseRecommendationDataResponse;
use App\Data\HubSpot\Responses\WarehouseSelectionData;
use App\Enums\HubSpot\HubSpotWorkflowExecutionState;
use App\Enums\HubSpot\WarehouseRecommendationFailureCode;
use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Models\HubSpot\WarehouseRecommendationTask;
use App\Services\HubSpot\Warehouse\WarehouseRecommendationService;
use App\Services\OpenRouter\OpenRouterService;
use Illuminate\Support\Facades\Http;

function traceData(WarehouseRecommendationTask $warehouseRecommendationTask, string $step, string $path): mixed
{
    $trace = collect($warehouseRecommendationTask->debug_trace)->firstWhere('step', $step);

    return data_get($trace, 'data.'.$path);
}

function hubspotCallbackPath(string $callbackId): string
{
    $apiVersion = config('hubspot.callback.api_version');
    assert(is_string($apiVersion));

    return '/automation/actions/callbacks/'.$apiVersion.'/'.$callbackId.'/complete';
}

function hubspotCallbackPattern(string $callbackId = '*'): string
{
    return 'api.hubapi.com'.hubspotCallbackPath($callbackId);
}

it('processes all line items, writes one note, and completes the callback', function (): void {
    config([
        'ai.providers.openrouter.key'                 => 'test-key',
        'ai.providers.openrouter.models.text.default' => 'test/model',
        'hubspot.crm.service_keys'                    => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'                     => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'                     => 0,
        'hubspot.crm.retry.sleep_ms'                  => 0,
    ]);

    WarehouseRecommendationAgent::fake([
        ['selected_warehouse' => ['id' => 'warehouse-local', 'name' => 'Local City Warehouse'], 'reason' => 'Fast delivery.'],
        ['selected_warehouse' => ['id' => 'warehouse-premium', 'name' => 'Premium Fulfillment Warehouse'], 'reason' => 'Enough stock.'],
    ])->preventStrayPrompts();

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005', 'properties' => ['dealname' => 'Northwind renewal']]),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001'], ['id' => 'li-1002']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
                ['id' => 'li-1002', 'properties' => ['hs_sku' => 'TV-002', 'quantity' => '1']],
            ],
        ]),
        'api.hubapi.com/crm/v3/objects/notes/search' => Http::response(['results' => []]),
        'api.hubapi.com/crm/v3/objects/notes'        => Http::response(['id' => 'note-001'], 201),
        hubspotCallbackPattern('callback-001')       => Http::response([], 204),
    ]);

    $task = WarehouseRecommendationTask::factory()->create([
        'status'               => WarehouseRecommendationTaskStatus::accepted,
        'started_at'           => null,
        'callback_id'          => 'callback-001',
        'deal_id'              => '500005',
        'workflow_id'          => '4720693460',
        'action_definition_id' => '400004',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->failure_code)->toBeNull()
        ->and($task->status)->toBe(WarehouseRecommendationTaskStatus::succeeded)
        ->and($task->started_at)->not->toBeNull()
        ->and($task->note_id)->toBe('note-001')
        ->and($task->result['items'])->toHaveCount(2)
        ->and(traceData($task, 'deal_read', 'deal_name'))->toBe('Northwind renewal')
        ->and(traceData($task, 'deal_read', 'line_item_ids'))->toBe(['li-1001', 'li-1002'])
        ->and(traceData($task, 'line_items_normalized', 'items.0.sku'))->toBe('TV-001')
        ->and(traceData($task, 'ai_recommendation', 'warehouse_id'))->toBe('warehouse-local')
        ->and(traceData($task, 'ai_recommendation', 'reason'))->toBe('Fast delivery.')
        ->and(traceData($task, 'ai_recommendation', 'warehouses'))->toHaveCount(3)
        ->and(traceData($task, 'ai_recommendation', 'warehouses.0.fulfils_quantity'))->toBeTrue()
        ->and(traceData($task, 'ai_recommendation', 'warehouses.2.fulfils_quantity'))->toBeFalse()
        ->and(traceData($task, 'note_created', 'note_id'))->toBe('note-001')
        ->and(collect($task->debug_trace)->pluck('step')->all())->toContain(
            'worker_started',
            'deal_read',
            'line_items_read',
            'line_items_normalized',
            'inventory_loaded',
            'ai_started',
            'ai_recommendation',
            'note_created',
            'callback_completed',
        );

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), hubspotCallbackPath('callback-001'))
        && $request['outputFields']['hs_execution_state'] === HubSpotWorkflowExecutionState::Success->value
        && $request['outputFields']['recommendedWarehouse'] === 'Local City Warehouse'
        && $request['typedOutputs'] === []
        && $request['requestContext'] === [
            'source'     => 'WORKFLOWS',
            'workflowId' => 4720693460,
            'actionId'   => 400004,
        ]);
});

it('does nothing when the task does not exist', function (): void {
    (new ProcessWarehouseRecommendation('does-not-exist'))->handle();

    expect(WarehouseRecommendationTask::query()->count())->toBe(0);
});

it('does not claim a task that is not accepted', function (): void {
    $task = WarehouseRecommendationTask::factory()->create([
        'status'     => WarehouseRecommendationTaskStatus::processing,
        'started_at' => null,
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::processing)
        ->and($task->started_at)->toBeNull();
});

it('expires an accepted task without performing workflow work', function (): void {
    Http::fake();

    $task = WarehouseRecommendationTask::factory()->create([
        'status'     => WarehouseRecommendationTaskStatus::accepted,
        'expires_at' => now()->subMinute(),
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::expired)
        ->and($task->failure_code)->toBe(WarehouseRecommendationFailureCode::TaskExpired->value)
        ->and($task->completed_at)->not->toBeNull()
        ->and($task->started_at)->toBeNull();

    Http::assertNothingSent();
});

it('fails the task when the AI response has no valid selection', function (): void {
    config([
        'hubspot.crm.service_keys' => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'  => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'  => 0,
    ]);

    WarehouseRecommendationAgent::fake([
        ['reason' => 'No warehouse available.'],
    ])->preventStrayPrompts();

    fakeRecommendationContext();

    $task = WarehouseRecommendationTask::factory()->create([
        'status'      => WarehouseRecommendationTaskStatus::accepted,
        'deal_id'     => '500005',
        'callback_id' => 'callback-invalid-ai',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::failed)
        ->and($task->failure_code)->toBe(WarehouseRecommendationFailureCode::AiResultInvalid->value)
        ->and($task->completed_at)->not->toBeNull();

    assertFailureCallback('callback-invalid-ai', WarehouseRecommendationFailureCode::AiResultInvalid->value);
});

it('fails the task when the AI selects an unknown candidate', function (): void {
    config([
        'hubspot.crm.service_keys' => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'  => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'  => 0,
    ]);

    fakeRecommendationContext();

    $recommendationService = new UnknownCandidateRecommendationService(app(OpenRouterService::class));

    $task = WarehouseRecommendationTask::factory()->create([
        'status'      => WarehouseRecommendationTaskStatus::accepted,
        'deal_id'     => '500005',
        'callback_id' => 'callback-unknown-candidate',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle(null, $recommendationService);

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::failed)
        ->and($task->failure_code)->toBe(WarehouseRecommendationFailureCode::AiResultInvalid->value);

    assertFailureCallback('callback-unknown-candidate', WarehouseRecommendationFailureCode::AiResultInvalid->value);
});

it('maps CRM failures to a stable code and continues when failure callback delivery fails', function (): void {
    config([
        'hubspot.crm.service_keys' => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'  => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'  => 0,
    ]);

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([], 500),
        hubspotCallbackPattern('callback-crm-failure')            => Http::response([], 500),
    ]);

    $task = WarehouseRecommendationTask::factory()->create([
        'status'      => WarehouseRecommendationTaskStatus::accepted,
        'deal_id'     => '500005',
        'callback_id' => 'callback-crm-failure',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::failed)
        ->and($task->failure_code)->toBe(WarehouseRecommendationFailureCode::CrmReadFailed->value)
        ->and($task->completed_at)->not->toBeNull();
});

it('does not send a failure callback for an admin console task', function (): void {
    config([
        'hubspot.crm.service_keys' => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'  => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'  => 0,
    ]);

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([], 500),
        hubspotCallbackPattern()                                  => Http::response([], 204),
    ]);

    $task = WarehouseRecommendationTask::factory()->create([
        'status'      => WarehouseRecommendationTaskStatus::accepted,
        'source'      => 'ADMIN_CONSOLE_TEST',
        'deal_id'     => '500005',
        'callback_id' => 'synthetic-admin-callback',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::failed)
        ->and($task->failure_code)->toBe(WarehouseRecommendationFailureCode::CrmReadFailed->value);

    Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/callbacks/'));
});

it('maps invalid normalized line item data to a stable code', function (): void {
    config([
        'hubspot.crm.service_keys' => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'  => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'  => 0,
    ]);

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001'], ['id' => 'li-1002']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
                ['id' => 'li-1002', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '1']],
            ],
        ]),
        hubspotCallbackPattern('callback-invalid-line-item') => Http::response([], 204),
    ]);

    $task = WarehouseRecommendationTask::factory()->create([
        'status'      => WarehouseRecommendationTaskStatus::accepted,
        'deal_id'     => '500005',
        'callback_id' => 'callback-invalid-line-item',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle();

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::failed)
        ->and($task->failure_code)->toBe(WarehouseRecommendationFailureCode::LineItemDataInvalid->value)
        ->and($task->completed_at)->not->toBeNull();

    assertFailureCallback('callback-invalid-line-item', WarehouseRecommendationFailureCode::LineItemDataInvalid->value);
});

it('records a safe placeholder when a failure has no exception message', function (): void {
    config([
        'hubspot.crm.service_keys' => ['tenant-test' => 'service-key'],
        'hubspot.callback.tokens'  => ['tenant-test' => 'callback-token'],
        'hubspot.crm.retry.times'  => 0,
    ]);

    fakeRecommendationContext();

    $task = WarehouseRecommendationTask::factory()->create([
        'status'      => WarehouseRecommendationTaskStatus::accepted,
        'deal_id'     => '500005',
        'callback_id' => 'callback-empty-message',
    ]);

    (new ProcessWarehouseRecommendation($task->id))->handle(null, new EmptyMessageRecommendationService(app(OpenRouterService::class)));

    $task->refresh();

    expect($task->status)->toBe(WarehouseRecommendationTaskStatus::failed)
        ->and(traceData($task, 'failed', 'message'))->toBe('[not provided]');
});

function fakeRecommendationContext(): void
{
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
            ],
        ]),
        hubspotCallbackPattern() => Http::response([], 204),
    ]);
}

function assertFailureCallback(string $callbackId, string $failureCode): void
{
    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), hubspotCallbackPath($callbackId))
        && $request['outputFields']['hs_execution_state'] === HubSpotWorkflowExecutionState::FailContinue->value
        && $request['outputFields']['errorCode'] === $failureCode);
}

readonly class UnknownCandidateRecommendationService extends WarehouseRecommendationService
{
    #[Override]
    public function recommend(WarehouseRecommendationData $warehouseRecommendationData, ?array $candidates = null): WarehouseRecommendationDataResponse
    {
        return new WarehouseRecommendationDataResponse(
            sku: $warehouseRecommendationData->sku,
            requested_quantity: $warehouseRecommendationData->requested_quantity,
            destination_postal_code: $warehouseRecommendationData->destination_postal_code,
            selected_warehouse: new WarehouseSelectionData('warehouse-unknown', 'Unknown Warehouse'),
            ai_generated: true,
            reason: 'Unknown candidate.',
            error: null,
            raw_ai_output: null,
            candidates: $candidates ?? [],
        );
    }
}

readonly class EmptyMessageRecommendationService extends WarehouseRecommendationService
{
    #[Override]
    public function recommend(WarehouseRecommendationData $warehouseRecommendationData, ?array $candidates = null): WarehouseRecommendationDataResponse
    {
        throw new RuntimeException('');
    }
}
