<?php

declare(strict_types=1);

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

            $user = $userService->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => 'super-secret',
            ]);

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

            $user = $userService->create([
                'name' => 'Another User',
                'email' => 'another@example.com',
                'password' => 'password123',
            ]);

            expect(User::find($user->id))->not->toBeNull()
                ->and(User::find($user->id)->email)->toBe('another@example.com');
        });

        it('throws an exception when the email is already taken', function (): void {
            $userService = app(UserService::class);

            $userService->create([
                'name' => 'Existing User',
                'email' => 'duplicate@example.com',
                'password' => 'password123',
            ]);

            expect(fn () => $userService->create([
                'name' => 'Another User',
                'email' => 'duplicate@example.com',
                'password' => 'password123',
            ]))->toThrow(QueryException::class);
        });

        it('throws an exception when the required name field is missing', function (): void {
            $userService = app(UserService::class);

            expect(fn () => $userService->create([
                'email' => 'missing-name@example.com',
                'password' => 'password123',
            ]))->toThrow(ErrorException::class);
        });
    });
});
