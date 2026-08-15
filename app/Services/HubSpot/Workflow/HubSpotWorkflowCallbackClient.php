<?php

declare(strict_types=1);

namespace App\Services\HubSpot\Workflow;

use App\Exceptions\HubSpot\HubSpotCrmReadException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use JsonException;
use Throwable;

final readonly class HubSpotWorkflowCallbackClient
{
    public function __construct(private string $tenantId) {}

    /**
     * @param  array<string, string>  $outputFields
     * @param  array<string, int|string>  $requestContext
     */
    public function complete(string $callbackId, array $outputFields, array $requestContext = []): void
    {
        $payload = [
            'outputFields' => $outputFields,
            'typedOutputs' => [],
        ];

        if ($requestContext !== []) {
            $payload['requestContext'] = $requestContext;
        }

        $response = $this->request()->post($this->callbackPath($callbackId), $payload);

        if (! $response->successful()) {
            throw new HubSpotCrmReadException(
                sprintf(
                    'HubSpot workflow callback failed with status %d.%s',
                    $response->status(),
                    $this->responseDetails($response),
                ),
            );
        }
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

        foreach (['x-hubspot-correlation-id', 'x-request-id'] as $header) {
            $value = $response->header($header);

            if ($value !== '') {
                $details[$header] = $value;
            }
        }

        if ($details === []) {
            return '';
        }

        $encodedDetails = json_encode($details, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return is_string($encodedDetails) ? ' Details: '.substr($encodedDetails, 0, 2000) : '';
    }

    private function callbackPath(string $callbackId): string
    {
        $configuredApiVersion = config('hubspot.callback.api_version', '2026-03');
        $apiVersion = is_string($configuredApiVersion) ? $configuredApiVersion : '2026-03';

        return '/automation/actions/callbacks/'.rawurlencode($apiVersion).'/'.rawurlencode($callbackId).'/complete';
    }

    private function request(): PendingRequest
    {
        $accessToken = $this->accessToken();
        $baseUrl = config('hubspot.callback.base_url', 'https://api.hubapi.com');
        $timeout = config('hubspot.callback.timeout', 10);

        return Http::baseUrl(is_string($baseUrl) ? $baseUrl : 'https://api.hubapi.com')
            ->withToken($accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(is_int($timeout) ? $timeout : 10)
            ->retry(3, 100, static function (Throwable $throwable): bool {
                if (! $throwable instanceof RequestException) {
                    return true;
                }

                if ($throwable->response->status() >= 500) {
                    return true;
                }

                return $throwable->response->status() === 429;
            }, false);
    }

    private function accessToken(): string
    {
        $refreshTokens = $this->refreshTokens();
        $refreshToken = $refreshTokens[$this->tenantId] ?? null;
        $clientId = config('hubspot.client_id');
        $clientSecret = config('hubspot.client_secret');
        $redirectUri = config('hubspot.oauth.redirect_uri');
        $baseUrl = config('hubspot.oauth.base_url', 'https://api.hubapi.com');
        $timeout = config('hubspot.callback.timeout', 10);

        if (! is_string($refreshToken) || $refreshToken === '') {
            throw new HubSpotCrmReadException('No HubSpot OAuth refresh token is configured for workflow callbacks.');
        }

        if (! is_string($clientId) || $clientId === ''
            || ! is_string($clientSecret) || $clientSecret === ''
            || ! is_string($redirectUri) || $redirectUri === '') {
            throw new HubSpotCrmReadException('HubSpot OAuth client credentials and redirect URI are required for workflow callbacks.');
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

        if (! $response->successful() || ! is_string($response->json('access_token'))) {
            throw new HubSpotCrmReadException(
                sprintf(
                    'HubSpot OAuth token refresh failed with status %d.%s',
                    $response->status(),
                    $this->responseDetails($response),
                ),
            );
        }

        return $response->json('access_token');
    }

    /** @return array<string, mixed> */
    private function refreshTokens(): array
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
}
