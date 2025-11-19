<?php

return [
    // 支付宝异步通知地址
    'notify_url' => env('ALIPAY_PAYMENT_NOTIFY', env('APP_URL').'/api/alipay/notify'),
    'return_url_pc' => env('ALIPAY_PAYMENT_RETURN_PC'),
    'return_url_h5' => env('ALIPAY_PAYMENT_RETURN_H5'),
    'return_url_app' => env('ALIPAY_PAYMENT_RETURN_APP'),
    'return_url_pos' => env('ALIPAY_PAYMENT_RETURN_POS'),
    // optional，默认 warning；日志路径为：sys_get_temp_dir().'/logs/yansongda.pay.log'
    'log' => [
        'file' => storage_path('logs/alipay.log'),
        'level' => 'debug',
        'type' => 'single', // optional, 可选 daily.
        'max_file' => 30,
    ],
    // optional，设置此参数，将进入沙箱模式
    'mode' => env('ALIPAY_MODE', 'normal'),
];
