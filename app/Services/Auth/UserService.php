<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\RegisterData;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

readonly class UserService
{
    public function create(RegisterData $registerData): User
    {
        return User::create([
            'name' => $registerData->name,
            'email' => $registerData->email,
            'password' => Hash::make($registerData->password),
        ]);
    }
}
