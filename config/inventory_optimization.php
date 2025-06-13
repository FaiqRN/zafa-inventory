<?php

// config/inventory_optimization.php
return [
    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'recommendations_ttl' => 3600, // 1 hour
        'seasonal_ttl' => 86400, // 24 hours
        'turnover_stats_ttl' => 1800, // 30 minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | Calculation Settings
    |--------------------------------------------------------------------------
    */
    'calculation' => [
        'default_cost_per_unit' => 15000,
        'confidence_thresholds' => [
            'high' => 12, // 12+ data points
            'medium' => 6, // 6+ data points
            'low' => 3, // 3+ data points
        ],
        'seasonal_bounds' => [
            'min_multiplier' => 0.5,
            'max_multiplier' => 2.0,
        ],
        'trend_bounds' => [
            'min_multiplier' => 0.7,
            'max_multiplier' => 1.4,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'batch_size' => 100, // Process recommendations in batches
        'max_recommendations' => 1000, // Maximum recommendations to generate
        'parallel_processing' => false, // Enable if using queue
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */
    'notifications' => [
        'enable_email' => false,
        'enable_slack' => false,
        'high_impact_threshold' => 1000000, // Rp 1M
    ],

];