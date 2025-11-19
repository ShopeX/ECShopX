<?php

return [
    'show' => env('WORK_WECHAT_SHOW', '1'),
    'corpid' => env('WORK_WECHAT_CORPID', ''),
    'agents' => [
        'app' => [
            'appid' => env('WORK_WECHAT_AGENTS_APP_APPID', ''),
            'agent_id' => env('WORK_WECHAT_AGENTS_APP_AGENTID', ''),
            'secret' => env('WORK_WECHAT_AGENTS_APP_SECRET', ''),
            'token' => env('WORK_WECHAT_AGENTS_APP_TOKEN', ''),
            'aes_key' => env('WORK_WECHAT_AGENTS_APP_ASEKEY', ''),
        ],
        'customer' => [
            'secret' => env('WORK_WECHAT_AGENTS_CUSTOMER_SECRET', ''),
            'token' => env('WORK_WECHAT_AGENTS_CUSTOMER_TOKEN', ''),
            'aes_key' => env('WORK_WECHAT_AGENTS_CUSTOMER_ASEKEY', ''),
        ],
        'report' => [
            'secret' => env('WORK_WECHAT_AGENTS_REPORT_SECRET', ''),
            'token' => env('WORK_WECHAT_AGENTS_REPORT_TOKEN', ''),
            'aes_key' => env('WORK_WECHAT_AGENTS_REPORT_ASEKEY', ''),
        ],
    ]
];