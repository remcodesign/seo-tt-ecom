<?php

declare(strict_types=1);

namespace App\Services\HubSpot\OAuth;

use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Models\HubSpot\HubSpotOAuthConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

final readonly class HubSpotOAuthService
{
    /**
     * Exchange the HubSpot authorization code for an access and refresh token,
     * then persist the tenant-scoped connection keyed by the portal hub_id.
     */
    public function exchangeAuthorizationCode(string $code): HubSpotOAuthConnection
    {
        $clientId = config('hubspot.client_id');
        $clientSecret = config('hubspot.client_secret');
        $redirectUri = config('hubspot.oauth.redirect_uri');
        $baseUrl = config('hubspot.oauth.base_url', 'https://api.hubapi.com');
        $timeout = config('hubspot.callback.timeout', 10);

        if (! is_string($clientId) || $clientId === ''
            || ! is_string($clientSecret) || $clientSecret === ''
            || ! is_string($redirectUri) || $redirectUri === '') {
            throw new HubSpotCrmReadException('HubSpot OAuth client credentials and redirect URI are required.');
        }

        $response = Http::asForm()
            ->acceptJson()
            ->timeout(is_int($timeout) ? $timeout : 10)
            ->post(rtrim(is_string($baseUrl) ? $baseUrl : 'https://api.hubapi.com', '/').'/oauth/v3/token', [
                'grant_type'    => 'authorization_code',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'code'          => $code,
            ]);

        if (! $response->successful()) {
            throw new HubSpotCrmReadException(
                sprintf(
                    'HubSpot OAuth token exchange failed with status %d.%s',
                    $response->status(),
                    $this->responseDetails($response),
                ),
            );
        }

        $accessToken = $response->json('access_token');
        $refreshToken = $response->json('refresh_token');
        $hubId = $response->json('hub_id');
        $expiresIn = $response->json('expires_in');
        $scopes = $response->json('scopes');

        if (! is_string($accessToken) || $accessToken === ''
            || ! is_string($refreshToken) || $refreshToken === ''
            || (! is_string($hubId) && ! is_int($hubId))) {
            throw new HubSpotCrmReadException('HubSpot OAuth token exchange returned an incomplete response.');
        }

        $tenantId = $this->tenantIdForHub((string) $hubId);

        return HubSpotOAuthConnection::query()->updateOrCreate(
            ['hub_id' => (string) $hubId],
            [
                'tenant_id'     => $tenantId,
                'access_token'  => $accessToken,
                'refresh_token' => $refreshToken,
                'expires_at'    => is_int($expiresIn) ? now()->addSeconds($expiresIn) : null,
                'scopes'        => is_array($scopes) ? $scopes : null,
            ],
        );
    }

    private function tenantIdForHub(string $hubId): string
    {
        $portalTenants = config('hubspot.portal_tenants', []);
        $portalConfig = is_array($portalTenants) ? ($portalTenants[$hubId] ?? null) : null;

        if (is_array($portalConfig) && is_string($portalConfig['tenant_id'] ?? null) && $portalConfig['tenant_id'] !== '') {
            return $portalConfig['tenant_id'];
        }

        return $hubId;
    }

    private function responseDetails(Response $response): string
    {
        $details = [];
        $responseJson = $response->json();

        if (is_array($responseJson) && $responseJson !== []) {
            $details['response'] = $responseJson;
        } elseif ($response->body() !== '') {
            $details['response'] = substr($response->body(), 0, 1000);
        }

        if ($details === []) {
            return '';
        }

        $encodedDetails = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encodedDetails) ? ' Details: '.substr($encodedDetails, 0, 2000) : '';
    }
}
