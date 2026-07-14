<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Data\Auth\RegisterData;
use App\Data\Auth\UpdateUserData;
use App\Enums\RoleLabel;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

readonly class UserService
{
    public function create(RegisterData $registerData): User
    {
        // used for specific test, to bypass the not fillable role_label in RegisterData
        return $this->createWith($registerData, $registerData->role_label ?? RoleLabel::user);
    }

    // (future) outside API usage :: only for admin users
    public function createWith(RegisterData $registerData, RoleLabel $roleLabel): User
    {
        $user = User::create([
            'name' => $registerData->name,
            'email' => $registerData->email,
            'password' => Hash::make($registerData->password),
        ]);

        $user->role_label = $roleLabel;
        $user->save();

        return $user;
    }

    public function updateWith(User $user, UpdateUserData $updateUserData): User
    {
        $data = [
            'name' => $updateUserData->name,
            'email' => $updateUserData->email,
        ];

        if ($updateUserData->password !== null) {
            $data['password'] = Hash::make($updateUserData->password);
        }

        $user->role_label = $updateUserData->role_label;
        $user->update($data);

        return $user;
    }
}
