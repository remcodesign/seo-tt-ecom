<?php

declare(strict_types=1);

namespace App\Services\HubSpot;

use Illuminate\Support\Facades\Log;

final class CustomerCheckService
{
    /**
     * @return array{is_vip: bool, lifetime_value: int, allowed_discount: int, reason: string, source: string}
     */
    public function check(string $email): array
    {
        // todo add DTO response object, also cleaner with PHPStan
        // TODO - This is a stub implementation for testing purposes. In a real implementation, you would query HubSpot or your database to determine if the customer is VIP and retrieve their lifetime value and allowed discount.
        $isVip = mb_strtolower(trim($email)) === 'vip@remcodesign.nl';

        $result = [
            'is_vip'           => $isVip,
            'lifetime_value'   => $isVip ? 4500 : 0,
            'allowed_discount' => $isVip ? 15 : 5,
            'reason'           => $isVip ? 'Returning test customer' : 'Unknown test customer',
            'source'           => 'hubspot test rules',
        ];

        Log::channel('hubspot')->info('Customer check completed.', [
            'email'            => $email,
            'is_vip'           => $result['is_vip'],
            'allowed_discount' => $result['allowed_discount'],
        ]);

        return $result;
    }
}
