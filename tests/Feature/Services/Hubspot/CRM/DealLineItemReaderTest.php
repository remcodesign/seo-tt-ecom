<?php

declare(strict_types=1);

use App\Data\HubSpot\Data\NormalizedLineItemData;
use App\Exceptions\HubSpot\HubSpotCrmNotConfiguredException;
use App\Exceptions\HubSpot\HubSpotCrmReadException;
use App\Exceptions\HubSpot\LineItemDataInvalidException;
use App\Services\HubSpot\CRM\DealLineItemReader;
use App\Services\HubSpot\CRM\HubSpotCrmClient;
use App\Services\HubSpot\CRM\LineItemNormalizer;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    config([
        'hubspot.crm.base_url'            => 'https://api.hubapi.com',
        'hubspot.crm.service_keys'        => ['tenant-test' => 'service-key'],
        'hubspot.crm.properties.sku'      => 'hs_sku',
        'hubspot.crm.properties.quantity' => 'quantity',
        'hubspot.crm.retry.times'         => 0,
        'hubspot.crm.retry.sleep_ms'      => 0,
    ]);
});

it('reads a deal, its associated line items, and normalizes them', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([
            'id'         => '500005',
            'properties' => ['hs_object_id' => '500005'],
        ]),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [
                ['id' => 'li-1001'],
                ['id' => 'li-1002'],
            ],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
                ['id' => 'li-1002', 'properties' => ['hs_sku' => 'TV-002', 'quantity' => '1']],
            ],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    $normalized = $reader->read('500005');

    expect($normalized)->toHaveCount(2)
        ->and($normalized[0])->toBeInstanceOf(NormalizedLineItemData::class)
        ->and($normalized[0]->line_item_id)->toBe('li-1001')
        ->and($normalized[0]->sku)->toBe('TV-001')
        ->and($normalized[0]->quantity)->toBe(2)
        ->and($normalized[1]->sku)->toBe('TV-002')
        ->and($normalized[1]->quantity)->toBe(1);

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/crm/v3/objects/deals/500005')
        && $request->hasHeader('Authorization', 'Bearer service-key'));
});

it('requests only the configured sku and quantity properties in the batch read', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']]],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    $reader->read('500005');

    Http::assertSent(fn ($request): bool => str_contains((string) $request->url(), '/crm/v3/objects/line_items/batch/read')
        && $request['properties'] === ['hs_sku', 'quantity']);
});

it('paginates deal line item associations', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::sequence()
            ->push(['results' => [['id' => 'li-1001']], 'paging' => ['next' => ['after' => 'cursor-2']]])
            ->push(['results' => [['id' => 'li-1002']]]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
                ['id' => 'li-1002', 'properties' => ['hs_sku' => 'TV-002', 'quantity' => '1']],
            ],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    $normalized = $reader->read('500005');

    expect($normalized)->toHaveCount(2);

    Http::assertSentCount(4);
});

it('throws a stable failure when a line item is missing a sku', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [['id' => 'li-1001', 'properties' => ['quantity' => '2']]],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    expect(fn (): array => $reader->read('500005'))
        ->toThrow(LineItemDataInvalidException::class, 'missing a SKU');
});

it('throws a stable failure when a line item has an invalid quantity', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '0']]],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    expect(fn (): array => $reader->read('500005'))
        ->toThrow(LineItemDataInvalidException::class, 'invalid quantity');
});

it('throws a stable failure when a sku is duplicated across line items', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [['id' => 'li-1001'], ['id' => 'li-1002']],
        ]),
        'api.hubapi.com/crm/v3/objects/line_items/batch/read' => Http::response([
            'results' => [
                ['id' => 'li-1001', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '2']],
                ['id' => 'li-1002', 'properties' => ['hs_sku' => 'TV-001', 'quantity' => '1']],
            ],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    expect(fn (): array => $reader->read('500005'))
        ->toThrow(LineItemDataInvalidException::class, 'duplicates SKU');
});

it('throws a stable failure when the crm read fails', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*' => Http::response([], 500),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    expect(fn (): array => $reader->read('500005'))
        ->toThrow(HubSpotCrmReadException::class, 'deal read failed');
});

it('throws a stable failure when the tenant has no crm token', function (): void {
    config(['hubspot.crm.service_keys' => []]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    expect(fn (): array => $reader->read('500005'))
        ->toThrow(HubSpotCrmNotConfiguredException::class, 'No HubSpot CRM connection');
});

it('returns an empty normalized list when a deal has no line items', function (): void {
    Http::fake([
        'api.hubapi.com/crm/v3/objects/deals/500005?properties=*'             => Http::response(['id' => '500005']),
        'api.hubapi.com/crm/v3/objects/deals/500005/associations/line_items*' => Http::response([
            'results' => [],
        ]),
    ]);

    $reader = new DealLineItemReader(
        new HubSpotCrmClient('tenant-test'),
        new LineItemNormalizer,
    );

    expect($reader->read('500005'))->toBe([]);
});
