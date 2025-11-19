<?php

return [
    // PayPal API 凭证
    'client_id' => env('PAYPAL_CLIENT_ID', ''),
    'client_secret' => env('PAYPAL_SECRET', ''),

    // 环境设置
    'sandbox' => env('PAYPAL_SANDBOX', true),

    // Webhook 配置
    'webhook_id' => env('PAYPAL_WEBHOOK_ID', ''),

    // 货币设置
    'currency' => env('PAYPAL_CURRENCY', 'USD'),

    // 回调 URL
    'return_url' => env('PAYPAL_RETURN_URL', '/payment/paypal/success'),
    'cancel_url' => env('PAYPAL_CANCEL_URL', '/payment/paypal/cancel'),
    'webhook_url' => env('PAYPAL_WEBHOOK_URL', '/payment/paypal/webhook'),

    // 前端跳转 URL
    'success_url' => env('PAYPAL_SUCCESS_URL', '/payment/success'),
    'fail_url' => env('PAYPAL_FAIL_URL', '/payment/failed'),
    'cancelled_url' => env('PAYPAL_CANCELLED_URL', '/payment/cancelled'),

    // 日志设置
    'log_enabled' => env('PAYPAL_LOG_ENABLED', false),
    'log_level' => env('PAYPAL_LOG_LEVEL', 'INFO'),
    'log_file' => storage_path('logs/paypal.log'),

    // 默认公司ID（用于找不到交易记录时的回退）
    'default_company_id' => env('PAYPAL_DEFAULT_COMPANY_ID', 1),
];
