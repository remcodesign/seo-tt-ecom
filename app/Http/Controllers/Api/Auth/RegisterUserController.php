<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Services\Auth\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

readonly class RegisterUserController
{
    public function __construct(private UserService $userService) {}

    /**
     * Register a new user.
     *
     * @throws ValidationException
     */
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $this->userService->create($validated);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user->only(['id', 'name', 'email']),
        ], 201);
    }
}
