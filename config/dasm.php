<?php

return [
    /*
    |--------------------------------------------------------------------------
    | DASMe Platform Integration
    |--------------------------------------------------------------------------
    |
    | إعدادات الربط مع منصة DASMe الأم
    |
    */

    'api_base_url' => env('DASM_API_URL', 'https://dasm.example.com/api/v1'),
    
    'api_token' => env('DASM_API_TOKEN', ''),
    
    'webhook_secret' => env('DASM_WEBHOOK_SECRET', ''),
    
    'timeout' => env('DASM_API_TIMEOUT', 30),
    
    /*
    | Cache TTL (in seconds)
    */
    'cache_ttl' => [
        'user' => 3600,        // 1 hour
        'car' => 1800,         // 30 minutes
        'entity' => 3600,      // 1 hour
        'auctions' => 300,     // 5 minutes
        'bidding_profile' => 1800, // 30 minutes
    ],
];
