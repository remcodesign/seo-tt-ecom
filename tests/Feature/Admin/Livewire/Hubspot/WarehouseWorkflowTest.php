<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\WarehouseRecommendationAgent;
use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Enums\RoleLabel;
use App\Jobs\HubSpot\ProcessWarehouseRecommendation;
use App\Livewire\Admin\HubSpot\WarehouseWorkflow;
use App\Models\HubSpot\WarehouseRecommendationTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'hubspot.portal_tenants' => [
            '12345' => ['tenant_id' => 'tenant-test', 'enabled' => true],
        ],
        'hubspot.crm.service_keys'   => ['tenant-test' => 'service-key'],
        'hubspot.crm.retry.times'    => 0,
        'hubspot.crm.retry.sleep_ms' => 0,
    ]);
});

describe('admin warehouse workflow', function (): void {
    it('redirects non-admin users', function (): void {
        $user = User::factory()->create(['role_label' => RoleLabel::user]);

        Livewire::actingAs($user)
            ->test(WarehouseWorkflow::class)
            ->assertRedirectToRoute('admin.login');
    });

    it('validates the selected portal and Deal before creating a task', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->set('form.portal_id', 'unknown')
            ->set('form.deal_id', '')
            ->set('form.callback_id', str_repeat('a', 161))
            ->call('queueAsynchronously')
            ->assertHasErrors(['form.portal_id', 'form.deal_id', 'form.callback_id']);

        expect(WarehouseRecommendationTask::query()->count())->toBe(0);
    });

    it('loads an existing non-accepted task instead of running it synchronously', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $warehouseRecommendationTask = WarehouseRecommendationTask::factory()->create([
            'portal_id'                 => '12345',
            'tenant_id'                 => 'tenant-test',
            'callback_id'               => 'admin-callback-failed',
            'action_definition_version' => '3',
            'status'                    => WarehouseRecommendationTaskStatus::failed,
            'failure_code'              => 'previous_failure',
        ]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->set('form.portal_id', '12345')
            ->set('form.deal_id', $warehouseRecommendationTask->deal_id)
            ->set('form.callback_id', 'admin-callback-failed')
            ->call('runSynchronously')
            ->assertSet('taskResult.id', $warehouseRecommendationTask->id)
            ->assertSet('taskResult.status', 'failed')
            ->assertSet('taskResult.failure_code', 'previous_failure');
    });

    it('refreshes task state and reports when the task no longer exists', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $warehouseRecommendationTask = WarehouseRecommendationTask::factory()->create([
            'status' => WarehouseRecommendationTaskStatus::processing,
        ]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->call('refreshStatus')
            ->set('taskId', $warehouseRecommendationTask->id)
            ->call('refreshStatus')
            ->assertSet('taskResult.id', $warehouseRecommendationTask->id)
            ->assertSet('taskResult.status', 'processing')
            ->set('taskId', 'missing-task-id')
            ->call('refreshStatus')
            ->assertSet('errorMessage', 'The workflow task could not be found.');
    });

    it('clears the current task result', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $warehouseRecommendationTask = WarehouseRecommendationTask::factory()->create();

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->set('taskId', $warehouseRecommendationTask->id)
            ->call('refreshStatus')
            ->set('errorMessage', 'A previous error.')
            ->call('clearResults')
            ->assertSet('taskId', null)
            ->assertSet('taskResult', null)
            ->assertSet('errorMessage', '');
    });

    it('only exposes enabled portals with valid tenant IDs', function (): void {
        config([
            'hubspot.portal_tenants' => [
                'disabled-portal' => ['tenant_id' => 'tenant-disabled', 'enabled' => false],
                'missing-tenant'  => ['enabled' => true],
                'valid-portal'    => ['tenant_id' => 'tenant-valid', 'enabled' => true],
            ],
        ]);

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->assertSet('form.portal_id', '')
            ->assertSee('valid-portal / tenant-valid')
            ->assertDontSee('disabled-portal / tenant-disabled');
    });

    it('creates one durable task and queues the production worker', function (): void {
        Queue::fake();
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->set('form.portal_id', '12345')
            ->set('form.deal_id', '500005')
            ->set('form.note_deal_id', '500006')
            ->set('form.callback_id', 'admin-callback-001')
            ->call('queueAsynchronously')
            ->assertSet('taskResult.status', 'accepted')
            ->assertSet('taskResult.source', 'ADMIN_CONSOLE_TEST');

        $warehouseRecommendationTask = WarehouseRecommendationTask::query()->firstOrFail();

        expect($warehouseRecommendationTask->tenant_id)->toBe('tenant-test')
            ->and($warehouseRecommendationTask->deal_id)->toBe('500005')
            ->and($warehouseRecommendationTask->note_deal_id)->toBe('500006')
            ->and($warehouseRecommendationTask->source)->toBe('ADMIN_CONSOLE_TEST');

        Queue::assertPushed(ProcessWarehouseRecommendation::class, fn (ProcessWarehouseRecommendation $processWarehouseRecommendation): bool => $processWarehouseRecommendation->taskId === $warehouseRecommendationTask->id);
    });

    it('reuses an existing task for the same admin callback identity', function (): void {
        Queue::fake();
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $warehouseRecommendationTask = WarehouseRecommendationTask::factory()->create([
            'portal_id'                 => '12345',
            'tenant_id'                 => 'tenant-test',
            'deal_id'                   => '500005',
            'note_deal_id'              => '500006',
            'callback_id'               => 'admin-callback-existing',
            'action_definition_version' => '3',
            'source'                    => 'ADMIN_CONSOLE_TEST',
            'status'                    => WarehouseRecommendationTaskStatus::accepted,
        ]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->set('form.portal_id', '12345')
            ->set('form.deal_id', '500005')
            ->set('form.note_deal_id', '500006')
            ->set('form.callback_id', 'admin-callback-existing')
            ->call('queueAsynchronously')
            ->assertSet('taskResult.id', $warehouseRecommendationTask->id)
            ->assertSet('taskResult.status', 'accepted');

        expect(WarehouseRecommendationTask::query()->count())->toBe(1);

        Queue::assertPushed(ProcessWarehouseRecommendation::class, 1);
        Queue::assertPushed(ProcessWarehouseRecommendation::class, fn (ProcessWarehouseRecommendation $processWarehouseRecommendation): bool => $processWarehouseRecommendation->taskId === $warehouseRecommendationTask->id);
    });

    it('runs the same worker inline and skips only the external workflow callback', function (): void {
        WarehouseRecommendationAgent::fake([
            [
                'selected_warehouse' => ['id' => 'warehouse-local', 'name' => 'Local City Warehouse'],
                'reason'             => 'Fast delivery.',
            ],
        ])->preventStrayPrompts();

        Http::fake([
            'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
            'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response(['results' => [['id' => 'li-1001']]]),
            'api.hubapi.com/crm/v3/objects/line_items/batch/read'                 => Http::response([
                'results' => [['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']]],
            ]),
            'api.hubapi.com/crm/v3/objects/notes/search' => Http::response(['results' => []]),
            'api.hubapi.com/crm/v3/objects/notes'        => Http::response(['id' => 'note-admin-001'], 201),
        ]);

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(WarehouseWorkflow::class)
            ->set('form.portal_id', '12345')
            ->set('form.deal_id', '500005')
            ->call('runSynchronously')
            ->assertSet('taskResult.status', 'succeeded')
            ->assertSet('taskResult.note_id', 'note-admin-001')
            ->assertSet('taskResult.callback_sent_at', null);

        $warehouseRecommendationTask = WarehouseRecommendationTask::query()->firstOrFail();

        expect($warehouseRecommendationTask->status)->toBe(WarehouseRecommendationTaskStatus::succeeded)
            ->and($warehouseRecommendationTask->note_deal_id)->toBe('500005')
            ->and($warehouseRecommendationTask->result['items'])->toHaveCount(1)
            ->and($warehouseRecommendationTask->debug_trace)->not->toBeEmpty();

        Http::assertSent(fn ($request): bool => $request->method() === 'POST'
            && str_contains((string) $request->url(), '/crm/v3/objects/notes')
            && data_get($request->data(), 'associations.0.to.id') === 500005);
        Http::assertNotSent(fn ($request): bool => str_contains((string) $request->url(), '/callbacks/'));
    });
});
