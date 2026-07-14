<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Blade admin users', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($this->admin);
    });

    it('shows the users list and create button', function (): void {
        User::factory()->count(2)->create();

        $this->get(route('blade.admin.users.index'))
            ->assertSuccessful()
            ->assertSee('Registered accounts')
            ->assertSee('Create user');
    });

    it('renders the create user form', function (): void {
        $this->get(route('blade.admin.users.create'))
            ->assertSuccessful()
            ->assertSee('Create a new user account')
            ->assertSee('Name')
            ->assertSee('Email');
    });

    it('stores a new user and redirects to the list', function (): void {
        $response = $this->post(route('blade.admin.users.store'), [
            'name' => 'New User',
            'email' => 'new-user@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role_label' => RoleLabel::user->value,
        ]);

        $response->assertRedirect(route('blade.admin.users.index'));
        $this->assertDatabaseHas('users', ['email' => 'new-user@example.com', 'name' => 'New User']);
    });

    it('renders the edit form with existing user data', function (): void {
        $user = User::factory()->create();

        $this->get(route('blade.admin.users.edit', $user))
            ->assertSuccessful()
            ->assertSee('Update account details')
            ->assertSee($user->email);
    });

    it('updates a user and redirects back to the list', function (): void {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
        ]);

        $response = $this->put(route('blade.admin.users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'updated@example.com',
            'password' => '',
            'password_confirmation' => '',
            'role_label' => RoleLabel::user->value,
        ]);

        $response->assertRedirect(route('blade.admin.users.index'));
        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'email' => 'updated@example.com']);
    });

    it('deletes a user', function (): void {
        $user = User::factory()->create();

        $response = $this->delete(route('blade.admin.users.destroy', $user));

        $response->assertRedirect(route('blade.admin.users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    });
});
