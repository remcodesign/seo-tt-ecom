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
