<?php

/*
 | Commerce domain configuration. Payment provider is resolved via the PaymentGateway
 | abstraction — code never couples to Stripe directly.
 */
return [
    'default_currency' => env('COMMERCE_DEFAULT_CURRENCY', 'SAR'),
    'supported_currencies' => ['SAR', 'USD', 'EGP'],

    'payment' => [
        'provider' => env('COMMERCE_PAYMENT_PROVIDER', 'fake'), // fake | stripe
        // Fake webhook HMAC secret (local/test only). Stripe uses services.stripe.webhook_secret.
        'webhook_secret' => env('COMMERCE_WEBHOOK_SECRET', 'whsec_fake'),
    ],

    'invoice' => [
        'prefix' => env('COMMERCE_INVOICE_PREFIX', 'INV'),
    ],

    'contract' => [
        // Order fulfillment requires acceptance of this contract template key.
        'required_key' => 'terms',
    ],
];
