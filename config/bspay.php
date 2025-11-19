<?php
// 汇付斗拱支付配置文件
return [
    'prod_mode' => env('PROD_MODE', true),
    'log_dir' => env('BSPAY_LOG_DIR', storage_path('logs')),
    'debug' => env('BSPAY_DEBUG', true),
    'notify_url' => env('BSPAY_NOTIFY_URL', ''),
    'rsa_public_key' => env('BSPAY_RSA_PUBLIC_KEY', ''),
    'headquarters_fee_mode' => env('BSPAY_HEADQUARTERS_FEE_MODE', 2),// 平台商户的交易手续费扣费方式 1:外扣 2:内扣
    // 'wx_sub_appid' => env('BSPAY_WX_SUB_APPID', ''),//微信分配的子商户公众账号id
];
