<?php

// Field Mappings
$fields = [
    'distribution_type' => 'Store Type',
    'shop_code' => 'Store Code',
    'name' => 'Store Name',
    'contact' => 'Contact Name',
    'contract_phone' => 'Contact Phone',
    'addr1' => 'Store Location',
    'addr2' => 'Store Address',
    'addr3' => 'Store Address Number',
    'hour1' => 'Operating Start Time',
    'hour2' => 'Operating End Time',
    'is_delivery' => 'Enable Express Delivery',
    'auto_sync_goods' => 'Auto Sync Products',
    'logo' => 'Store Logo',
    'banner' => 'Store Banner',
    'wdt_shop_no' => 'WDT ERP Store Number',
    'jst_shop_id' => 'JST Store ID',
];

// Error Messages
$error = [
    'excel_format_only' => 'Only Excel file format is supported for employee purchase activity products',
    'region_error' => 'Error, region error',
];

// Option Values
$options = [
    'yes' => 'Yes',
];

return array_merge($fields, $error, $options); 