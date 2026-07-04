<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Data\Auth\RevokeTokenData;
use Illuminate\Support\Facades\Auth;

readonly class RevokeTokenController
{
    /**
     * Revoke the current Sanctum API token.
     */
    public function __invoke(): RevokeTokenData
    {
        Auth::user()?->currentAccessToken()?->delete();

        return new RevokeTokenData(message: 'Token revoked.');
    }
}
