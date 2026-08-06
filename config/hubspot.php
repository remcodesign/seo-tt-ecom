<?php

return [
    'client_secret' => env('HUBSPOT_CLIENT_SECRET'),
    'test_mode' => (bool) env('HUBSPOT_TEST_MODE', true),
    'ai' => [
        'timeout' => (int) env('HUBSPOT_AI_TIMEOUT', 15),
    ],
];
