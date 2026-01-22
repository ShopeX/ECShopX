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
    'distributor_not_exists_or_no_permission' => '店铺不存在或无权限',
    'has_unfinished_refund_cannot_switch_payment_subject' => '有未完成的退款单，不允许切换收款主体',
    'switch_to_distributor_payment_subject_need_at_least_one_payment' => '切换为店铺收款主体时，至少需要开启微信支付或支付宝支付中的一种',
    'wxpay_config_incomplete' => '微信支付配置不完整，请先完成配置后再切换收款主体',
    'distributor_wxpay_config_incomplete' => '店铺微信支付配置不完整，请先完成配置后再切换收款主体',
    'platform_wxpay_config_incomplete' => '平台微信支付配置不完整，请先完成配置后再切换收款主体',
    'distributor_alipay_config_incomplete' => '店铺支付宝配置不完整，请先完成配置后再切换收款主体',
    'platform_alipay_config_incomplete' => '平台支付宝配置不完整，请先完成配置后再切换收款主体',
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