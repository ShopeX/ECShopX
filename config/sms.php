<?php

return [
    'apiUrl' => 'http://api.sms.shopex.cn',
    'entId' => env('SMS_ENTID'),
    'entPwd' => env('SMS_ENTPWD'),
    'license' => env('SMS_LICENCE'),
    'source' => env('SMS_SOURCE'),
    'secret' => env('SMS_SECRET'),
    //不同版本保留的短信模版列表
    'b2c' => [],
    'platform' => [],
    'standard' => [],
    'in_purchase' => [
        '注册验证码',
        '找回密码验证码',
        '手机号登录验证码',
        '手机号修改验证码',
        '微信支付成功通知'
    ],
];