<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Auth (API)', function (): void {
    describe('Sanctum Token Creation', function (): void {
        it('issues a token for valid credentials', function (): void {
            $user = User::factory()->create([
                'email' => 'token@example.com',
                'password' => bcrypt('secret'),
            ]);

            $response = $this->postJson('/api/sanctum/token', [
                'email' => 'token@example.com',
                'password' => 'secret',
                'device_name' => 'test-device',
            ]);

            $response->assertSuccessful()
                ->assertJsonStructure(['token']);

            $token = $response->json('token');
            expect($token)->toBeString()->not->toBeEmpty();
        });

        it('rejects invalid credentials', function (): void {
            User::factory()->create([
                'email' => 'valid@example.com',
                'password' => bcrypt('correct'),
            ]);

            $this->postJson('/api/sanctum/token', [
                'email' => 'valid@example.com',
                'password' => 'wrong-password',
                'device_name' => 'test-device',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['email']);
        });

        it('rejects missing fields', function (): void {
            $this->postJson('/api/sanctum/token', [
                'email' => 'only@email.com',
            ])->assertUnprocessable()
                ->assertJsonValidationErrors(['password', 'device_name']);
        });

        it('issued token can authenticate subsequent requests', function (): void {
            $user = User::factory()->create([
                'email' => 'full-cycle@example.com',
                'password' => bcrypt('secret'),
            ]);

            $response = $this->postJson('/api/sanctum/token', [
                'email' => 'full-cycle@example.com',
                'password' => 'secret',
                'device_name' => 'full-test',
            ]);

            $token = $response->json('token');

            // Use the token to access a protected endpoint
            $this->getJson('/api/blog/posts', [
                'Authorization' => 'Bearer '.$token,
            ])->assertSuccessful();
        });
    });

    describe('Sanctum Token Revocation', function (): void {
        it('revokes the current token', function (): void {
            $user = User::factory()->create([
                'email' => 'revoke@example.com',
                'password' => bcrypt('secret'),
            ]);

            $token = $user->createToken('pre-test')->plainTextToken;

            // Verify the token exists in the database
            expect($user->tokens()->count())->toBe(1);

            $this->withToken($token)
                ->deleteJson('/api/sanctum/tokens/current')
                ->assertSuccessful()
                ->assertJson(['message' => 'Token revoked.']);

            // Verify the token was deleted from the database
            expect($user->tokens()->count())->toBe(0);
        });

        it('requires authentication to revoke', function (): void {
            $this->deleteJson('/api/sanctum/tokens/current')
                ->assertUnauthorized();
        });
    });
});
