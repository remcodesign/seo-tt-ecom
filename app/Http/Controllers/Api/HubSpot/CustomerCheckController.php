<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\HubSpot;

use App\Data\HubSpot\Requests\CustomerCheckData;
use App\Services\HubSpot\CustomerCheckService;
use Illuminate\Http\JsonResponse;

final readonly class CustomerCheckController
{
    public function __construct(
        private CustomerCheckService $customerCheckService,
    ) {}

    public function __invoke(CustomerCheckData $customerCheckData): JsonResponse
    {
        return response()->json($this->customerCheckService->check($customerCheckData->email));
    }
}
