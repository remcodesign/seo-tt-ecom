import { describe, expect, it } from 'vitest';

import type { HubSpotFetch } from '../types/hubspot-fetch.js';
import { createHubSpotQuoteApi } from './quote-api.js';

describe('HubSpot quote API', () => {
    it('posts the customer check through the permitted fetch adapter', async () => {
        let request: {
            resource: string;
            options: Parameters<HubSpotFetch>[1];
        } | null = null;
        const fetcher: HubSpotFetch = async (resource, options) => {
            request = { resource, options };

            return {
                ok: true,
                status: 200,
                json: async () => ({
                    is_vip: true,
                    lifetime_value: 4500,
                    allowed_discount: 15,
                    reason: 'Returning test customer',
                    source: 'Laravel',
                }),
            };
        };
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(api.checkCustomer('vip@example.test')).resolves.toMatchObject({
            allowed_discount: 15,
        });
        expect(request).toEqual({
            resource: 'https://api.example.test/customer-check',
            options: {
                method: 'POST',
                timeout: 15000,
                body: { email: 'vip@example.test' },
            },
        });
    });

    it('posts the quote pitch with the complete input payload', async () => {
        let request: {
            resource: string;
            options: Parameters<HubSpotFetch>[1];
        } | null = null;
        const fetcher: HubSpotFetch = async (resource, options) => {
            request = { resource, options };

            return {
                ok: true,
                status: 200,
                json: async () => ({
                    text: 'A tailored proposal is ready.',
                    provider: 'fallback',
                    generated: false,
                }),
            };
        };
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(
            api.generatePitch({
                deal_name: 'VIP Website Renewal',
                deal_amount: 12000,
                customer_email: 'vip@example.test',
                allowed_discount: 15,
            }),
        ).resolves.toMatchObject({ generated: false });
        expect(request).toEqual({
            resource: 'https://api.example.test/quote-pitch',
            options: {
                method: 'POST',
                timeout: 15000,
                body: {
                    deal_name: 'VIP Website Renewal',
                    deal_amount: 12000,
                    customer_email: 'vip@example.test',
                    allowed_discount: 15,
                },
            },
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

    it('surfaces fetcher failures to the card', async () => {
        const fetcher: HubSpotFetch = async () => {
            throw new Error('Network unavailable');
        };
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(api.checkCustomer('vip@example.test')).rejects.toThrow(
            'Network unavailable',
        );
    });

    it('rejects malformed customer check success responses', async () => {
        const fetcher = async () => ({
            ok: true,
            status: 200,
            json: async () => ({
                is_vip: 'yes',
                lifetime_value: 4500,
                allowed_discount: 15,
                reason: 'Returning test customer',
                source: 'Laravel',
            }),
        });
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(api.checkCustomer('vip@example.test')).rejects.toThrow(
            'Invalid customer check response',
        );
    });

    it('rejects malformed quote pitch success responses', async () => {
        const fetcher = async () => ({
            ok: true,
            status: 200,
            json: async () => ({
                text: '',
                provider: 'fallback',
                generated: false,
            }),
        });
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(
            api.generatePitch({
                deal_name: 'VIP Website Renewal',
                deal_amount: 12000,
                customer_email: 'vip@example.test',
                allowed_discount: 15,
            }),
        ).rejects.toThrow('Invalid quote pitch response');
    });

    it('rejects an empty customer check response', async () => {
        const fetcher: HubSpotFetch = async () => ({
            ok: true,
            status: 200,
            json: async () => null,
        });
        const api = createHubSpotQuoteApi(fetcher, 'https://api.example.test');

        await expect(api.checkCustomer('vip@example.test')).rejects.toThrow(
            'Invalid customer check response',
        );
    });
});
