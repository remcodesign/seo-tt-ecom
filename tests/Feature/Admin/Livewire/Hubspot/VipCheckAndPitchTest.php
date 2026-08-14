<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\Console;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('HubSpot VIP check and quote pitch tools', function (): void {
    it('redirects a non-admin user from the HubSpot console', function (): void {
        $user = User::factory()->create(['role_label' => RoleLabel::user]);

        Livewire::actingAs($user)
            ->test(Console::class)
            ->assertRedirectToRoute('admin.login');
    });

    it('validates all quote pitch fields before generating a pitch', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('dealName', '')
            ->set('dealAmount', '-1')
            ->set('email', 'invalid-email')
            ->set('allowedDiscount', '101')
            ->call('generatePitch')
            ->assertHasErrors(['dealName', 'dealAmount', 'email', 'allowedDiscount']);
    });

    it('stores a generated pitch and clears a previous error', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/provider-model',
        ]);

        QuotePitchAgent::fake(['A generated test pitch.'])->preventStrayPrompts();

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('dealName', 'Website Renewal')
            ->set('dealAmount', '123.45')
            ->set('email', 'customer@example.test')
            ->set('allowedDiscount', '12')
            ->set('errorMessage', 'Previous error')
            ->call('generatePitch')
            ->assertSet('pitchResult', [
                'text' => 'A generated test pitch.',
                'provider' => 'openrouter',
                'generated' => true,
                'model' => 'test/provider-model',
            ])
            ->assertSet('errorMessage', '');

        QuotePitchAgent::assertPrompted(fn (AgentPrompt $agentPrompt): bool => $agentPrompt->contains('Website Renewal')
            && $agentPrompt->contains('123.45')
            && $agentPrompt->contains('customer@example.test')
            && $agentPrompt->contains('12 percent'));
    });

    it('checks a VIP customer and clears the previous pitch result', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('email', 'vip@remcodesign.nl')
            ->set('pitchResult', [
                'text' => 'Old pitch',
                'provider' => 'fallback',
                'generated' => false,
                'model' => null,
            ])
            ->set('errorMessage', 'Old error')
            ->call('checkCustomer')
            ->assertSet('customerResult', [
                'is_vip' => true,
                'lifetime_value' => 4500,
                'allowed_discount' => 15,
                'reason' => 'Returning test customer',
                'source' => 'hubspot test rules',
            ])
            ->assertSet('pitchResult', null)
            ->assertSet('errorMessage', '');
    });

    it('validates the customer email before checking it', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('email', 'invalid-email')
            ->call('checkCustomer')
            ->assertHasErrors(['email']);
    });

    it('clears customer and pitch results', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('customerResult', [
                'is_vip' => true,
                'lifetime_value' => 4500,
                'allowed_discount' => 15,
                'reason' => 'Returning test customer',
                'source' => 'hubspot test rules',
            ])
            ->set('pitchResult', [
                'text' => 'Generated pitch',
                'provider' => 'openrouter',
                'generated' => true,
                'model' => 'test/model',
            ])
            ->set('errorMessage', 'Something went wrong')
            ->call('clearResults')
            ->assertSet('customerResult', null)
            ->assertSet('pitchResult', null)
            ->assertSet('errorMessage', '');
    });

});
