<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\WarehouseLogs;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('accepts the warehouse log filter defaults', function (): void {
    $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

    $testable = Livewire::actingAs($admin)->test(WarehouseLogs::class);

    $testable->instance()->form->validate();

    $testable->assertHasNoErrors();
});

it('rejects invalid warehouse log filter values', function (string $field, string $value): void {
    $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

    $testable = Livewire::actingAs($admin)
        ->test(WarehouseLogs::class)
        ->set('form.'.$field, $value);

    $exception = null;

    try {
        $testable->instance()->form->validate();
    } catch (ValidationException $validationException) {
        $exception = $validationException;
    }

    assert($exception instanceof ValidationException);
    expect($exception->errors())->toHaveKey('form.'.$field);
})->with([
    'search exceeds the maximum length'      => ['search', str_repeat('a', 161)],
    'status is required'                     => ['status', ''],
    'status exceeds the maximum length'      => ['status', str_repeat('a', 41)],
    'source is required'                     => ['source', ''],
    'source exceeds the maximum length'      => ['source', str_repeat('a', 81)],
    'date filter is required'                => ['date_filter', ''],
    'date filter exceeds the maximum length' => ['date_filter', str_repeat('a', 41)],
]);
