export interface DealProperties {
    dealname?: string | null;
    amount?: string | number | null;
    hs_object_id?: string | null;
}

export interface ContactAssociation {
    properties?: {
        email?: string | null;
    };
    email?: string | null;
}

export interface CustomerCheckResult {
    is_vip: boolean;
    lifetime_value: number;
    allowed_discount: number;
    reason: string;
    source: string;
}

export interface QuotePitchInput {
    deal_name: string;
    deal_amount: number | null;
    customer_email: string;
    allowed_discount: number;
}

export interface QuotePitchResult {
    text: string;
    provider: string;
    generated: boolean;
}

export function getFirstContactEmail(input: unknown): string | null {
    if (Array.isArray(input)) {
        return getFirstContactEmail(input[0]);
    }

    if (!input || typeof input !== 'object') {
        return null;
    }

    const association = input as ContactAssociation & {
        results?: ContactAssociation[];
        data?: ContactAssociation[];
    };

    if (association.results) {
        return getFirstContactEmail(association.results);
    }

    if (association.data) {
        return getFirstContactEmail(association.data);
    }

    return association.properties?.email ?? association.email ?? null;
}

export function getDealAmount(deal: DealProperties): number | null {
    if (deal.amount === null || deal.amount === undefined || deal.amount === '') {
        return null;
    }

    const amount = Number(deal.amount);

    return Number.isFinite(amount) ? amount : null;
}

export function getFallbackPitch(input: QuotePitchInput): QuotePitchResult {
    return {
        text: `For ${input.deal_name}, we can prepare a tailored proposal with up to ${input.allowed_discount}% flexibility for this customer.`,
        provider: 'fallback',
        generated: false,
    };
}