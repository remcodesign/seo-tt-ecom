<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'app.url' => 'http://localhost',
        'hubspot.client_secret' => 'test-client-secret',
    ]);
});

it('rejects a HubSpot request with an expired timestamp', function (): void {
    $timestamp = (string) Carbon::now()->subMinutes(6)->getTimestampMs();

    postSignedHubSpotRequest($timestamp, validSignature: true)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');
});

it('rejects a HubSpot request with an invalid signature', function (): void {
    $timestamp = (string) Carbon::now()->getTimestampMs();

    postSignedHubSpotRequest($timestamp, validSignature: false)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');
});

it('rejects a HubSpot request with a malformed timestamp', function (): void {
    postSignedHubSpotRequest('not-a-timestamp', validSignature: true)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');
});

it('rejects a HubSpot request without a required signature header', function (): void {
    $timestamp = (string) Carbon::now()->getTimestampMs();

    postSignedHubSpotRequest($timestamp, validSignature: true, includeSignature: false)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');
});

it('rejects a request when the signed body is altered', function (): void {
    $timestamp = (string) Carbon::now()->getTimestampMs();
    $body = json_encode(['email' => 'vip@example.test'], JSON_THROW_ON_ERROR);
    $signedBody = json_encode(['email' => 'other@example.test'], JSON_THROW_ON_ERROR);
    $url = url('/api/hubspot/customer-check');
    $signaturePayload = 'POST'.$url.$signedBody.$timestamp;
    $signature = base64_encode(hash_hmac('sha256', $signaturePayload, 'test-client-secret', true));

    postSignedHubSpotRequest($timestamp, validSignature: true, body: $body, signature: $signature)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');
});

it('rejects a request when the signed URL does not match the request URL', function (): void {
    $timestamp = (string) Carbon::now()->getTimestampMs();
    $body = json_encode(['email' => 'vip@example.test'], JSON_THROW_ON_ERROR);
    $signaturePayload = 'POST'.url('/api/hubspot/quote-pitch').$body.$timestamp;
    $signature = base64_encode(hash_hmac('sha256', $signaturePayload, 'test-client-secret', true));

    postSignedHubSpotRequest($timestamp, validSignature: true, signature: $signature)
        ->assertForbidden()
        ->assertJsonPath('message', 'Invalid HubSpot request.');
});

function postSignedHubSpotRequest(
    string $timestamp,
    bool $validSignature,
    bool $includeSignature = true,
    ?string $body = null,
    ?string $signature = null,
): TestResponse {
    $body ??= json_encode(['email' => 'vip@example.test'], JSON_THROW_ON_ERROR);
    $url = url('/api/hubspot/customer-check');
    $signaturePayload = 'POST'.$url.$body.$timestamp;
    $signature ??= $validSignature
        ? base64_encode(hash_hmac('sha256', $signaturePayload, 'test-client-secret', true))
        : 'invalid-signature';

    $headers = [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_X_HUBSPOT_REQUEST_TIMESTAMP' => $timestamp,
    ];

    if ($includeSignature) {
        $headers['HTTP_X_HUBSPOT_SIGNATURE_V3'] = $signature;
    }

    return test()->call('POST', '/api/hubspot/customer-check', [], [], [], $headers, $body);
}
