<?php

declare(strict_types=1);

use App\Data\HubSpot\Data\HubSpotDealData;
use App\Data\HubSpot\Data\HubSpotLineItemData;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Services\HubSpot\CRM\HubSpotCrmClient;
use GuzzleHttp\Psr7\Response as PsrResponse;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response as HttpClientResponse;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'hubspot.crm.base_url'       => 'https://api.hubapi.com',
        'hubspot.crm.tenants'        => ['tenant-test' => 'pat-test-token'],
        'hubspot.crm.retry.times'    => 0,
        'hubspot.crm.retry.sleep_ms' => 0,
    ]);
});

it('returns an empty list without requesting an empty line item batch', function (): void {
    Http::fake();

    $client = new HubSpotCrmClient('tenant-test');

    expect($client->readLineItems([], ['hs_sku', 'quantity']))->toBe([]);

    Http::assertNothingSent();
});

it('throws a stable failure when the line item batch read returns no results', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => null,
        ]),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    expect(fn (): array => $client->readLineItems(['li-1001'], ['hs_sku', 'quantity']))
        ->toThrow(HubSpotCrmReadException::class, 'line item batch read returned no results');
});

it('skips a batch result that is not an array', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                'not-an-array',
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
            ],
        ]),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    $lineItems = $client->readLineItems(['li-1001'], ['hs_sku', 'quantity']);

    expect($lineItems)->toHaveCount(1)
        ->and($lineItems[0])->toBeInstanceOf(HubSpotLineItemData::class)
        ->and($lineItems[0]->line_item_id)->toBe('li-1001');
});

it('skips a batch result that has no id', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['properties' => ['hs_sku' => 'TV-000', 'quantity' => '1']],
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
            ],
        ]),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    $lineItems = $client->readLineItems(['missing-id', 'li-1001'], ['hs_sku', 'quantity']);

    expect($lineItems)->toHaveCount(1)
        ->and($lineItems[0])->toBeInstanceOf(HubSpotLineItemData::class)
        ->and($lineItems[0]->line_item_id)->toBe('li-1001');
});

it('uses an empty property map when a batch result has non-array properties', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => 'invalid-properties'],
            ],
        ]),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    $lineItems = $client->readLineItems(['li-1001'], ['hs_sku', 'quantity']);

    expect($lineItems)->toHaveCount(1)
        ->and($lineItems[0]->properties)->toBe([]);
});

it('throws when note creation returns no note id', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/notes' => Http::response([], 201),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    expect(fn (): string => $client->createDealNote('500005', [
        'hs_note_body' => 'Warehouse recommendation result.',
    ]))->toThrow(
        HubSpotCrmReadException::class,
        'HubSpot note create returned no note id.',
    );

    Http::assertSentCount(1);
});

it('retries a server error (5xx) and then throws a stable failure', function (): void {
    config(['hubspot.crm.retry.times' => 3]);

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([], 500),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    expect(fn (): HubSpotDealData => $client->readDeal('500005'))
        ->toThrow(HubSpotCrmReadException::class, 'deal read failed');

    // The 5xx is retried (retry.times = 3 means 3 retries + 1 original request).
    Http::assertSentCount(4);
});

it('retries a rate limited (429) response and then throws a stable failure', function (): void {
    config(['hubspot.crm.retry.times' => 2]);

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([], 429),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    expect(fn (): HubSpotDealData => $client->readDeal('500005'))
        ->toThrow(HubSpotCrmReadException::class, 'deal read failed');

    Http::assertSentCount(3);
});

it('does not retry a client error (4xx)', function (): void {
    config(['hubspot.crm.retry.times' => 3]);

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([], 404),
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    expect(fn (): HubSpotDealData => $client->readDeal('500005'))
        ->toThrow(HubSpotCrmReadException::class, 'deal read failed');

    // 4xx is not retried, so only the original request is sent.
    Http::assertSentCount(1);
});

it('retries a network connection exception and then throws a stable failure', function (): void {
    config(['hubspot.crm.retry.times' => 2]);

    $attempts = 0;

    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => function () use (&$attempts): never {
            $attempts++;

            throw new ConnectionException('Connection refused');
        },
    ]);

    $client = new HubSpotCrmClient('tenant-test');

    expect(fn (): HubSpotDealData => $client->readDeal('500005'))
        ->toThrow(ConnectionException::class);

    expect($attempts)->toBe(3);
});

it('classifies response and non-response retry failures', function (): void {
    $client = new HubSpotCrmClient('tenant-test');
    $method = new ReflectionMethod($client, 'shouldRetry');

    expect($method->invoke($client, new RuntimeException('unexpected failure')))->toBeFalse()
        ->and($method->invoke($client, new HttpClientResponse(new PsrResponse(500))))->toBeTrue()
        ->and($method->invoke($client, new HttpClientResponse(new PsrResponse(429))))->toBeTrue()
        ->and($method->invoke($client, new HttpClientResponse(new PsrResponse(404))))->toBeFalse();
});
