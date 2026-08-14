<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\WarehouseRecommendationAgent;
use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\Console;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('HubSpot warehouse recommendation tool', function (): void {
    it('validates warehouse recommendation inputs', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('sku', '')
            ->set('requestedQuantity', '0')
            ->set('destinationPostalCode', '')
            ->call('recommendWarehouse')
            ->assertHasErrors(['sku', 'requestedQuantity', 'destinationPostalCode']);
    });

    it('stores the warehouse recommendation and clears a previous error', function (): void {
        config([
            'ai.providers.openrouter.key'               => 'test-key',
            'ai.providers.openrouter.models.text.smart' => 'test/smart-model',
        ]);

        WarehouseRecommendationAgent::fake([[
            'selected_warehouse' => [
                'id'     => 'warehouse-premium',
                'name'   => 'Premium Fulfillment Warehouse',
                'reason' => 'The premium warehouse fulfils the requested quantity.',
            ],
        ]])->preventStrayPrompts();

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('sku', 'SKU-123')
            ->set('requestedQuantity', '2')
            ->set('destinationPostalCode', '1234AB')
            ->set('errorMessage', 'Previous error')
            ->call('recommendWarehouse')
            ->assertSet('warehouseResult.selected_warehouse', [
                'id'   => 'warehouse-premium',
                'name' => 'Premium Fulfillment Warehouse',
            ])
            ->assertSet('warehouseResult.ai_generated', true)
            ->assertSet('errorMessage', '');
    });

});
