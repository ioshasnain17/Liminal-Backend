<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'stripe' => [
        'model' => App\Models\User::class,
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
    ],
    'paypal' => [
        'client_id' => 'AXzEesUOqoN2GTmqRRKGBEwIrbzvd2DpMAOR4liEJyCP5Nv04EIQxLtLr5Mdb2Dd5-Oc-pr0Vs12aLnw',
        'secret' => 'EEFIG7dKoZtJL964UZKrKi3kGYd6QCaZ6q3b30lDpCedLTT0nO049VabFIgfbac2epK1eNxd0FBGdqll',
        'settings' => [
            'mode' => env('PAYPAL_MODE', 'sandbox'),
            // Add other PayPal SDK settings here as needed
        ],
    ],
];
