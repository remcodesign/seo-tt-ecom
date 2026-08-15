<?php

declare(strict_types=1);

use App\Enums\HubSpot\WarehouseRecommendationTaskStatus;
use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\WarehouseLogs;
use App\Models\HubSpot\WarehouseRecommendationTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('redirects non-admin users', function (): void {
    $user = User::factory()->create(['role_label' => RoleLabel::user]);

    Livewire::actingAs($user)
        ->test(WarehouseLogs::class)
        ->assertRedirectToRoute('admin.login');
});

it('lists filtered tasks and opens the complete task details', function (): void {
    $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
    $failedTask = WarehouseRecommendationTask::factory()->create([
        'status'       => WarehouseRecommendationTaskStatus::failed,
        'source'       => 'WORKFLOWS',
        'deal_id'      => '516930743536',
        'failure_code' => 'crm_read_failed',
        'result'       => ['summary' => 'The workflow failed safely.'],
        'debug_trace'  => [[
            'step'    => 'failed',
            'message' => 'The worker stopped with a bounded error.',
            'data'    => ['exception' => 'RuntimeException'],
        ]],
    ]);
    WarehouseRecommendationTask::factory()->create([
        'status'  => WarehouseRecommendationTaskStatus::succeeded,
        'deal_id' => 'other-deal',
    ]);

    Livewire::actingAs($admin)
        ->test(WarehouseLogs::class)
        ->set('form.status', 'failed')
        ->assertSee('516930743536')
        ->assertDontSee('other-deal')
        ->call('selectTask', $failedTask->id)
        ->assertSee('The workflow failed safely.')
        ->assertSee('The worker stopped with a bounded error.')
        ->assertSee('RuntimeException')
        ->assertSet('selectedTaskId', $failedTask->id);
});

it('resets filters and closes task details', function (): void {
    $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
    $task = WarehouseRecommendationTask::factory()->create();

    Livewire::actingAs($admin)
        ->test(WarehouseLogs::class)
        ->set('form.search', $task->deal_id)
        ->set('form.status', 'failed')
        ->call('selectTask', $task->id)
        ->call('resetFilters')
        ->assertSet('form.search', '')
        ->assertSet('form.status', 'all')
        ->assertSet('form.source', 'all')
        ->assertSet('form.date_filter', 'all')
        ->call('clearSelection')
        ->assertSet('selectedTaskId', null);
});
