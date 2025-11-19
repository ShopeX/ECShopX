<?php
// 聚水潭接口配置文件

return [
    'is_online' => env('JUSHUITAN_IS_ONLINE', false),
    'app_key' => env('JUSHUITAN_APP_KEY', ''),
    'app_secret' => env('JUSHUITAN_APP_SECRET', ''),
    'api_base_url' => env('JUSHUITAN_BASE_URL', ''),
    'oauth_base_url' => env('JUSHUITAN_OAUTH_BASE_URL', ''),

    'methods' => [
        'oauth_token_isv' => '/openWebIsv/auth/accessToken',
        'oauth_token' => '/openWeb/auth/accessToken',
        'item_store_query' => '/open/jushuitan/inventory/query',
        'item_add' => '/open/jushuitan/itemsku/upload',
        'shop_item_add' => '/open/jushuitan/skumap/upload',
        'order_add' => '/open/jushuitan/orders/upload',
        'order_cancel' => '/open/jushuitan/orderbyoid/cancel',
        'aftersale_add' => '/open/aftersale/upload',
    ],
];
