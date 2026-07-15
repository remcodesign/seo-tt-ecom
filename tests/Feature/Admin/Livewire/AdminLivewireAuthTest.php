<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Login;
use App\Models\User;
use Livewire\Livewire;

describe('Livewire admin auth', function (): void {
    it('shows the login page', function (): void {
        Livewire::test(Login::class)
            ->assertSee('Admin Login')
            ->assertSee('Sign in to your admin account');
    });

    it('redirects an authenticated admin away from the login page', function (): void {
        $admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        Livewire::actingAs($admin)
            ->test(Login::class)
            ->assertRedirectToRoute('admin.dashboard');
    });

    it('allows an admin user to sign in and access the dashboard', function (): void {
        $admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        Livewire::test(Login::class)
            ->set('email', $admin->email)
            ->set('password', 'secret')
            ->call('authenticate')
            ->assertRedirectToRoute('admin.dashboard');

        $this->assertAuthenticated();
    });

    it('shows an error message when admin credentials are invalid', function (): void {
        Livewire::test(Login::class)
            ->set('email', 'admin@example.com')
            ->set('password', 'wrong-password')
            ->call('authenticate')
            ->assertHasErrors('email')
            ->assertSee('The provided credentials are incorrect.');
    });

    it('prevents non-admin users from accessing the dashboard', function (): void {
        $user = User::factory()->create([
            'role_label' => RoleLabel::user,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    });

    it('logs out the admin user', function (): void {
        $admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($admin)
            ->post(route('admin.logout'))
            ->assertRedirect(route('admin.login'));
    });

    it('shows the dashboard with stats', function (): void {
        $admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        Livewire::actingAs($admin)
            ->test(Dashboard::class)
            ->assertSuccessful()
            ->assertSee('Admin dashboard');
    });
});
