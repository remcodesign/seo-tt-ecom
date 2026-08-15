<?php

declare(strict_types=1);

namespace App\Services\HubSpot\CRM;

use App\Data\HubSpot\Data\NormalizedLineItemData;
use App\Enums\HubSpot\DealLineItemReaderStage;

/**
 * Orchestrates the deterministic two-stage CRM retrieval for a Deal:
 * 1. read the Deal and its associated Line Item IDs;
 * 2. batch-read the Line Items requesting only the configured SKU/quantity
 *    properties, then normalize them.
 *
 * The client owns transport; this service owns the read strategy and hands the
 * normalized result to the worker. No cross-tenant read is possible because the
 * client is constructed with a single tenant id.
 */
final readonly class DealLineItemReader
{
    public function __construct(
        private HubSpotCrmClient $hubSpotCrmClient,
        private LineItemNormalizer $lineItemNormalizer,
    ) {}

    /**
     * @return list<NormalizedLineItemData>
     */
    public function read(string $dealId, ?callable $onStage = null): array
    {
        $hubSpotDealData = $this->hubSpotCrmClient->readDeal($dealId, ['hs_object_id', 'dealname']);
        if ($onStage !== null) {
            $onStage(DealLineItemReaderStage::DealRead, $hubSpotDealData);
        }

        $lineItems = $this->hubSpotCrmClient->readLineItems(
            $hubSpotDealData->line_item_ids,
            $this->requestedProperties(),
        );

        if ($onStage !== null) {
            $onStage(DealLineItemReaderStage::LineItemsRead, $lineItems);
        }

        $normalizedLineItems = $this->lineItemNormalizer->normalize($lineItems);
        if ($onStage !== null) {
            $onStage(DealLineItemReaderStage::LineItemsNormalized, $normalizedLineItems);
        }

        return $normalizedLineItems;
    }

    /**
     * @return list<string>
     */
    private function requestedProperties(): array
    {
        $sku = config('hubspot.crm.properties.sku', 'hs_sku');
        $quantity = config('hubspot.crm.properties.quantity', 'quantity');

        return array_values(array_unique(array_filter([
            is_string($sku) ? $sku : 'hs_sku',
            is_string($quantity) ? $quantity : 'quantity',
        ], static fn (string $property): bool => $property !== '')));
    }
}
