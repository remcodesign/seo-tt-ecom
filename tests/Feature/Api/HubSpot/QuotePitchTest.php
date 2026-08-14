<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config([
        'app.url'                                     => 'http://localhost',
        'hubspot.client_secret'                       => 'test-client-secret',
        'ai.providers.openrouter.key'                 => null,
        'ai.providers.openrouter.models.text.default' => '',
    ]);
});

describe('HubSpot quote pitch API', function (): void {
    it('returns the fallback pitch for a valid signed request', function (): void {
        signedHubSpotQuotePitchPost('/api/hubspot/quote-pitch', [
            'deal_name'        => 'VIP Website Renewal',
            'deal_amount'      => 12000,
            'customer_email'   => 'vip@example.test',
            'allowed_discount' => 15,
        ])
            ->assertSuccessful()
            ->assertJsonPath('provider', 'fallback')
            ->assertJsonPath('generated', false)
            ->assertJsonPath('text', 'For this returning customer we can offer a tailored proposal with up to 15 percent flexibility.');
    });
});

function signedHubSpotQuotePitchPost(string $uri, array $payload): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $timestamp = (string) (Carbon::now()->getTimestamp() * 1000);
    $url = url($uri);
    $signaturePayload = 'POST'.$url.$body.$timestamp;
    $signature = base64_encode(hash_hmac('sha256', $signaturePayload, 'test-client-secret', true));

    return test()->call('POST', $uri, [], [], [], [
        'CONTENT_TYPE'                     => 'application/json',
        'HTTP_X_HUBSPOT_SIGNATURE_V3'      => $signature,
        'HTTP_X_HUBSPOT_REQUEST_TIMESTAMP' => $timestamp,
    ], $body);
}
