import type { ContactAssociation, DealProperties } from '../types/crm.js';

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
