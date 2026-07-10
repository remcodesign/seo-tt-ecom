<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Data\Auth\RevokeTokenDataResponse;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

readonly class RevokeTokenController
{
    /**
     * Revoke the current Sanctum API token.
     */
    public function __invoke(): RevokeTokenDataResponse
    {
        $user = Auth::user();
        assert($user instanceof User);

        $user->currentAccessToken()->delete();

        return new RevokeTokenDataResponse(message: 'Token revoked.');
    }
}
