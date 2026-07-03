<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

readonly class RevokeTokenController
{
    /**
     * Revoke the current Sanctum API token.
     */
    public function __invoke(): JsonResponse
    {
        Auth::user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Token revoked.'], 200);
    }
}
