<?php

return [

    #'host' => env('WEBSOCKET_SERVER_HOST', '0.0.0.0'),
	'host' => '0.0.0.0',
    'port' => env('WEBSOCKET_SERVER_PORT', '9051'),
    'token'=> env('WEBSOCKET_TOKEN', 'kXx3FbVxYLGUTgeXPxrxBHG4AsZ2qQhM'),

    /*
    |--------------------------------------------------------------------------
    | server config
    |--------------------------------------------------------------------------
    |
    | 此处配置为swoole_websocket_serverd的配置选项, 可根据实际
    |
    */
    'options' => [
        'worker_num' => env('WEBSOCKET_WORKER_NUM', 4)
    ],
];
