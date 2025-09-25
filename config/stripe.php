<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Stripe Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Stripe payment processing. These values should be
    | set in your .env file for security.
    |
    */

    'secret_key' => env('STRIPE_SECRET_KEY'),
    'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'aud'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | Stripe API version to use. This ensures consistent behavior.
    |
    */

    'api_version' => '2023-10-16',

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for processing Stripe webhooks.
    |
    */

    'webhook' => [
        'tolerance' => 300, // 5 minutes tolerance for webhook timestamp
        'events' => [
            'payment_intent.succeeded',
            'payment_intent.payment_failed',
            'payment_intent.canceled',
            'payment_intent.requires_action',
            'payment_intent.processing',
            'payment_method.attached',
            'invoice.payment_succeeded',
            'invoice.payment_failed',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Mode Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for test mode behavior.
    |
    */

    'test_mode' => env('STRIPE_TEST_MODE', true),

    /*
    |--------------------------------------------------------------------------
    | Connection Settings
    |--------------------------------------------------------------------------
    |
    | Timeouts and retry settings for Stripe API calls.
    |
    */

    'connect_timeout' => 30,
    'read_timeout' => 60,
    'max_network_retries' => 3,

    // Legacy compatibility
    'secret' => env('STRIPE_SECRET_KEY'),
    'public' => env('STRIPE_PUBLISHABLE_KEY'),
];