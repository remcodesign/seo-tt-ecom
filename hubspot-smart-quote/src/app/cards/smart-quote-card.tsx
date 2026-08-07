import {
    Button,
    Flex,
    Heading,
    LoadingSpinner,
    Text,
    hubspot,
} from '@hubspot/ui-extensions';
import { useAssociations, useCrmProperties } from '@hubspot/ui-extensions/crm';
import { useState } from 'react';

import { LARAVEL_API_BASE_URL } from './api-config.js';
import type { DealProperties } from './crm-types.js';
import {
    createHubSpotQuoteApi,
    type QuoteApi,
    type HubSpotFetchOptions,
} from './quote-client.js';
import type { CustomerCheckResult, QuotePitchResult } from './quote-types.js';
import {
    getDealAmount,
    getFirstContactEmail,
} from './quote-logic.js';

interface CardActions {
    addAlert: (options: { message: string; type?: string }) => void;
    copyTextToClipboard: (text: string) => Promise<void>;
}

hubspot.extend(({ actions }) => (
    <SmartQuoteCard actions={actions as CardActions} />
));

export function SmartQuoteCard({ actions }: { actions: CardActions }) {
    const dealProperties = useCrmProperties([
        'dealname',
        'amount',
        'hs_object_id',
    ]);
    const associations = useAssociations({
        toObjectType: '0-1',
        properties: ['email'],
        pageLength: 1,
    });
    const [customerResult, setCustomerResult] =
        useState<CustomerCheckResult | null>(null);
    const [pitchResult, setPitchResult] = useState<QuotePitchResult | null>(null);
    const [customerError, setCustomerError] = useState<string | null>(null);
    const [pitchError, setPitchError] = useState<string | null>(null);
    const [isCheckingCustomer, setIsCheckingCustomer] = useState(false);
    const [isGeneratingPitch, setIsGeneratingPitch] = useState(false);

    const deal: DealProperties = dealProperties.properties as DealProperties;
    const contactEmail = getFirstContactEmail(associations);
    const isLoading = dealProperties.isLoading;
    const quoteApi: QuoteApi = createHubSpotQuoteApi(
        (resource: string, options: HubSpotFetchOptions) =>
            hubspot.fetch(resource, options),
        LARAVEL_API_BASE_URL,
    );

    async function handleCustomerCheck(): Promise<void> {
        if (!contactEmail) {
            return;
        }

        setIsCheckingCustomer(true);
        setCustomerError(null);
        setPitchResult(null);
        setPitchError(null);

        try {
            setCustomerResult(await quoteApi.checkCustomer(contactEmail));
        } catch {
            setCustomerError('Customer data could not be loaded.');
        } finally {
            setIsCheckingCustomer(false);
        }
    }

    async function handleGeneratePitch(): Promise<void> {
        if (!customerResult || !contactEmail) {
            return;
        }

        setIsGeneratingPitch(true);
        setPitchError(null);

        try {
            setPitchResult(
                await quoteApi.generatePitch({
                    deal_name: deal.dealname ?? 'Unnamed deal',
                    deal_amount: getDealAmount(deal),
                    customer_email: contactEmail,
                    allowed_discount: customerResult.allowed_discount,
                }),
            );
        } catch {
            setPitchError('Quote text could not be generated.');
        } finally {
            setIsGeneratingPitch(false);
        }
    }

    async function handleCopy(): Promise<void> {
        if (!pitchResult) {
            return;
        }

        try {
            await actions.copyTextToClipboard(pitchResult.text);
            actions.addAlert({ type: 'success', message: 'Quote text copied.' });
        } catch {
            actions.addAlert({
                type: 'warning',
                message: 'Quote text could not be copied.',
            });
        }
    }

    if (isLoading) {
        return <LoadingSpinner label="Loading Deal data" />;
    }

    if (dealProperties.error || !contactEmail) {
        return (
            <Flex direction="column" gap="medium">
                <Heading>Smart Quote</Heading>
                <Text>
                    {dealProperties.error
                        ? 'Deal data could not be loaded.'
                        : 'Associate a contact with this deal first.'}
                </Text>
            </Flex>
        );
    }

    return (
        <Flex direction="column" gap="medium">
            <Flex direction="column" gap="small">
                <Heading>Smart Quote 5</Heading>
                <Text>{deal.dealname ?? 'Unnamed deal'}</Text>
                <Text>
                    Amount: {getDealAmount(deal)?.toLocaleString() ?? 'No amount'}
                </Text>
                <Text>Customer: {contactEmail}</Text>
            </Flex>

            <Button
                disabled={isCheckingCustomer || !contactEmail}
                onClick={handleCustomerCheck}
            >
                {isCheckingCustomer ? 'Checking customer' : 'Check customer'}
            </Button>

            {customerError && <Text>{customerError}</Text>}

            {customerResult && (
                <Flex direction="column" gap="small">
                    <Heading>Customer value</Heading>
                    <Text>VIP: {customerResult.is_vip ? 'Yes' : 'No'}</Text>
                    <Text>
                        Lifetime value: {customerResult.lifetime_value.toLocaleString()}
                    </Text>
                    <Text>
                        Allowed discount: {customerResult.allowed_discount}%
                    </Text>
                    <Text>{customerResult.reason}</Text>
                </Flex>
            )}

            <Button
                disabled={!customerResult || isGeneratingPitch}
                onClick={handleGeneratePitch}
            >
                {isGeneratingPitch ? 'Generating quote text' : 'Generate quote text'}
            </Button>

            {pitchError && <Text>{pitchError}</Text>}

            {pitchResult && (
                <Flex direction="column" gap="small">
                    <Heading>Quote pitch</Heading>
                    <Text>{pitchResult.text}</Text>
                    <Text>Provider: {pitchResult.provider}</Text>
                    <Button onClick={handleCopy}>Copy quote text</Button>
                </Flex>
            )}
        </Flex>
    );
}