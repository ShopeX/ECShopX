<?php

// 验证消息
$validation = [
    'name_required_max' => '店铺名称必填且最大长度不能超过255个字符',
    'province_required' => '省市区必填',
    'city_required' => '省市区必填',
    'area_required' => '省市区必填',
    'address_required_max' => '地址必填最大长度不能超过255个字符',
    'contract_phone_required' => '联系电话必填',
    'hours_required' => '营业时间必填',
    'workdays_required' => '重复日期必填',
    'wait_pickup_days_required' => '最长预约时间必填',
    'latest_pickup_time_required' => '当天最晚自提时间必填',
    'latest_pickup_time_required_current' => '当前最晚自提时间必填',
    'id_required' => '自提点id必填',
    'rel_distributor_id_required' => '店铺id必填',
];

return $validation; 