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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],


        /*
    |--------------------------------------------------------------------------
    | Geocoding Services
    |--------------------------------------------------------------------------
    |
    | API keys untuk berbagai provider geocoding.
    | Nominatim (OpenStreetMap) gratis, yang lain perlu registrasi.
    |
    */

    'locationiq' => [
        'key' => env('LOCATIONIQ_API_KEY'),
    ],

    'opencage' => [
        'key' => env('OPENCAGE_API_KEY'),
    ],

    'mapbox' => [
        'key' => env('MAPBOX_API_KEY'),
    ],
        'wablas' => [
        'api_url' => env('WABLAS_API_URL', 'https://console.wablas.com/api'),
        'token' => env('WABLAS_TOKEN'),
        'device_id' => env('WABLAS_DEVICE_ID'),
        'timeout' => env('WABLAS_TIMEOUT', 30),
        'retry_attempts' => env('WABLAS_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WABLAS_RETRY_DELAY', 5), // seconds
    ],
];
