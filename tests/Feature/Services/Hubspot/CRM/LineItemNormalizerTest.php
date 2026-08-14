<?php

declare(strict_types=1);

use App\Data\HubSpot\Data\HubSpotLineItemData;
use App\Exceptions\HubSpot\LineItemDataInvalidException;
use App\Services\HubSpot\CRM\LineItemNormalizer;

it('throws when a line item exceeds the maximum quantity', function (): void {
    $normalizer = new LineItemNormalizer(maxQuantity: 5);
    $lineItem = new HubSpotLineItemData(
        line_item_id: 'li-1001',
        properties: [
            'hs_sku'   => 'TV-001',
            'quantity' => '6',
        ],
    );

    expect(fn (): array => $normalizer->normalize([$lineItem]))
        ->toThrow(LineItemDataInvalidException::class, 'exceeds the maximum quantity');
});
