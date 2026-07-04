<?php

declare(strict_types=1);

use App\Data\Auth\RegisterData;
use App\Data\Auth\UserData;
use App\Models\User;

it('hydrates RegisterData and converts it to an array', function (): void {
    $registerData = new RegisterData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'password',
    );

    expect($registerData->name)->toBe('Jane Doe')
        ->and($registerData->email)->toBe('jane@example.com')
        ->and($registerData->password)->toBe('password')
        ->and($registerData->toArray())->toBe([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
        ]);
});

it('converts a User model into UserData', function (): void {
    $user = User::factory()->make([
        'id' => 42,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
    ]);

    $userData = UserData::from($user);

    expect($userData)->toBeInstanceOf(UserData::class)
        ->and($userData->id)->toBe(42)
        ->and($userData->name)->toBe('Jane Doe')
        ->and($userData->email)->toBe('jane@example.com');
});
