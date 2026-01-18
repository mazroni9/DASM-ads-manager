<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Ad Platform Configuration
    |--------------------------------------------------------------------------
    */

    /*
    | Default median CPC/CPM per placement (in SAR)
    | These will be calculated dynamically from stats later
    */
    'median_bids' => [
        'search_listings' => [
            'CPC' => 1.5,
            'CPM' => 15.0,
        ],
        'car_details' => [
            'CPC' => 1.0,
            'CPM' => 10.0,
        ],
        'auction_room' => [
            'CPC' => 2.0,
            'CPM' => 20.0,
        ],
        'home' => [
            'CPC' => 1.2,
            'CPM' => 12.0,
        ],
        'live_stream_overlay' => [
            'CPC' => 2.5,
            'CPM' => 25.0,
        ],
    ],

    /*
    | Average CTR per placement (for quality score calculation)
    */
    'avg_ctr' => [
        'search_listings' => 2.0, // 2%
        'car_details' => 3.0,
        'auction_room' => 2.5,
        'home' => 1.5,
        'live_stream_overlay' => 1.0,
    ],

    /*
    | Ad slots configuration per placement
    */
    'slots' => [
        'search_listings' => [
            'top_3' => 3,
            'in_feed' => 1, // every N results
            'in_feed_interval' => 10,
        ],
        'car_details' => [
            'similar_sponsored' => 3,
        ],
        'auction_room' => [
            'right_rail' => 1,
            'between_lots' => 1,
        ],
        'live_stream_overlay' => [
            'lower_third' => 1,
            'side_banner' => 1,
        ],
    ],

    /*
    | Minimum budget requirements
    */
    'min_daily_budget' => 10.0, // SAR

    /*
    | Anti-fraud settings
    */
    'anti_fraud' => [
        'click_cooldown_seconds' => 20,
        'max_clicks_per_session_per_creative' => 10,
        'max_clicks_per_5min_per_session' => 20,
        'min_viewport_seconds' => 1,
        'min_visible_ratio' => 0.5,
    ],

    /*
    | Ranking algorithm weights
    */
    'ranking' => [
        'bid_weight' => 0.7,
        'quality_weight' => 1.0,
        'relevance_weight' => 1.2,
    ],

    /*
    | Tracking token settings
    */
    'tracking' => [
        'token_ttl_seconds' => 600, // 10 minutes
        'secret_key' => env('ADS_TRACKING_SECRET', 'your-secret-key'),
    ],

    /*
    | Budget exhaustion threshold
    */
    'budget_threshold' => 0.10, // If balance < 10% of daily_budget, pause campaign

    /*
    | Currency
    */
    'currency' => 'SAR',
];
