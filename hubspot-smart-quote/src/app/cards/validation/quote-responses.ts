import type {
    CustomerCheckResult,
    QuotePitchResult,
} from '../types/quote.js';

export function isCustomerCheckResult(
    value: unknown,
): value is CustomerCheckResult {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const result = value as Record<string, unknown>;

    return (
        typeof result.is_vip === 'boolean' &&
        typeof result.lifetime_value === 'number' &&
        Number.isFinite(result.lifetime_value) &&
        typeof result.allowed_discount === 'number' &&
        Number.isFinite(result.allowed_discount) &&
        typeof result.reason === 'string' &&
        result.reason.trim().length > 0 &&
        typeof result.source === 'string' &&
        result.source.trim().length > 0
    );
}

export function isQuotePitchResult(value: unknown): value is QuotePitchResult {
    if (typeof value !== 'object' || value === null) {
        return false;
    }

    const result = value as Record<string, unknown>;

    return (
        typeof result.text === 'string' &&
        result.text.trim().length > 0 &&
        typeof result.provider === 'string' &&
        result.provider.trim().length > 0 &&
        typeof result.generated === 'boolean'
    );
}
