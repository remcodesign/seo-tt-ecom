<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Livewire\Admin\Users\Form;
use App\Livewire\Admin\Users\Index;
use App\Livewire\Components\Users\FilterSearch;
use App\Models\User;
use Livewire\Livewire;

describe('Livewire admin users', function (): void {
    beforeEach(function (): void {
        $this->admin = User::factory()->create([
            'role_label' => RoleLabel::admin,
            'password' => bcrypt('secret'),
        ]);

        $this->actingAs($this->admin);
    });

    describe('Index Filters/Sorting/Search', function (): void {
        it('filters users by role', function (): void {
            $writer = User::factory()->create([
                'role_label' => RoleLabel::writer,
                'email' => 'writer@example.com',
            ]);

            $admin = User::factory()->create([
                'role_label' => RoleLabel::admin,
                'email' => 'admin@example.com',
            ]);

            Livewire::test(Index::class)
                ->set('roleLabelFilter', RoleLabel::writer->value)
                ->assertSee('writer@example.com')
                ->assertDontSee('admin@example.com');
        });

        it('ignores invalid sort columns', function (): void {
            Livewire::test(Index::class)
                ->call('sortBy', 'invalid_column')
                ->assertSet('orderBy', 'id')
                ->assertSet('orderDirection', 'desc');
        });

        it('filters users by search using setSearch', function (): void {
            User::factory()->create([
                'role_label' => RoleLabel::writer,
                'email' => 'writer@example.com',
                'name' => 'Writer Example',
            ]);

            User::factory()->create([
                'role_label' => RoleLabel::admin,
                'email' => 'admin@example.com',
                'name' => 'Admin Example',
            ]);

            Livewire::test(Index::class)
                ->call('setSearch', 'writer')
                ->assertSet('search', 'writer')
                ->assertSee('writer@example.com')
                ->assertDontSee('admin@example.com');
        });

        it('filters users by role label using setRoleLabelFilter', function (): void {
            User::factory()->create([
                'role_label' => RoleLabel::writer,
                'email' => 'writer@example.com',
            ]);

            User::factory()->create([
                'role_label' => RoleLabel::admin,
                'email' => 'admin@example.com',
            ]);

            Livewire::test(Index::class)
                ->call('setRoleLabelFilter', RoleLabel::admin->value)
                ->assertSet('roleLabelFilter', RoleLabel::admin->value)
                ->assertSee('admin@example.com')
                ->assertDontSee('writer@example.com');
        });

        it('orders users by name and email', function (): void {
            $first = User::factory()->create(['name' => 'Aaron', 'email' => 'aaron@example.com']);
            $second = User::factory()->create(['name' => 'Zoe', 'email' => 'zoe@example.com']);

            Livewire::test(Index::class)
                ->call('sortBy', 'name')
                ->assertSet('orderBy', 'name')
                ->assertSet('orderDirection', 'asc')
                ->call('sortBy', 'name')
                ->assertSet('orderDirection', 'desc')
                ->call('sortBy', 'email')
                ->assertSet('orderBy', 'email')
                ->assertSet('orderDirection', 'asc');
        });

        it('dispatches searchUpdated when the search input changes', function (): void {
            Livewire::test(FilterSearch::class, ['roleLabels' => [
                ['value' => RoleLabel::user->value, 'label' => 'User'],
            ]])
                ->set('search', 'test')
                ->assertDispatched('searchUpdated', 'test');
        });

        it('dispatches roleLabelFilterUpdated when the role filter changes', function (): void {
            Livewire::test(FilterSearch::class, ['roleLabels' => [
                ['value' => RoleLabel::user->value, 'label' => 'User'],
            ]])
                ->set('roleLabelFilter', RoleLabel::admin->value)
                ->assertDispatched('roleLabelFilterUpdated', RoleLabel::admin->value);
        });
    });

    describe('CRUD : Create', function (): void {
        it('shows the users list and create button', function (): void {
            User::factory()->count(2)->create();

            Livewire::test(Index::class)
                ->assertSuccessful()
                ->assertSee('Registered accounts')
                ->assertSee('Create user');
        });

        it('renders the create user form', function (): void {
            Livewire::test(Form::class)
                ->assertSuccessful()
                ->assertSee('Create a new user account')
                ->assertSee('Name')
                ->assertSee('Email');
        });

        it('stores a new user and redirects to the list', function (): void {
            Livewire::test(Form::class)
                ->set('form.name', 'New User')
                ->set('form.email', 'new-user@example.com')
                ->set('form.password', 'password123')
                ->set('form.password_confirmation', 'password123')
                ->set('form.role_label', RoleLabel::user->value)
                ->call('save')
                ->assertRedirectToRoute('admin.users.index');

            $this->assertDatabaseHas('users', ['email' => 'new-user@example.com', 'name' => 'New User']);
        });
    });

    describe('CRUD : Delete', function (): void {
        it('does not delete the authenticated user', function (): void {
            Livewire::test(Index::class)
                ->call('delete', $this->admin->id)
                ->assertSee('You cannot delete your own account.');

            $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
        });

        it('deletes a user', function (): void {
            $user = User::factory()->create();

            Livewire::test(Index::class)
                ->call('delete', $user->id)
                ->assertSuccessful();

            $this->assertDatabaseMissing('users', ['id' => $user->id]);
        });
    });

    describe('CRUD : Update', function (): void {
        it('renders the edit form with existing user data', function (): void {
            $user = User::factory()->create();

            Livewire::test(Form::class, ['user' => $user])
                ->assertSuccessful()
                ->assertSee('Update account details')
                ->assertSet('form.email', $user->email);
        });

        it('updates a user and redirects back to the list', function (): void {
            $user = User::factory()->create([
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ]);

            Livewire::test(Form::class, ['user' => $user])
                ->set('form.name', 'Updated Name')
                ->set('form.email', 'updated@example.com')
                ->set('form.role_label', RoleLabel::user->value)
                ->set('form.password', '')
                ->set('form.password_confirmation', '')
                ->call('save')
                ->assertRedirectToRoute('admin.users.index');

            $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Updated Name', 'email' => 'updated@example.com']);
        });

        it('updates a user with the same email', function (): void {
            $user = User::factory()->create([
                'name' => 'Same Email User',
                'email' => 'same@example.com',
            ]);

            Livewire::test(Form::class, ['user' => $user])
                ->set('form.name', 'Same Email User Updated')
                ->set('form.email', 'same@example.com')
                ->set('form.role_label', RoleLabel::user->value)
                ->set('form.password', '')
                ->set('form.password_confirmation', '')
                ->call('save')
                ->assertRedirectToRoute('admin.users.index');

            $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Same Email User Updated', 'email' => 'same@example.com']);
        });
    });
});
