<?php

declare(strict_types=1);

use App\Services\HubSpot\CustomerCheckService;

describe('CustomerCheckService', function (): void {
    it('recognizes the VIP test customer after trimming and normalizing the email', function (): void {
        expect(app(CustomerCheckService::class)->check('  VIP@REMCODESIGN.NL  '))->toBe([
            'is_vip' => true,
            'lifetime_value' => 4500,
            'allowed_discount' => 15,
            'reason' => 'Returning test customer',
            'source' => 'hubspot test rules',
        ]);
    });

    it('returns the default customer rules for an unknown email', function (): void {
        expect(app(CustomerCheckService::class)->check('unknown@example.test'))->toBe([
            'is_vip' => false,
            'lifetime_value' => 0,
            'allowed_discount' => 5,
            'reason' => 'Unknown test customer',
            'source' => 'hubspot test rules',
        ]);
    });
});
