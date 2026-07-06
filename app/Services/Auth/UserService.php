<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\RegisterData;
use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

readonly class UserService
{
    public function create(RegisterData $registerData): User
    {
        return $this->createWith($registerData, RoleLabel::user);
    }

    // (future) outside API usage :: only for admin users
    public function createWith(RegisterData $registerData, RoleLabel $roleLabel, bool $isAdmin = false): User
    {
        $user = User::create([
            'name' => $registerData->name,
            'email' => $registerData->email,
            'password' => Hash::make($registerData->password),
        ]);

        $user->role_label = $roleLabel->value;
        $user->is_admin = $isAdmin;
        $user->save();

        return $user;
    }
}
