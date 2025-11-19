<?php

// 验证消息
$validation = [
    'page_error' => '分页参数错误',
    'pagesize_max' => '每页最多查询50条数据',
];

// 错误消息
$error = [
    'params_error' => '参数错误',
];

return array_merge($validation, $error); 