<?php

// 字段映射
$fields = [
    'distribution_type' => '店铺类型',
    'shop_code' => '店铺号',
    'name' => '店铺名称',
    'contact' => '联系人姓名',
    'contract_phone' => '联系方式',
    'addr1' => '店铺所在省市区',
    'addr2' => '店铺详细地址',
    'addr3' => '店铺详细地址门牌号',
    'hour1' => '经营开始时间',
    'hour2' => '经营结束时间',
    'is_delivery' => '开启快递配送',
    'auto_sync_goods' => '自动同步商品',
    'logo' => '店铺LOGO',
    'banner' => '店铺背景',
    'wdt_shop_no' => '旺店通ERP店铺号',
    'jst_shop_id' => '聚水潭店铺编号',
];

// 错误消息
$error = [
    'excel_format_only' => '内购活动商品只支持上传Excel文件格式',
    'region_error' => '错误，省市区错误',
];

// 选项值
$options = [
    'yes' => '是',
];

return array_merge($fields, $error, $options); 