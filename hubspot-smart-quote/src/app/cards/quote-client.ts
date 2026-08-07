import type {
    CustomerCheckResult,
    QuotePitchInput,
    QuotePitchResult,
} from './quote-types.js';

export interface HubSpotFetchOptions {
    method: 'POST';
    timeout: number;
    body: Record<string, unknown>;
}

export interface HubSpotFetchResponse {
    ok: boolean;
    status: number;
    json: () => Promise<unknown>;
}

export type HubSpotFetch = (
    resource: string,
    options: HubSpotFetchOptions,
) => Promise<HubSpotFetchResponse>;

export interface QuoteApi {
    checkCustomer: (email: string) => Promise<CustomerCheckResult>;
    generatePitch: (input: QuotePitchInput) => Promise<QuotePitchResult>;
}

const REQUEST_TIMEOUT = 15000;

export function createHubSpotQuoteApi(
    fetcher: HubSpotFetch,
    baseUrl: string,
): QuoteApi {
    async function post<T>(
        path: string,
        body: Record<string, unknown>,
    ): Promise<T> {
        const response = await fetcher(`${baseUrl}/${path}`, {
            method: 'POST',
            timeout: REQUEST_TIMEOUT,
            body,
        });

        if (!response.ok) {
            throw new Error(`HubSpot request failed with ${response.status}`);
        }

        return (await response.json()) as T;
    }

    return {
        checkCustomer: (email) =>
            post<CustomerCheckResult>('customer-check', { email }),
        generatePitch: (input) =>
            post<QuotePitchResult>('quote-pitch', { ...input }),
    };
}