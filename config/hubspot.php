<?php

return [
    'client_id'     => env('HUBSPOT_CLIENT_ID'),
    'client_secret' => env('HUBSPOT_CLIENT_SECRET'),
    'oauth'         => [
        'base_url'     => env('HUBSPOT_OAUTH_BASE_URL', 'https://api.hubapi.com'),
        'redirect_uri' => env('HUBSPOT_OAUTH_REDIRECT_URI'),
    ],
    'test_mode'       => (bool) env('HUBSPOT_TEST_MODE', true),
    'portal_tenants'  => json_decode((string) env('HUBSPOT_PORTAL_TENANTS', '{}'), true) ?: [],
    'request_logging' => [
        'enabled' => (bool) env('HUBSPOT_REQUEST_LOGGING', false),
    ],
    'ai' => [
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
        // Maps a Laravel tenant_id to its HubSpot Service Key.
        // Service Keys must be tenant-scoped and stored as deployment secrets.
        'service_keys' => json_decode((string) env('HUBSPOT_SERVICE_KEYS', '{}'), true) ?: [],
    ],
    'callback' => [
        'base_url'            => env('HUBSPOT_CALLBACK_BASE_URL', 'https://api.hubapi.com'),
        'api_version'         => env('HUBSPOT_CALLBACK_API_VERSION', '2026-03'),
        'refresh_tokens'      => json_decode((string) env('HUBSPOT_CALLBACK_REFRESH_TOKENS', '{}'), true) ?: [],
        'refresh_tokens_file' => env('HUBSPOT_CALLBACK_REFRESH_TOKENS_FILE'),
        'timeout'             => (int) env('HUBSPOT_CALLBACK_TIMEOUT', 10),
    ],
    'notes' => [
        'association_type_id' => (int) env('HUBSPOT_NOTE_DEAL_ASSOCIATION_TYPE_ID', 214),
    ],
];
