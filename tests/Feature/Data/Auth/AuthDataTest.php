<?php

declare(strict_types=1);

use App\Data\Auth\RegisterData;
use App\Data\Auth\UserDataResponse;
use App\Enums\RoleLabel;
use App\Models\User;

it('hydrates RegisterData and converts it to an array', function (): void {
    $registerData = new RegisterData(
        name: 'Jane Doe',
        email: 'jane@example.com',
        password: 'password'
    );

    expect($registerData->name)->toBe('Jane Doe')
        ->and($registerData->email)->toBe('jane@example.com')
        ->and($registerData->password)->toBe('password')
        ->and($registerData->toArray())->toBe([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password',
            'role_label' => null, // but will be set to RoleLabel::user in the UserService when creating a user
        ]);
});

it('converts a User model into UserData', function (): void {
    $user = User::factory()->make([
        'id' => 42,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'role_label' => RoleLabel::admin,
    ]);

    $userDataResponse = UserDataResponse::from($user);

    expect($userDataResponse)->toBeInstanceOf(UserDataResponse::class)
        ->and($userDataResponse->id)->toBe(42)
        ->and($userDataResponse->name)->toBe('Jane Doe')
        ->and($userDataResponse->role_label)->toBe(RoleLabel::admin);
});
