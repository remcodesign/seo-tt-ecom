<?php

declare(strict_types=1);

namespace App\Enums\HubSpot;

enum WarehouseRecommendationTaskStatus: string
{
    case accepted = 'accepted';
    case processing = 'processing';
    case succeeded = 'succeeded';
    case failed = 'failed';
    case expired = 'expired';
}
