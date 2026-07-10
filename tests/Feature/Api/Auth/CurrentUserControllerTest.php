<?php

declare(strict_types=1);

use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Current User API', function (): void {
    it('returns the authenticated user', function (): void {
        $user = User::factory()->create([
            'name' => 'Current User',
            'email' => 'current@example.com',
            'password' => bcrypt('secret'),
            'role_label' => RoleLabel::user,
        ]);

        $token = $user->createToken('test-device')->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/user')
            ->assertSuccessful()
            ->assertJson([
                'id' => $user->id,
                'name' => 'Current User',
                'role_label' => 'user',
            ]);
    });

    it('requires authentication', function (): void {
        $this->getJson('/api/user')
            ->assertUnauthorized();
    });
});
