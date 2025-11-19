<?php

return [
    /**
     * 账号基本信息，请从微信公众平台/开放平台获取
     */
    // 'app_id'  => 'your-app-id',         // AppID
    // 'secret'  => 'your-app-secret',     // AppSecret
    // 'token'   => 'your-token',          // Token
    // 'aes_key' => '',                    // EncodingAESKey，兼容与安全模式下请一定要填写！！！

     /**
      * 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
      * 使用自定义类名时，构造函数将会接收一个 `EasyWeChat\Kernel\Http\Response` 实例
      */
    'response_type' => 'array',

    /**
     * 日志配置
     *
     * level: 日志级别, 可选为：
     *         debug/info/notice/warning/error/critical/alert/emergency
     * path：日志文件位置(绝对路径!!!)，要求可写权限
     */
    'log' => [
        'default' => env('APP_ENV', 'local'), // 默认使用的 channel，生产环境可以改为下面的 production
        'channels' => [
            // 测试环境
            'local' => [
                'driver' => 'single',
                'path' => env('WECHAT_LOG_FILE', storage_path('logs/wechat.log')),
                'level' => 'debug',
            ],
            // 生产环境
            'production' => [
                'driver' => 'daily',
                'path' => env('WECHAT_LOG_FILE', storage_path('logs/wechat.log')),
                'level' => 'debug',
            ],
        ],
    ],

    /**
     * 接口请求相关配置，超时时间等，具体可用参数请参考：
     * http://docs.guzzlephp.org/en/stable/request-config.html
     *
     * - retries: 重试次数，默认 1，指定当 http 请求失败时重试的次数。
     * - retry_delay: 重试延迟间隔（单位：ms），默认 500
     * - log_template: 指定 HTTP 日志模板，请参考：https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php
     */
    'http' => [
        'max_retries' => 1,
        'retry_delay' => 500,
        'timeout' => 20.0,
        // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri
    ],

    /**
     * OAuth 配置
     *
     * scopes：公众平台（snsapi_userinfo / snsapi_base），开放平台：snsapi_login
     * callback：OAuth授权完成后的回调页地址
     */
    // 'oauth' => [
    //     'scopes'   => ['snsapi_userinfo'],
    //     'callback' => '/examples/oauth_callback.php',
    // ],

    /* ↓↓↓↓↓↓ 开放平台配置。以下配置在默认文件中并没有包含，为了方便管理，统一放在此处 ↓↓↓↓↓ */
    'open_platform' => [
        'app_id'  => env('WECHAT_APPID'),
        'secret'  => env('WECHAT_SECRET'),
        'token'   => env('WECHAT_TOKEN'),
        'aes_key' => env('WECHAT_AES_KEY'),
    ],
    'wxa_need_open_platform' => env('WXA_NEED_OPEN_PLATFORM', true), // 是否需要第三方平台
    'open_third' => env('WX_OPEN_THIRD', true), // 是否需要服务号，false则需要自己绑定到开放平台
    'mutually_exclusive_temp' => ['yykweishop'], //开启时互斥的小程序模板
    'default_weishop_temp' => env('DEFAULT_WEISHOP_TEMP', 'yykweishop'), //如果同时开启了互斥的模板，设置一个默认值 ];
    'is_automatic_submit_review' => env('IS_AUTOMATIC_SUBMIT_REVIEW', true), //小程序授权之后是否需要自动提交审核;
    'live-player-plugin' => [
        'version' => '1.2.10', // 直播组件版本号
        'provider' => 'wx2b03c6e691cd7370', // 直播组件appid
    ],
];
