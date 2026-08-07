import type { QuoteApi } from '../types/quote.js';
import type { HubSpotFetch } from '../types/hubspot-fetch.js';
import {
    isCustomerCheckResult,
    isQuotePitchResult,
} from '../validation/quote-responses.js';

const REQUEST_TIMEOUT = 15000;

export function createHubSpotQuoteApi(
    fetcher: HubSpotFetch,
    baseUrl: string,
): QuoteApi {
    async function post<T>(
        path: string,
        body: Record<string, unknown>,
        isValid: (value: unknown) => value is T,
        responseName: string,
    ): Promise<T> {
        const response = await fetcher(`${baseUrl}/${path}`, {
            method: 'POST',
            timeout: REQUEST_TIMEOUT,
            body,
        });

        if (!response.ok) {
            throw new Error(`HubSpot request failed with ${response.status}`);
        }

        const result: unknown = await response.json();

        if (!isValid(result)) {
            throw new Error(`Invalid ${responseName} response`);
        }

        return result;
    }

    return {
        checkCustomer: (email) =>
            post(
                'customer-check',
                { email },
                isCustomerCheckResult,
                'customer check',
            ),
        generatePitch: (input) =>
            post('quote-pitch', { ...input }, isQuotePitchResult, 'quote pitch'),
    };
}
