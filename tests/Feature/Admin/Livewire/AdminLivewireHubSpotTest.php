<?php

declare(strict_types=1);

use App\Ai\Agents\HubSpot\QuotePitchAgent;
use App\Enums\RoleLabel;
use App\Livewire\Admin\HubSpot\Console;
use App\Livewire\Admin\HubSpot\Logs;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Livewire\Livewire;

uses(RefreshDatabase::class);

describe('HubSpot admin tools', function (): void {
    it('redirects a non-admin user from the HubSpot console', function (): void {
        $user = User::factory()->create(['role_label' => RoleLabel::user]);

        Livewire::actingAs($user)
            ->test(Console::class)
            ->assertRedirectToRoute('admin.login');
    });

    // todo update with a more robust test that doesn't rely on the stubbed VIP email
    // it('checks the known VIP customer from the admin console', function (): void {
    //     $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

    //     Livewire::actingAs($admin)
    //         ->test(Console::class)
    //         ->set('email', 'vip@example.test')
    //         ->call('checkCustomer')
    //         ->assertSet('customerResult.is_vip', true)
    //         ->assertSet('customerResult.allowed_discount', 15)
    //         ->assertSee('Returning test customer');
    // });

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

    it('shows the dedicated HubSpot and AI log page to admins', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);

        Livewire::actingAs($admin)
            ->test(Logs::class)
            ->assertSuccessful()
            ->assertSee('Integration logs')
            ->assertSee('Only the dedicated HubSpot and AI channels are shown.')
            ->assertSee('Logs per day')
            ->assertSee('Logs per hour')
            ->assertSee('Normal logs')
            ->assertSee('Log levels')
            ->assertSee('Level')
            ->assertSee('emergency')
            ->assertSee('Clear')
            ->assertSee('Reset filters');
    });

    it('sorts log entries by date and then channel', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $hubSpotPath = storage_path('logs/hubspot-test-sort-'.uniqid().'.log');
        $aiPath = storage_path('logs/ai-test-sort-'.uniqid().'.log');

        file_put_contents($hubSpotPath, "[2026-08-06 10:00:00] hubspot.INFO: HubSpot entry\n");
        file_put_contents($aiPath, "[2026-08-06 10:00:00] ai.INFO: AI entry\n[2026-08-06 11:00:00] ai.INFO: Newest entry\n");

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'all')
                ->assertSeeInOrder(['Newest entry', 'AI entry', 'HubSpot entry']);
        } finally {
            @unlink($hubSpotPath);
            @unlink($aiPath);
        }
    });

    it('filters log entries by the current day', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $path = storage_path('logs/hubspot-test-date-filter-'.uniqid().'.log');

        file_put_contents($path, sprintf(
            "[%s] hubspot.INFO: Today entry\n[%s] hubspot.INFO: Yesterday entry\n",
            now()->format('Y-m-d H:i:s'),
            now()->subDay()->format('Y-m-d H:i:s'),
        ));

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'today')
                ->assertSee('Today entry')
                ->assertDontSee('Yesterday entry');
        } finally {
            @unlink($path);
        }
    });

    it('filters today using the log timezone calendar day', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $path = storage_path('logs/hubspot-test-date-filter-timezone-'.uniqid().'.log');

        CarbonImmutable::setTestNow(CarbonImmutable::create(2026, 8, 7, 0, 30, 0, 'UTC'));
        file_put_contents($path, "[2026-08-06 22:30:00] hubspot.INFO: Amsterdam today entry\n[2026-08-06 21:00:00] hubspot.INFO: Amsterdam yesterday entry\n");

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'today')
                ->assertSee('Amsterdam today entry')
                ->assertDontSee('Amsterdam yesterday entry');
        } finally {
            CarbonImmutable::setTestNow();
            @unlink($path);
        }
    });

    it('converts UTC log timestamps to Amsterdam time', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $path = storage_path('logs/hubspot-test-timezone-'.uniqid().'.log');

        file_put_contents($path, "[2026-08-06 14:17:23] local.INFO: Timezone marker\n");

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'all')
                ->set('search', 'Timezone marker')
                ->assertSee('2026-08-06 16:17:23');
        } finally {
            @unlink($path);
        }
    });

    it('hides test logs by default and shows filtered group counts', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $path = storage_path('logs/hubspot-test-groups-'.uniqid().'.log');

        file_put_contents($path, "[2026-08-06 10:00:00] local.INFO: Group marker normal entry\n[2026-08-06 09:00:00] testing.INFO: Group marker test entry\n");

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'all')
                ->set('search', 'Group marker')
                ->assertSet('logGroup', 'normal')
                ->assertSee('Group marker normal entry')
                ->assertDontSee('Group marker test entry')
                ->assertSee('Normal logs (1)')
                ->assertSee('Test logs (1)')
                ->set('logGroup', 'test')
                ->assertDontSee('Group marker normal entry')
                ->assertSee('Group marker test entry');
        } finally {
            @unlink($path);
        }
    });

    it('updates group counts after applying the search filter', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $path = storage_path('logs/hubspot-test-group-search-'.uniqid().'.log');

        file_put_contents($path, "[2026-08-06 10:00:00] local.INFO: Shared normal entry\n[2026-08-06 09:00:00] testing.INFO: Shared test entry\n[2026-08-06 08:00:00] testing.INFO: Other test entry\n");

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'all')
                ->set('search', 'Shared')
                ->assertSee('Normal logs (1)')
                ->assertSee('Test logs (1)');
        } finally {
            @unlink($path);
        }
    });

    it('filters entries by log level and resets all filters', function (): void {
        $admin = User::factory()->create(['role_label' => RoleLabel::admin]);
        $path = storage_path('logs/hubspot-test-level-filter-'.uniqid().'.log');

        file_put_contents($path, "[2026-08-06 10:00:00] local.INFO: Info marker\n[2026-08-06 09:00:00] local.ERROR: Error marker\n");

        try {
            Livewire::actingAs($admin)
                ->test(Logs::class)
                ->set('dateFilter', 'all')
                ->set('search', 'marker')
                ->set('enabledLogLevels', ['error'])
                ->assertSee('Error marker')
                ->assertDontSee('Info marker')
                ->assertSee('info')
                ->assertSee('error')
                ->assertSeeHtml('<span class="ml-2 text-xs font-semibold text-slate-400">(1)</span>')
                ->call('clearLogLevels')
                ->assertSet('enabledLogLevels', [])
                ->assertDontSee('Error marker')
                ->assertDontSee('Info marker')
                ->call('resetFilters')
                ->assertSet('logType', 'all')
                ->assertSet('dateFilter', 'today')
                ->assertSet('logGroup', 'normal')
                ->assertSet('search', '')
                ->assertSet('enabledLogLevels', ['emergency', 'alert', 'critical', 'error', 'warning', 'notice', 'info', 'debug']);
        } finally {
            @unlink($path);
        }
    });
});
