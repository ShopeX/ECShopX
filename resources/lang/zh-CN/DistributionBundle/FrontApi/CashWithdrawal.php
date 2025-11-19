<?php

// 验证消息
$validation = [
    'page_error' => '分页参数错误',
    'pagesize_max' => '每页最多查询50条数据',
];

// 错误消息
$error = [
    'withdrawal_min_amount' => '佣金提现最少为1元',
    'withdrawal_max_amount' => '佣金单次最多提现800元',
    'select_distributor_first' => '请选择店铺后提现',
];

return array_merge($validation, $error); 