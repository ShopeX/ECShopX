<?php
// 聚合支付配置文件

return [
    'env' => env('ADAPAY_ENV', 'live'), //live or test
    'sdk_version' => env('ADAPAY_SDK_VERSION', 'v1.1.4'),
    'log_dir' => env('ADAPAY_LOG_DIR', storage_path('logs')),
    'debug' => env('ADAPAY_DEBUG', true),
    'notify_url' => env('ADAPAY_NOTIFY_URL'),
    'gate_way_url' => env('ADAPAY_GATE_WAY_URL', 'https://%s.adapay.tech'),
    'fee_mode' => env('ADAPAY_FEE_MODE', 'O'),
    'rsa_public_key' => env('ADAPAY_RSA_PUBLIC_KEY', 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwN6xgd6Ad8v2hIIsQVnbt8a3JituR8o4Tc3B5WlcFR55bz4OMqrG/356Ur3cPbc2Fe8ArNd/0gZbC9q56Eb16JTkVNA/fye4SXznWxdyBPR7+guuJZHc/VW2fKH2lfZ2P3Tt0QkKZZoawYOGSMdIvO+WqK44updyax0ikK6JlNQIDAQAB'),
    
    //商户菜单控制  
    //valid 已开户 invalid 未开户; example: admin->valid 主商户已开户需要禁用的菜单项; dealer->invalid (adapay)经销商未开户需要禁用的菜单项
    //'adapay_merchant' => 2   key表示菜单的alias_name, 数值表示level层级
    // 'deny_menus' => [
    //     //主商户
    //     'admin' => [
    //         'succ' => ['/applications/adapay/adapay_merchant' => 3],
    //         'fail' => [
    //             '/shop_dealer' => 1, '/applications/adapay/adapay_merchant_info' => 3, '/applications/adapay/adapay_member_audit' => 3,'/applications/adapay/adapay_trades' => 3, '/applications/adapay/adapay_cash' => 3, '/applications/adapay/adapay_cash_setting' => 3],
    //     ],
    //     //adapay经销商端
    //     'dealer' => [
    //         'succ' => ['/dealer/adapay_member/entry' => 2],
    //         'fail' => ['/dealer/adapay_member/info' => 2, '/dealer/trades/adapay_trades' => 2, '/dealer/trades/adapay_cash' => 2],
    //         'sub_deny' => ['/dealer/setting/account_management' => 2],
    //     ],
    //     //店铺端
    //     'distributor' => [
    //         'admin_fail' => ['/shopadmin/applications' => 1],
    //         'succ' => ['/shopadmin/applications/adapay/adapay_member_entry' => 3],
    //         'fail' => ['/shopadmin/applications/adapay/adapay_trades' => 3, '/shopadmin/applications/adapay/adapay_cash' => 3, '/shopadmin/applications/adapay/adapay_member_info' => 3],
    //     ]
    // ],
    'wx_lite_jumppay' => env('ADAPAY_WXLITE_JUMPPAY', false),
];
