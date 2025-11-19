<?php

// 错误消息
$error = [
    'miniprogram_not_approved' => '小程序还从未通过审核，无法生成小程序码',
    'mobile_phone_format_error' => '请填写正确的手机号或电话号码',
    'shop_code_exists' => '当前店铺编号已存在，不可重复添加',
    'shop_mobile_exists' => '当前店铺手机号已存在，不可重复添加',
    'wdt_shop_bound' => '当前旺店通ERP门店编号已经被其他店铺绑定',
    'jst_shop_bound' => '当前聚水潭ERP店铺编号已经被其他店铺绑定',
    'confirm_update_data' => '请确认修改数据是否正确',
    'shop_info_query_failed' => '店铺信息查询失败',
];

// 业务数据
$business = [
    'platform_self_operated' => '平台自营',
];

// 时间相关
$time = [
    'monday' => '周一',
    'sunday' => '周日',
    'to' => '至',
];

return array_merge($error, $business, $time); 