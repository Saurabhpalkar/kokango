<?php

return [

    'payment' => [
        'driver' => env('PAYMENT_DRIVER', 'fake'),

        'razorpay' => [
            'key_id' => env('RAZORPAY_KEY_ID'),
            'key_secret' => env('RAZORPAY_KEY_SECRET'),
            'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET'),
        ],
    ],

    'shipping' => [
        'driver' => env('SHIPPING_DRIVER', 'fake'),

        'shiprocket' => [
            'email' => env('SHIPROCKET_EMAIL'),
            'password' => env('SHIPROCKET_PASSWORD'),
            'pickup_pincode' => env('SHIPROCKET_PICKUP_PINCODE'),
            'base_url' => 'https://apiv2.shiprocket.in/v1/external',
        ],
    ],

    // Cash on Delivery. max_paise = 0 means no upper limit.
    'cod' => [
        'enabled' => (bool) env('COD_ENABLED', true),
        'max_paise' => (int) env('COD_MAX_PAISE', 0),
    ],

    'low_stock_threshold' => 10,

    'max_cart_qty' => 20,

    // API requests per minute per guest IP (signed-in users get twice this).
    'rate_limit_per_minute' => (int) env('API_RATE_LIMIT', 120),

];
