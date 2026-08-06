<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\HubSpot;

use App\Data\HubSpot\Requests\QuotePitchData;
use App\Services\HubSpot\QuotePitchService;
use Illuminate\Http\JsonResponse;

final readonly class QuotePitchController
{
    public function __construct(
        private QuotePitchService $quotePitchService,
    ) {}

    public function __invoke(QuotePitchData $quotePitchData): JsonResponse
    {
        return response()->json($this->quotePitchService->generate(
            dealName: $quotePitchData->deal_name,
            dealAmount: $quotePitchData->deal_amount,
            customerEmail: $quotePitchData->customer_email,
            allowedDiscount: $quotePitchData->allowed_discount,
        ));
    }
}
