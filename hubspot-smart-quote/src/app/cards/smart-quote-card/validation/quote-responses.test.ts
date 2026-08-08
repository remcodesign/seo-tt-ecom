import { describe, expect, it } from 'vitest';

import {
    isCustomerCheckResult,
    isQuotePitchResult,
} from './quote-responses.js';

describe('isCustomerCheckResult', () => {
    it('accepts a complete customer check response', () => {
        expect(
            isCustomerCheckResult({
                is_vip: true,
                lifetime_value: 4500,
                allowed_discount: 15,
                reason: 'Returning customer',
                source: 'Laravel',
            }),
        ).toBe(true);
    });

    it('accepts zero-valued numeric fields', () => {
        expect(
            isCustomerCheckResult({
                is_vip: false,
                lifetime_value: 0,
                allowed_discount: 0,
                reason: 'No discount rule',
                source: 'Laravel',
            }),
        ).toBe(true);
    });

    it.each([
        null,
        undefined,
        'not an object',
        { is_vip: 'yes' },
        { is_vip: false, lifetime_value: Number.NaN },
        { is_vip: false, lifetime_value: Number.POSITIVE_INFINITY },
        { is_vip: false, lifetime_value: 100, allowed_discount: '5' },
        {
            is_vip: false,
            lifetime_value: 100,
            allowed_discount: 5,
            reason: '   ',
            source: 'Laravel',
        },
        {
            is_vip: false,
            lifetime_value: 100,
            allowed_discount: 5,
            reason: 'Valid reason',
            source: '',
        },
    ])('rejects malformed response %#', (value) => {
        expect(isCustomerCheckResult(value)).toBe(false);
    });
});

describe('isQuotePitchResult', () => {
    it('accepts a complete quote pitch response', () => {
        expect(
            isQuotePitchResult({
                text: 'A tailored proposal is ready.',
                provider: 'fallback',
                generated: false,
            }),
        ).toBe(true);
    });

    it.each([
        null,
        undefined,
        { text: '' },
        { text: '   ', provider: 'fallback', generated: false },
        { text: 'Proposal', provider: '', generated: false },
        { text: 'Proposal', provider: 'fallback', generated: 'no' },
        { text: 'Proposal', provider: 'fallback' },
    ])('rejects malformed response %#', (value) => {
        expect(isQuotePitchResult(value)).toBe(false);
    });
});
