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

    'low_stock_threshold' => 10,

    'max_cart_qty' => 20,

];
