<?php

/*
 | Third-party service credentials. All keys read from env; defaults are empty/fake so nothing
 | contacts a real vendor unless explicitly configured. Concrete provider adapters are the ONLY
 | code that reads these — Actions/Controllers never touch vendor SDKs or secrets.
 */
return [

    'stripe' => [
        'base_url' => env('STRIPE_BASE_URL', 'https://api.stripe.com'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        // Reject webhook timestamps older than this many seconds (replay protection).
        'webhook_tolerance' => (int) env('STRIPE_WEBHOOK_TOLERANCE', 300),
    ],

    'mux' => [
        // Signing key id + base64-encoded PEM private key from the Mux dashboard.
        'signing_key_id' => env('MUX_SIGNING_KEY_ID'),
        'signing_key' => env('MUX_SIGNING_KEY'),
        'stream_base_url' => env('MUX_STREAM_BASE_URL', 'https://stream.mux.com'),
        'audience' => env('MUX_TOKEN_AUDIENCE', 'v'), // v=video, t=thumbnail, s=storyboard
    ],

    'cloudfront' => [
        'url' => env('CLOUDFRONT_URL'),
        'key_pair_id' => env('CLOUDFRONT_KEY_PAIR_ID'),
        // Base64-encoded PEM private key (RSA) matching the CloudFront key pair.
        'private_key' => env('CLOUDFRONT_PRIVATE_KEY'),
    ],

    'mailgun' => [
        'base_url' => env('MAILGUN_BASE_URL', 'https://api.mailgun.net'),
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'from' => env('MAILGUN_FROM', env('MAIL_FROM_ADDRESS', 'no-reply@helbaron.test')),
    ],

    'twilio' => [
        'base_url' => env('TWILIO_BASE_URL', 'https://api.twilio.com'),
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'from' => env('TWILIO_FROM'),
    ],

    'firebase' => [
        // Legacy HTTP server key (simplest testable seam). See blockers for FCM HTTP v1.
        'base_url' => env('FIREBASE_BASE_URL', 'https://fcm.googleapis.com'),
        'server_key' => env('FIREBASE_SERVER_KEY'),
    ],

];
