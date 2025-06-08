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
    /*
    |--------------------------------------------------------------------------
    | Texas Wablas WhatsApp API Configuration (ENHANCED)
    |--------------------------------------------------------------------------
    |
    | Configuration for Texas Wablas WhatsApp API integration.
    | This is the FIXED configuration for proper WhatsApp broadcast functionality.
    |
    */
    'wablas' => [
        // Core API Configuration
        'api_url' => env('WABLAS_API_URL', 'https://texas.wablas.com/api'),
        'token' => env('WABLAS_TOKEN'),
        'secret_key' => env('WABLAS_SECRET_KEY'),
        'device_id' => env('WABLAS_DEVICE_ID'),
        
        // Timeout and Retry Configuration
        'timeout' => env('WABLAS_TIMEOUT', 60),
        'retry_attempts' => env('WABLAS_RETRY_ATTEMPTS', 3),
        'retry_delay' => env('WABLAS_RETRY_DELAY', 5), // seconds
        
        // Rate Limiting Configuration (CRITICAL for broadcast)
        'message_delay' => env('WABLAS_MESSAGE_DELAY', 3), // seconds between messages
        'batch_delay' => env('WABLAS_BATCH_DELAY', 5), // seconds between batches
        'batch_size' => env('WABLAS_BATCH_SIZE', 5), // messages per batch
        'max_daily_messages' => env('WABLAS_MAX_DAILY_MESSAGES', 1000), // daily limit
        
        // File Upload Configuration
        'max_image_size' => env('WABLAS_MAX_IMAGE_SIZE', 5120), // KB (5MB)
        'allowed_image_types' => ['jpeg', 'jpg', 'png', 'gif'],
        'max_images_per_message' => env('WABLAS_MAX_IMAGES', 5),
        
        // Webhook Configuration
        'webhook_url' => env('WABLAS_WEBHOOK_URL'),
        'webhook_secret' => env('WABLAS_WEBHOOK_SECRET'),
        'webhook_enabled' => env('WABLAS_WEBHOOK_ENABLED', false),
        
        // Advanced Features
        'enable_status_tracking' => env('WABLAS_ENABLE_STATUS_TRACKING', true),
        'enable_delivery_reports' => env('WABLAS_ENABLE_DELIVERY_REPORTS', true),
        'enable_read_receipts' => env('WABLAS_ENABLE_READ_RECEIPTS', true),
        
        // Debug and Logging
        'debug_mode' => env('WABLAS_DEBUG_MODE', false),
        'log_requests' => env('WABLAS_LOG_REQUESTS', true),
        'log_responses' => env('WABLAS_LOG_RESPONSES', false),
        
        // Phone Number Validation
        'phone_validation' => [
            'country_code' => '62', // Indonesia
            'min_length' => 12,
            'max_length' => 15,
            'validate_format' => true,
        ],
        
        // Message Templates (Optional)
        'templates' => [
            'test_message' => 'Test pesan dari Zafa Potato CRM - {timestamp}',
            'welcome_message' => 'Selamat datang di Zafa Potato! Terima kasih telah bergabung dengan kami.',
            'follow_up_template' => 'Halo {name}, terima kasih sudah menjadi pelanggan setia Zafa Potato!',
            'promo_template' => 'Halo {name}! Ada promo spesial untuk Anda: {promo_details}',
        ],
        
        // API Endpoints (Texas Wablas v2)
        'endpoints' => [
            'send_message' => '/v2/send-message',
            'send_image' => '/v2/send-image',
            'send_document' => '/v2/send-document',
            'device_info' => '/device/info',
            'device_scan' => '/device/scan',
            'message_status' => '/report/message',
            'webhook_register' => '/webhook/register',
        ],
        
        // Error Handling
        'error_handling' => [
            'retry_on_rate_limit' => true,
            'retry_on_device_disconnected' => false,
            'fail_on_invalid_phone' => true,
            'continue_on_partial_failure' => true,
        ],
        
        // Performance Settings
        'performance' => [
            'use_connection_pooling' => true,
            'connection_timeout' => 30,
            'read_timeout' => 60,
            'max_concurrent_requests' => 10,
        ],
        
        // Monitoring and Analytics
        'monitoring' => [
            'track_send_rates' => true,
            'track_success_rates' => true,
            'track_response_times' => true,
            'alert_on_high_failure_rate' => true,
            'failure_rate_threshold' => 20, // percent
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Additional Configuration Helpers
    |--------------------------------------------------------------------------
    */
    
    // Admin Configuration
    'admin' => [
        'phone' => env('APP_ADMIN_PHONE', '6282245454528'),
        'email' => env('APP_ADMIN_EMAIL', 'admin@zafapotato.com'),
        'notifications' => [
            'device_disconnected' => true,
            'high_failure_rate' => true,
            'daily_summary' => true,
        ],
    ],
    
    // Queue Configuration for WhatsApp
    'queue' => [
        'whatsapp_queue' => env('WHATSAPP_QUEUE_NAME', 'whatsapp'),
        'default_delay' => env('WHATSAPP_QUEUE_DELAY', 0),
        'max_retries' => env('WHATSAPP_QUEUE_RETRIES', 3),
        'retry_delay' => env('WHATSAPP_QUEUE_RETRY_DELAY', 300), // 5 minutes
    ],
    
    // Cache Configuration
    'cache' => [
        'device_status_ttl' => env('CACHE_DEVICE_STATUS_TTL', 300), // 5 minutes
        'customer_data_ttl' => env('CACHE_CUSTOMER_DATA_TTL', 1800), // 30 minutes
        'message_templates_ttl' => env('CACHE_MESSAGE_TEMPLATES_TTL', 3600), // 1 hour
    ],
];
