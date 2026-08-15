<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class LogHubSpotWebhookRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('hubspot.request_logging.enabled', false)) {
            return $next($request);
        }

        $startedAt = microtime(true);

        try {
            $response = $next($request);
        } catch (Throwable $throwable) {
            $this->logRequest($request, null, $startedAt);

            throw $throwable;
        }

        $this->logRequest($request, $response, $startedAt);

        return $response;
    }

    private function logRequest(Request $request, ?Response $response, float $startedAt): void
    {
        $rawBody = $request->getContent();

        Log::channel('hubspot')->info('HubSpot webhook request captured.', [
            'method'          => $request->method(),
            'url'             => $request->fullUrl(),
            'route'           => $request->route()?->uri(),
            'headers'         => $this->headers($request),
            'payload'         => $this->payload($rawBody),
            'raw_body'        => $rawBody,
            'response_status' => $response?->getStatusCode(),
            'duration_ms'     => round((microtime(true) - $startedAt) * 1000, 2),
        ]);
    }

    /** @return array<string, string|null> */
    private function headers(Request $request): array
    {
        return [
            'content_type'                => $request->header('Content-Type'),
            'user_agent'                  => $request->userAgent(),
            'x_hubspot_request_timestamp' => $request->header('X-HubSpot-Request-Timestamp'),
            'x_hubspot_signature_v3'      => $request->header('X-HubSpot-Signature-v3'),
            'x_hubspot_signature_present' => $request->hasHeader('X-HubSpot-Signature-v3') ? 'yes' : 'no',
        ];
    }

    private function payload(string $rawBody): mixed
    {
        if ($rawBody === '') {
            return null;
        }

        try {
            return json_decode($rawBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            return '[invalid JSON]';
        }
    }
}
