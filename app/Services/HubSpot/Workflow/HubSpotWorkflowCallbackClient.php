<?php

declare(strict_types=1);

namespace App\Services\HubSpot\Workflow;

use App\Exceptions\HubSpot\HubSpotCrmReadException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

final readonly class HubSpotWorkflowCallbackClient
{
    public function __construct(private string $tenantId) {}

    /** @param  array<string, string>  $outputFields */
    public function complete(string $callbackId, array $outputFields): void
    {
        $response = $this->request()->post('/callbacks/'.rawurlencode($callbackId).'/complete', [
            'outputFields' => $outputFields,
        ]);

        if (! $response->successful()) {
            throw new HubSpotCrmReadException(
                sprintf('HubSpot workflow callback failed with status %d.', $response->status()),
            );
        }
    }

    private function request(): PendingRequest
    {
        $serviceKeys = config('hubspot.crm.service_keys', []);
        $serviceKey = is_array($serviceKeys) ? ($serviceKeys[$this->tenantId] ?? null) : null;
        $baseUrl = config('hubspot.callback.base_url', 'https://api.hubapi.com');
        $timeout = config('hubspot.callback.timeout', 10);

        if (! is_string($serviceKey) || $serviceKey === '') {
            throw new HubSpotCrmReadException('No HubSpot Service Key is configured for workflow callbacks.');
        }

        return Http::baseUrl(is_string($baseUrl) ? $baseUrl : 'https://api.hubapi.com')
            ->withToken($serviceKey)
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
}
