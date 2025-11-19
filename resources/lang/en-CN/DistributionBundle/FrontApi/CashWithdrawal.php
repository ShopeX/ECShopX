<?php

// Validation Messages
$validation = [
    'page_error' => 'Pagination parameter error',
    'pagesize_max' => 'Maximum 50 records per page',
];

// Error Messages
$error = [
    'withdrawal_min_amount' => 'Minimum withdrawal amount is 1 yuan',
    'withdrawal_max_amount' => 'Maximum withdrawal amount is 800 yuan per transaction',
    'select_distributor_first' => 'Please select a store before withdrawal',
];

return array_merge($validation, $error); 