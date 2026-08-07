<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\Console;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('HubSpot console', function (): void {
    it('redirects a non-admin user from the HubSpot console', function (): void {
        $user = User::factory()->create(['role_label' => RoleLabel::user]);

        Livewire::actingAs($user)
            ->test(Console::class)
            ->assertRedirectToRoute('admin.login');
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

    // todo update with a more robust test that doesn't rely on the stubbed VIP email
    it('checks the known VIP customer from the admin console', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->set('email', 'vip@remcodesign.nl')
            ->call('checkCustomer')
            ->assertSet('customerResult.is_vip', true)
            ->assertSet('customerResult.allowed_discount', 15)
            ->assertSee('Returning test customer');
    });

    it('uses the fallback pitch when OpenRouter is not configured', function (): void {
        config([
            'ai.providers.openrouter.key' => null,
            'ai.providers.openrouter.models.text.default' => '',
        ]);

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->call('generatePitch')
            ->assertSet('pitchResult.provider', 'fallback')
            ->assertSee('15 percent flexibility');
    });

    it('uses OpenRouter when it is configured', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/provider-model',
        ]);

        QuotePitchAgent::fake(['A generated test pitch.'])->preventStrayPrompts();

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->call('generatePitch')
            ->assertSet('pitchResult.provider', 'openrouter')
            ->assertSee('A generated test pitch.');

        QuotePitchAgent::assertPrompted(fn (AgentPrompt $agentPrompt): bool => $agentPrompt->contains('VIP Website Renewal')
            && $agentPrompt->contains('15 percent'));
    });

    it('tries the next configured OpenRouter model after a rate limit', function (): void {
        config([
            'ai.providers.openrouter.key' => 'test-key',
            'ai.providers.openrouter.models.text.default' => 'test/rate-limited-model,test/fallback-model',
        ]);

        $attempt = 0;
        QuotePitchAgent::fake(function () use (&$attempt): string {
            $attempt++;

            if ($attempt === 1) {
                throw RateLimitedException::forProvider('openrouter', 429);
            }

            return 'A fallback model pitch.';
        })->preventStrayPrompts();

        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Console::class)
            ->call('generatePitch')
            ->assertSet('pitchResult.provider', 'openrouter')
            ->assertSet('pitchResult.model', 'test/fallback-model')
            ->assertSee('A fallback model pitch.');
    });
});
