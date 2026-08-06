import { describe, expect, it } from 'vitest';

import {
    createHubSpotQuoteApi,
    createMockQuoteApi,
} from './quote-client.js';

describe('mock quote API', () => {
    it('returns the VIP fixture without making a network request', async () => {
        const api = createMockQuoteApi();

        await expect(api.checkCustomer('vip@example.test')).resolves.toMatchObject({
            is_vip: true,
            lifetime_value: 4500,
            allowed_discount: 15,
        });
    });

    it('returns the default rule for an unknown customer', async () => {
        const api = createMockQuoteApi();

        await expect(api.checkCustomer('unknown@example.test')).resolves.toMatchObject({
            is_vip: false,
            allowed_discount: 5,
        });
    });

    it('returns the deterministic fallback pitch', async () => {
        const api = createMockQuoteApi();

        await expect(
            api.generatePitch({
                deal_name: 'VIP Website Renewal',
                deal_amount: 12000,
                customer_email: 'vip@example.test',
                allowed_discount: 15,
            }),
        ).resolves.toMatchObject({ provider: 'fallback', generated: false });
    });
});

describe('HubSpot quote API', () => {
    it('posts the customer check through the permitted fetch adapter', async () => {
        const fetcher = async (
            _resource: string,
            options: { body: Record<string, unknown> },
        ) => ({
            ok: true,
            status: 200,
            json: async () => ({
                is_vip: true,
                lifetime_value: 4500,
                allowed_discount: 15,
                reason: 'Returning test customer',
                source: 'Laravel',
            }),
            receivedBody: options.body,
        });
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(api.checkCustomer('vip@example.test')).resolves.toMatchObject({
            allowed_discount: 15,
        });
    });

    it('surfaces non-success responses to the card', async () => {
        const fetcher = async () => ({
            ok: false,
            status: 403,
            json: async () => ({}),
        });
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(api.checkCustomer('vip@example.test')).rejects.toThrow(
            'HubSpot request failed with 403',
        );
    });
});