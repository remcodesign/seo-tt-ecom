<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('User (API)', function (): void {
    describe('User Registration', function (): void {
        it('registers a new user via POST /api/users', function (): void {
            $response = $this->postJson('/api/users', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'password' => 'password',
                'password_confirmation' => 'password', // via validation rule `Confirmed`

            ]);

            // Verify the response status and structure
            $response->assertCreated()
                ->assertJsonStructure([
                    'id',
                    'name',
                    'email',
                    'role_label',
                ])
                ->assertJson([
                    'id' => 1,
                    'name' => 'Jane Doe',
                    'email' => 'jane@example.com',
                    'role_label' => 'user',
                ]);

            // Verify that the user is actually in the database
            $this->assertDatabaseHas('users', [
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'role_label' => RoleLabel::user,
            ]);
        });

        it('rejects registration with missing fields', function (): void {
            $response = $this->postJson('/api/users', [
                'name' => 'No Email',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['email', 'password']);
        });

        it('rejects registration with mismatched passwords', function (): void {
            $response = $this->postJson('/api/users', [
                'name' => 'Bad Password',
                'email' => 'bad@example.com',
                'password' => 'password',
                'password_confirmation' => 'different',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        });

        it('rejects too-short password', function (): void {
            $response = $this->postJson('/api/users', [
                'name' => 'Short Pwd',
                'email' => 'short@example.com',
                'password' => 'short',
                'password_confirmation' => 'short',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['password']);
        });

        it('rejects duplicate email registration', function (): void {
            User::factory()->create(['email' => 'dup@example.com']);

            $response = $this->postJson('/api/users', [
                'name' => 'Duplicate',
                'email' => 'dup@example.com',
                'password' => 'password',
                'password_confirmation' => 'password',
            ]);

            $response->assertUnprocessable()
                ->assertJsonValidationErrors(['email']);
        });
    });
});
