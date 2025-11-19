<?php

// 字段标题
$fields = [
    'mobile' => '手机号',
    'username' => '姓名',
    'distributor_no' => '店铺号',
];

// 错误消息
$error = [
    'excel_format_only' => '内购活动商品只支持上传Excel文件格式',
    'distributor_not_found' => '错误，店铺未找到',
];

return array_merge($fields, $error); 