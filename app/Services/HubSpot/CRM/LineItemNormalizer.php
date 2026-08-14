<?php

declare(strict_types=1);

namespace App\Services\HubSpot\CRM;

use App\Data\HubSpot\Data\HubSpotLineItemData;
use App\Data\HubSpot\Data\NormalizedLineItemData;
use App\Exceptions\HubSpot\LineItemDataInvalidException;

/**
 * Normalizes raw HubSpot Line Item records into bounded, validated Line Items.
 * Owns the SKU/quantity property names and validation rules. It never asks the
 * AI to repair CRM data and never invents a SKU or quantity.
 */
final readonly class LineItemNormalizer
{
    public function __construct(
        private int $maxQuantity = 1000,
    ) {}

    /**
     * @param  list<HubSpotLineItemData>  $lineItems
     * @return list<NormalizedLineItemData>
     */
    public function normalize(array $lineItems): array
    {
        $skuProperty = $this->skuProperty();
        $quantityProperty = $this->quantityProperty();
        $seen = [];

        $normalized = [];

        foreach ($lineItems as $lineItem) {
            $sku = $lineItem->properties[$skuProperty] ?? null;
            $quantity = $lineItem->properties[$quantityProperty] ?? null;

            if (! is_string($sku) || trim($sku) === '') {
                throw new LineItemDataInvalidException(
                    sprintf('Line item [%s] is missing a SKU.', $lineItem->line_item_id),
                );
            }

            $sku = trim($sku);

            if (isset($seen[$sku])) {
                throw new LineItemDataInvalidException(
                    sprintf('Line item [%s] duplicates SKU [%s].', $lineItem->line_item_id, $sku),
                );
            }

            $seen[$sku] = true;

            if (! is_numeric($quantity) || (int) $quantity < 1) {
                throw new LineItemDataInvalidException(
                    sprintf('Line item [%s] has an invalid quantity.', $lineItem->line_item_id),
                );
            }

            $quantity = (int) $quantity;

            if ($quantity > $this->maxQuantity) {
                throw new LineItemDataInvalidException(
                    sprintf('Line item [%s] exceeds the maximum quantity.', $lineItem->line_item_id),
                );
            }

            $normalized[] = new NormalizedLineItemData(
                line_item_id: $lineItem->line_item_id,
                sku: $sku,
                quantity: $quantity,
            );
        }

        return $normalized;
    }

    private function skuProperty(): string
    {
        $property = config('hubspot.crm.properties.sku', 'hs_sku');

        return is_string($property) && $property !== '' ? $property : 'hs_sku';
    }

    private function quantityProperty(): string
    {
        $property = config('hubspot.crm.properties.quantity', 'quantity');

        return is_string($property) && $property !== '' ? $property : 'quantity';
    }
}
