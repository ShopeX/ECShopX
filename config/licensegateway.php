<?php

// license配置
return [

    /*
    |--------------------------------------------------------------------------
    | 产品名称
    |--------------------------------------------------------------------------
    |
    | 对应在激活码服务器那边的产品名称
    |
    */

    'product_name' => env('LICENSE_PRODUCT', 'yyk'),

    /*
    |--------------------------------------------------------------------------
    | 激活地址
    |--------------------------------------------------------------------------
    |
    | 激活码服务器的地址
    |
    */

    'license_url' =>env('LICENSE_PRODUCTION_URL', 'https://service.ec-os.net/api/yyk/register'),

    //独立部署激活地址
    'independent_license_url' =>env('INDEPENDENT_LICENSE_PRODUCTION_URL', 'https://service.ec-os.net/api/active/register'),
    'version' =>env('INDEPENDENT_VERSION', '2.0.9'),
//    'independent_product_name' =>env('INDEPENDENT_PRODUCT_NAME', 'ECShopx_Source_Cluster'),

];
