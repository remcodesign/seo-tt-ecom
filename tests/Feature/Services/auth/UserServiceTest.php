<?php

declare(strict_types=1);

use App\Data\Auth\RegisterData;
use App\Data\Auth\UpdateUserData;
use App\Enums\RoleLabel;
use App\Models\User;
use App\Services\Auth\UserService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

describe('UserService', function (): void {
    describe('create', function (): void {
        it('creates a user and hashes the password', function (): void {
            $userService = app(UserService::class);

            $user = $userService->create(new RegisterData(
                name: 'Test User',
                email: 'test@example.com',
                password: 'super-secret',
            ));

            expect($user)->toBeInstanceOf(User::class)
                ->and($user->exists)->toBeTrue()
                ->and($user->name)->toBe('Test User')
                ->and($user->email)->toBe('test@example.com')
                ->and(Hash::check('super-secret', $user->password))->toBeTrue()
                ->and($user->password)->not->toBe('super-secret');

            expect(User::where('email', 'test@example.com')->exists())->toBeTrue();
        });

        it('persists the returned user instance to the database', function (): void {
            $userService = app(UserService::class);

            $user = $userService->create(new RegisterData(
                name: 'Another User',
                email: 'another@example.com',
                password: 'password123',
            ));

            expect(User::find($user->id))->not->toBeNull()
                ->and(User::find($user->id)->email)->toBe('another@example.com');
        });

        it('creates a user with the requested role label', function (): void {
            $userService = app(UserService::class);

            $user = $userService->createWith(new RegisterData(
                name: 'Role User',
                email: 'role@example.com',
                password: 'password123',
            ), RoleLabel::writer);

            expect($user->role_label)->toBe(RoleLabel::writer)
                ->and($user->fresh()->role_label)->toBe(RoleLabel::writer);
        });

        it('throws an exception when the email is already taken', function (): void {
            $userService = app(UserService::class);

            $userService->create(new RegisterData(
                name: 'Existing User',
                email: 'duplicate@example.com',
                password: 'password123',
            ));

            expect(fn () => $userService->create(new RegisterData(
                name: 'Another User',
                email: 'duplicate@example.com',
                password: 'password123',
            )))->toThrow(QueryException::class);
        });
    });

    describe('updateWith', function (): void {
        it('updates user name and email', function (): void {
            $userService = app(UserService::class);
            $user = User::factory()->create([
                'name' => 'Original Name',
                'email' => 'original@example.com',
            ]);

            $updatedUser = $userService->updateWith($user, new UpdateUserData(
                name: 'Updated Name',
                email: 'updated@example.com',
                role_label: RoleLabel::user,
            ));

            expect($updatedUser->name)->toBe('Updated Name')
                ->and($updatedUser->email)->toBe('updated@example.com');
        });

        it('updates the password when provided', function (): void {
            $userService = app(UserService::class);
            $user = User::factory()->create([
                'password' => Hash::make('old-password'),
            ]);

            $userService->updateWith($user, new UpdateUserData(
                name: $user->name,
                email: $user->email,
                role_label: RoleLabel::user,
                password: 'new-password',
            ));

            $user->refresh();

            expect(Hash::check('new-password', $user->password))->toBeTrue();
        });

        it('does not change the password when not provided', function (): void {
            $userService = app(UserService::class);
            $originalPassword = Hash::make('original-password');
            $user = User::factory()->create([
                'password' => $originalPassword,
            ]);

            $userService->updateWith($user, new UpdateUserData(
                name: $user->name,
                email: $user->email,
                role_label: RoleLabel::user,
            ));

            $user->refresh();

            expect($user->password)->toBe($originalPassword);
        });

        it('updates the user role label when provided', function (): void {
            $userService = app(UserService::class);
            $user = User::factory()->create([
                'role_label' => RoleLabel::user,
            ]);

            $userService->updateWith($user, new UpdateUserData(
                name: $user->name,
                email: $user->email,
                role_label: RoleLabel::admin,
            ));

            $user->refresh();

            expect($user->role_label)->toBe(RoleLabel::admin);
        });
    });
});
