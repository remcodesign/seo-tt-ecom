<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\HubSpot;

use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Services\HubSpot\OAuth\HubSpotOAuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final readonly class HubSpotOAuthController
{
    public function __construct(
        private HubSpotOAuthService $hubSpotOAuthService,
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $code = $request->query('code');

        if (! is_string($code) || $code === '') {
            return response()->json([
                'message' => 'Missing HubSpot OAuth authorization code.',
            ], 400);
        }

        try {
            $connection = $this->hubSpotOAuthService->exchangeAuthorizationCode($code);
        } catch (HubSpotCrmReadException) {
            return response()->json([
                'message' => 'HubSpot OAuth installation failed.',
            ], 502);
        }

        return response()->json([
            'message' => 'HubSpot OAuth connection established.',
            'hub_id'  => $connection->hub_id,
        ]);
    }
}
