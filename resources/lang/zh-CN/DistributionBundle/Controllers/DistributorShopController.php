<?php

// 验证消息
$validation = [
    'name_required_between' => '请填写门店名称',
    'is_valid_required' => '请选择是否启用',
    'contract_phone_required' => '请填写客服电话',
    'mobile_required' => '联系人电话',
    'hour_required' => '请选择营业时间',
    'distributor_id_required' => '请确定需要更新的店铺',
];

// 错误消息
$error = [
    'shop_required' => '门店必选！',
    'delete_shop_error' => '删除门店出错.',
];

return array_merge($validation, $error); 