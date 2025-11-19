<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 小程序模板
    |--------------------------------------------------------------------------
    |
    | 配置小程序模板对应的参数版本号等
    |
     */

    /*'yykmendian' => [
        'key_name'    => 'yykmendian',
        'name'        => '服务预约小程序',
        'tag'         => '门店管理', //标签
        'template_id' => intval(env('YYKMENDIAN_TEMPLATE_ID')),
        'version'     => env('YYKMENDIAN_VERSION'),
        'desc'        => '门店管理',
        'domain' => [
            'requestdomain'   => env('YYKMENDIAN_REQUESTDOMAIN') ? explode(',', env('YYKMENDIAN_REQUESTDOMAIN')) : [], // request合法域名,以逗号分割
            'wsrequestdomain' => env('YYKMENDIAN_WSREQUESTDOMAIN') ? explode(',', env('YYKMENDIAN_WSREQUESTDOMAIN')) : [], // socket合法域名,以逗号分割
            'uploaddomain'    => env('YYKMENDIAN_UPLOADDOMAIN') ? explode(',', env('YYKMENDIAN_UPLOADDOMAIN')) : [], // uploadFile合法域名,以逗号分割
            'downloaddomain'  => env('YYKMENDIAN_DOWNLOADDOMAIN') ? explode(',', env('YYKMENDIAN_DOWNLOADDOMAIN')), : [] // downloadFile合法域名,以逗号分割
            'webviewdomain'   => env('YYKMENDIAN_WEBVIEWDOMAIN') ? explode(',', env('YYKMENDIAN_WEBVIEWDOMAIN')) : [], //业务域名,以逗号分割
        ],
    ],*/
    'yykweishop' => [
        'key_name'    => 'yykweishop',
        'name'        => '微商城',
        'tag'         => '微商城', //标签
        'template_id' => intval(env('YYKWEISHOP_TEMPLATE_ID')),
        'version'     => env('YYKWEISHOP_VERSION'),
        'desc'        => '微商城小程序',
        'domain' => [
            'requestdomain'   => env('YYKWEISHOP_REQUESTDOMAIN') ? explode(',', env('YYKWEISHOP_REQUESTDOMAIN')) : [], // request合法域名,以逗号分割
            'wsrequestdomain' => env('YYKWEISHOP_WSREQUESTDOMAIN') ? explode(',', env('YYKWEISHOP_WSREQUESTDOMAIN')) : [], // socket合法域名,以逗号分割
            'uploaddomain'    => env('YYKWEISHOP_UPLOADDOMAIN') ? explode(',', env('YYKWEISHOP_UPLOADDOMAIN')) : [], // uploadFile合法域名,以逗号分割
            'downloaddomain'  => env('YYKWEISHOP_DOWNLOADDOMAIN') ? explode(',', env('YYKWEISHOP_DOWNLOADDOMAIN')) : [], // downloadFile合法域名,以逗号分割
            'webviewdomain'   => env('YYKWEISHOP_WEBVIEWDOMAIN') ? explode(',', env('YYKWEISHOP_WEBVIEWDOMAIN')) : [], //业务域名,以逗号分割
        ],
    ],
];
