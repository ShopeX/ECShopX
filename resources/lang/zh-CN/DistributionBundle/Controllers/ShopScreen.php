<?php

// 验证消息
$validation = [
    'title_required' => '请填写标题',
    'sub_title_required' => '请填写副标题',
    'style_params_required' => '请填写样式参数',
    'image_list_required' => '请选择图片',
];

// 错误消息
$error = [
    'params_error' => '参数错误.',
    'ads_params_error' => '广告参数出错',
    'ads_required' => '广告必填',
];

return array_merge($validation, $error); 