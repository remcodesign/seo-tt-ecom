<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Data\Auth\RegisterData;
use App\Data\Auth\UserDataResponse;
use App\Services\Auth\UserService;

readonly class RegisterUserController
{
    public function __construct(private UserService $userService) {}

    public function __invoke(RegisterData $registerData): UserDataResponse
    {
        return UserDataResponse::from($this->userService->create($registerData));
    }
}
