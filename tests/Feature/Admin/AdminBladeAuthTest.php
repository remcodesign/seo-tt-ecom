<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Blade admin auth', function (): void {
    it('1. shows the login page', function (): void {
        $response = $this->get('/blade/admin/login');

        $response->assertSuccessful();
        $response->assertSee('Admin Login');
        $response->assertSee('Sign in to your admin account');
    });

    it('2.1 allows an admin user to sign in and access the dashboard', function (): void {
        $admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        $response = $this->post('/blade/admin/login', [
            'email' => $admin->email,
            'password' => 'secret',
        ]);

        $response->assertRedirect(route('blade.admin.dashboard'));

        $this->followRedirects($response)
            ->assertSuccessful()
            ->assertSee('Admin dashboard');
    });

    it('2.2 shows an error message when admin credentials are invalid', function (): void {
        $response = $this->post('/blade/admin/login', [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302);
        $response = $this->followRedirects($response);

        $response->assertSee('Unable to sign in');
        $response->assertSee('The provided credentials are incorrect.');
    });

    it('3. prevents non-admin users from accessing the dashboard (role check)', function (): void {
        $user = User::factory()->create([
            'role_label' => RoleLabel::user,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($user)
            ->get('/blade/admin')
            ->assertStatus(403);
    });

    it('4. logs out the admin user', function (): void {
        $admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($admin)
            ->post('/blade/admin/logout')
            ->assertRedirect('/blade/admin/login');
    });
});
