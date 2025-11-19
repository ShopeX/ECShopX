<?php

// Validation Messages
$validation = [
    'name_required_between' => 'Please enter the store name',
    'is_valid_required' => 'Please choose whether to enable',
    'contract_phone_required' => 'Please enter customer service phone',
    'mobile_required' => 'Contact phone is required',
    'hour_required' => 'Please select business hours',
    'distributor_id_required' => 'Please confirm the store to be updated',
];

// Error Messages
$error = [
    'shop_required' => 'Store is required!',
    'delete_shop_error' => 'Error deleting store.',
];

return array_merge($validation, $error); 