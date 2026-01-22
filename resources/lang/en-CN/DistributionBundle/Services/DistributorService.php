<?php

// Error Messages
$error = [
    'miniprogram_not_approved' => 'Mini program has never been approved, unable to generate mini program code',
    'mobile_phone_format_error' => 'Please enter a valid mobile number or phone number',
    'shop_code_exists' => 'Current store code already exists, cannot be added repeatedly',
    'shop_mobile_exists' => 'Current store mobile number already exists, cannot be added repeatedly',
    'wdt_shop_bound' => 'Current WDT ERP store number has been bound by other stores',
    'jst_shop_bound' => 'Current JST ERP store number has been bound by other stores',
    'confirm_update_data' => 'Please confirm if the update data is correct',
    'shop_info_query_failed' => 'Store information query failed',
    'distributor_not_exists_or_no_permission' => 'Distributor does not exist or no permission',
    'has_unfinished_refund_cannot_switch_payment_subject' => 'There are unfinished refund orders, cannot switch payment subject',
    'switch_to_distributor_payment_subject_need_at_least_one_payment' => 'When switching to distributor payment subject, at least one payment method (WeChat Pay or Alipay) must be enabled',
    'wxpay_config_incomplete' => 'WeChat Pay configuration is incomplete, please complete the configuration before switching payment subject',
    'distributor_wxpay_config_incomplete' => 'Distributor WeChat Pay configuration is incomplete, please complete the configuration before switching payment subject',
    'platform_wxpay_config_incomplete' => 'Platform WeChat Pay configuration is incomplete, please complete the configuration before switching payment subject',
    'distributor_alipay_config_incomplete' => 'Distributor Alipay configuration is incomplete, please complete the configuration before switching payment subject',
    'platform_alipay_config_incomplete' => 'Platform Alipay configuration is incomplete, please complete the configuration before switching payment subject',
];

// Business Data
$business = [
    'platform_self_operated' => 'Platform Self-operated',
];

// Time Related
$time = [
    'monday' => 'Monday',
    'sunday' => 'Sunday',
    'to' => 'to',
];

return array_merge($error, $business, $time); 