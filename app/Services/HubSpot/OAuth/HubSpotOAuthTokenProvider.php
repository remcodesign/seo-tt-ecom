<?php

declare(strict_types=1);

namespace App\Services\HubSpot\OAuth;

use App\Exceptions\HubSpot\HubSpotCrmNotConfiguredException;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Models\HubSpot\HubSpotOAuthConnection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;

/**
 * Resolves a valid HubSpot access token for a tenant. Prefers the stored
 * OAuth connection (refreshing it when expired) and falls back to the legacy
 * config/file refresh-token map so existing deployments keep working.
 */
final readonly class HubSpotOAuthTokenProvider
{
    public function accessToken(string $tenantId): string
    {
        // Attempt to resolve a stored OAuth connection for the tenant and refresh it if expired.
        $connection = HubSpotOAuthConnection::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->first();

        if ($connection !== null) {
            if ($connection->expires_at !== null && $connection->expires_at->isPast()) {
                return $this->refreshConnection($connection);
            }

            return $connection->access_token;
        }

        // Attempt to resolve a legacy refresh token from the config or file and refresh it to get a new access token.
        $serviceKey = $this->legacyServiceKey($tenantId);

        if ($serviceKey !== null) {
            return $serviceKey;
        }

        // Attempt to resolve a legacy refresh token from the config or file and refresh it to get a new access token.
        // todo - remove this fallback once all deployments have been migrated to the new OAuth connection model.
        // remove from env : HUBSPOT_CALLBACK_REFRESH_TOKENS_FILE, HUBSPOT_CALLBACK_REFRESH_TOKENS
        return $this->refreshFromLegacyConfig($tenantId);
    }

    private function legacyServiceKey(string $tenantId): ?string
    {
        $serviceKeys = config('hubspot.crm.service_keys', []);
        $token = is_array($serviceKeys) ? ($serviceKeys[$tenantId] ?? null) : null;

        return is_string($token) && $token !== '' ? $token : null;
    }

    private function refreshConnection(HubSpotOAuthConnection $hubSpotOAuthConnection): string
    {
        $response = $this->refreshTokenResponse($hubSpotOAuthConnection->refresh_token);

        $accessToken = $response->json('access_token');
        $newRefreshToken = $response->json('refresh_token');
        $expiresIn = $response->json('expires_in');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new HubSpotCrmReadException('HubSpot OAuth token refresh returned no access token.');
        }

        $hubSpotOAuthConnection->update([
            'access_token'  => $accessToken,
            'refresh_token' => is_string($newRefreshToken) && $newRefreshToken !== ''
                ? $newRefreshToken
                : $hubSpotOAuthConnection->refresh_token,
            'expires_at' => is_int($expiresIn) ? now()->addSeconds($expiresIn) : null,
        ]);

        return $accessToken;
    }

    private function refreshFromLegacyConfig(string $tenantId): string
    {
        $refreshTokens = $this->legacyRefreshTokens();
        $refreshToken = $refreshTokens[$tenantId] ?? null;

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new HubSpotCrmNotConfiguredException(
                sprintf('No HubSpot OAuth connection is configured for tenant [%s].', $tenantId),
            );
        }

        $response = $this->refreshTokenResponse($refreshToken);
        $accessToken = $response->json('access_token');

        if (! is_string($accessToken) || $accessToken === '') {
            throw new HubSpotCrmReadException('HubSpot OAuth token refresh returned no access token.');
        }

        return $accessToken;
    }

    private function refreshTokenResponse(string $refreshToken): Response
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
                'grant_type'    => 'refresh_token',
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri'  => $redirectUri,
                'refresh_token' => $refreshToken,
            ]);

        if (! $response->successful()) {
            throw new HubSpotCrmReadException(
                sprintf(
                    'HubSpot OAuth token refresh failed with status %d.%s',
                    $response->status(),
                    $this->responseDetails($response),
                ),
            );
        }

        return $response;
    }

    /** @return array<string, mixed> */
    private function legacyRefreshTokens(): array
    {
        $configuredFile = config('hubspot.callback.refresh_tokens_file');

        if (is_string($configuredFile) && $configuredFile !== '') {
            $path = str_starts_with($configuredFile, DIRECTORY_SEPARATOR)
                ? $configuredFile
                : base_path($configuredFile);
            $contents = file_get_contents($path);

            if ($contents === false) {
                throw new HubSpotCrmReadException('HubSpot OAuth refresh-token file could not be read.');
            }

            try {
                $tokens = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
            } catch (JsonException $exception) {
                throw new HubSpotCrmReadException('HubSpot OAuth refresh-token file contains invalid JSON.', 0, $exception);
            }

            if (! is_array($tokens)) {
                throw new HubSpotCrmReadException('HubSpot OAuth refresh-token file must contain a JSON object.');
            }

            $normalizedTokens = [];

            foreach ($tokens as $tenantId => $token) {
                if (is_string($tenantId)) {
                    $normalizedTokens[$tenantId] = $token;
                }
            }

            return $normalizedTokens;
        }

        $refreshTokens = config('hubspot.callback.refresh_tokens', []);

        return is_array($refreshTokens) ? $refreshTokens : [];
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
