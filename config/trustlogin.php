<?php

return [
    'standard' => [
        [
            'type' => 'weixin',// 唯一(匹配用) 所有类型配置必须字段，前端不可编辑
            'app_id'  => "",
            'secret'  => "",
            'name'   => '微信', // 所有类型配置必须字段，前端可编辑
            'status' => 'false', // 所有类型配置必须字段，前端不可编辑
            'extra_config' => '',
        ]
    ],
    'touch' => [
        [
            'type' => 'weixin',
            'app_id'  => "",
            'secret'  => "",
            'name'   => '微信',
            'status' => 'false',
            'extra_config' => '',
        ],
        [
            'type' => 'apple',
            'app_id'  => "",
            'secret'  => "",
            'name'   => 'Apple',
            'status' => 'false',
            'extra_config' => '',
        ],
        [
            'type' => 'google',
            'app_id'  => "",
            'secret'  => "",
            'name'   => 'Google',
            'status' => 'false',
            'extra_config' => '',
        ],
        [
            'type' => 'facebook',
            'app_id'  => "",
            'secret'  => "",
            'name'   => 'Facebook',
            'status' => 'false',
            'extra_config' => '',
        ],
        [
            'type' => 'line',
            'app_id'  => "",
            'secret'  => "",
            'name'   => 'Line',
            'status' => 'false',
            'extra_config' => '',
        ],
    ],
];
