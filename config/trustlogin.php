<?php

return [
    'standard' => [
        [
            'type' => 'weixin',// 唯一(匹配用) 所有类型配置必须字段，前端不可编辑
            'app_id'  => "",
            'secret'  => "",
            'name'   => '微信', // 所有类型配置必须字段，前端可编辑
            'status' => 'false', // 所有类型配置必须字段，前端不可编辑
        ]
    ],
    'touch' => [
        [
            'type' => 'weixin',
            'app_id'  => "",
            'secret'  => "",
            'name'   => '微信',
            'status' => 'false',
        ]
    ],
];
