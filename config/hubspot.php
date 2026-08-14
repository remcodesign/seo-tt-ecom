<?php

return [
    'client_secret'  => env('HUBSPOT_CLIENT_SECRET'),
    'test_mode'      => (bool) env('HUBSPOT_TEST_MODE', true),
    'portal_tenants' => json_decode((string) env('HUBSPOT_PORTAL_TENANTS', '{}'), true) ?: [],
    'ai'             => [
        'timeout'       => (int) env('HUBSPOT_AI_TIMEOUT', 15),
        'smart_timeout' => (int) env('HUBSPOT_AI_SMART_TIMEOUT', 60),
    ],
    'crm' => [
        'base_url' => env('HUBSPOT_CRM_BASE_URL', 'https://api.hubapi.com'),
        'timeout'  => (int) env('HUBSPOT_CRM_TIMEOUT', 15),
        'retry'    => [
            'times'    => (int) env('HUBSPOT_CRM_RETRY_TIMES', 3),
            'sleep_ms' => (int) env('HUBSPOT_CRM_RETRY_SLEEP_MS', 100),
        ],
        'batch_size' => (int) env('HUBSPOT_CRM_BATCH_SIZE', 100),
        'properties' => [
            'sku'      => env('HUBSPOT_CRM_LINE_ITEM_SKU_PROPERTY', 'hs_sku'),
            'quantity' => env('HUBSPOT_CRM_LINE_ITEM_QUANTITY_PROPERTY', 'quantity'),
        ],
        // Maps a Laravel tenant_id to its authorized HubSpot CRM access token.
        // Tokens must be tenant-scoped and encrypted in production.
        'tenants' => json_decode((string) env('HUBSPOT_CRM_TENANT_TOKENS', '{}'), true) ?: [],
    ],
];
