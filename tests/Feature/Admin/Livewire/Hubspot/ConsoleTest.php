<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\Console;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('HubSpot console page', function (): void {
    it('keeps the console tools separated into tabs', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->assertSee('Quote tools')
            ->assertSeeHtml('Inventory & warehouse AI')
            ->set('activeTab', 'mcp')
            ->assertSee('Remote CRM MCP')
            ->assertSee('OAuth 2.1 + PKCE required');
    });
});
