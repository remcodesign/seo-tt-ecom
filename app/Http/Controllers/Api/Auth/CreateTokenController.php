<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Data\Auth\CreateTokenData;
use App\Data\Auth\TokenDataResponse;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

readonly class CreateTokenController
{
    /**
     * Issue a new Sanctum API token for the given credentials.
     */
    public function __invoke(CreateTokenData $createTokenData): TokenDataResponse
    {
        $user = User::where('email', $createTokenData->email)->first();

        if (! $user || ! Hash::check($createTokenData->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $token = $user->createToken($createTokenData->device_name)->plainTextToken;

        return new TokenDataResponse($token);
    }
}
