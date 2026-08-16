<?php

declare(strict_types=1);

namespace App\Services\HubSpot\Workflow;

use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Services\HubSpot\OAuth\HubSpotOAuthTokenProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
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
        return (new HubSpotOAuthTokenProvider)->accessToken($this->tenantId);
    }
}
