<?php

return [
    'default' => 'openrouter',

    'providers' => [
        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
            'models' => [
                'text' => [
                    'default' => env('OPENROUTER_MODEL', 'qwen/qwen3.7-flash'),
                ],
            ],
        ],
    ],
];
