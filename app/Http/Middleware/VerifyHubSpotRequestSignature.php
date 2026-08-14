<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class VerifyHubSpotRequestSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $signature = $request->header('X-HubSpot-Signature-v3');
        $timestamp = $request->header('X-HubSpot-Request-Timestamp');
        $clientSecret = config('hubspot.client_secret');

        if ($signature === null
            || $signature === ''
            || $timestamp === null
            || ! ctype_digit($timestamp)
            || ! is_string($clientSecret)
            || $clientSecret === '') {
            abort(403, 'Invalid HubSpot request.');
        }

        $timestampSeconds = intdiv((int) $timestamp, 1000);

        if (abs(Carbon::now()->getTimestamp() - $timestampSeconds) > 300) {
            abort(403, 'Invalid HubSpot request.');
        }

        $url = $request->getSchemeAndHttpHost().$request->getRequestUri();
        $signaturePayload = $request->method().$url.$request->getContent().$timestamp;
        $expectedSignature = base64_encode(
            hash_hmac('sha256', $signaturePayload, $clientSecret, true),
        );

        if (! hash_equals($expectedSignature, $signature)) {
            abort(403, 'Invalid HubSpot request.');
        }

        return $next($request);
    }
}
