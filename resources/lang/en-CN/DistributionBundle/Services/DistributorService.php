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