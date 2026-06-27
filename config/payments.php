<?php

return [

    'gateways' => [
        'credit_card' => [
            'env_prefix' => 'CREDIT_CARD',
            'api_key' => env('CREDIT_CARD_API_KEY'),
            'secret' => env('CREDIT_CARD_SECRET'),
        ],
        'paypal' => [
            'env_prefix' => 'PAYPAL',
            'api_key' => env('PAYPAL_CLIENT_ID'),
            'secret' => env('PAYPAL_CLIENT_SECRET'),
        ],
        'stripe' => [
            'env_prefix' => 'STRIPE',
            'api_key' => env('STRIPE_PUBLIC_KEY'),
            'secret' => env('STRIPE_SECRET_KEY'),
        ],
        'bank_transfer' => [
            'env_prefix' => 'BANK_TRANSFER',
            'api_key' => env('BANK_TRANSFER_API_KEY'),
            'secret' => env('BANK_TRANSFER_SECRET'),
        ],
    ],

];
