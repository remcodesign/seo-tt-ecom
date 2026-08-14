<?php

return [
    'client_secret' => env('HUBSPOT_CLIENT_SECRET'),
    'test_mode' => (bool) env('HUBSPOT_TEST_MODE', true),
    'portal_tenants' => json_decode((string) env('HUBSPOT_PORTAL_TENANTS', '{}'), true) ?: [],
    'ai' => [
        'timeout' => (int) env('HUBSPOT_AI_TIMEOUT', 15),
        'smart_timeout' => (int) env('HUBSPOT_AI_SMART_TIMEOUT', 60),
    ],
];
