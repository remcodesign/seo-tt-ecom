<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Data\Auth\UserDataResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

readonly class CurrentUserController
{
    public function __invoke(): UserDataResponse
    {
        $user = Auth::user();
        assert($user instanceof User);

        return UserDataResponse::from($user);
    }
}
