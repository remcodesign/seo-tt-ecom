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
