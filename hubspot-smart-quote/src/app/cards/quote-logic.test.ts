import { describe, expect, it } from 'vitest';

import {
    getDealAmount,
    getFallbackPitch,
    getFirstContactEmail,
} from './quote-logic.js';

describe('quote logic', () => {
    it('resolves the first associated contact from HubSpot results', () => {
        expect(
            getFirstContactEmail({
                results: [{ properties: { email: 'vip@example.test' } }],
            }),
        ).toBe('vip@example.test');
    });

    it('supports the direct association shape used by mocks', () => {
        expect(getFirstContactEmail([{ email: 'unknown@example.test' }])).toBe(
            'unknown@example.test',
        );
    });

    it('returns no email when a Deal has no contact association', () => {
        expect(getFirstContactEmail({ results: [] })).toBeNull();
    });

    it('normalizes a CRM amount to a number', () => {
        expect(getDealAmount({ amount: '12000' })).toBe(12000);
        expect(getDealAmount({ amount: 'not-a-number' })).toBeNull();
    });

    it('builds a deterministic fallback pitch', () => {
        expect(
            getFallbackPitch({
                deal_name: 'VIP Website Renewal',
                deal_amount: 12000,
                customer_email: 'vip@example.test',
                allowed_discount: 15,
            }),
        ).toEqual({
            text: 'For VIP Website Renewal, we can prepare a tailored proposal with up to 15% flexibility for this customer.',
            provider: 'fallback',
            generated: false,
        });
    });
});