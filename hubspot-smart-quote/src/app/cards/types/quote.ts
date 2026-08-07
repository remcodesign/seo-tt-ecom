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

export interface QuoteApi {
    checkCustomer: (email: string) => Promise<CustomerCheckResult>;
    generatePitch: (input: QuotePitchInput) => Promise<QuotePitchResult>;
}
