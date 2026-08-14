<?php

return [
    'default' => 'openrouter',

    'providers' => [
        'openrouter' => [
            'driver' => 'openrouter',
            'key' => env('OPENROUTER_API_KEY'),
            'models' => [
                'text' => [
                    'default' => env('OPENROUTER_MODEL', 'qwen/qwen-2.5-7b-instruct'),
                    'smart' => env('OPENROUTER_SMART_MODELS', 'qwen/qwen3.7-flash'),
                ],
            ],
        ],
    ],
];
