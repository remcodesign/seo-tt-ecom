<?php

declare(strict_types=1);

namespace App\Enums\HubSpot;

enum DealLineItemReaderStage: string
{
    case DealRead = 'deal_read';
    case LineItemsRead = 'line_items_read';
    case LineItemsNormalized = 'line_items_normalized';
}
