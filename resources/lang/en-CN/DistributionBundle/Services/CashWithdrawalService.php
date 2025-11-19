<?php

// Error Messages
$error = [
    'distributor_invalid' => 'Invalid distributor',
    'minimum_withdrawal_amount' => 'Minimum withdrawal amount is',
    'yuan' => 'yuan',
    'withdrawal_request_not_exist' => 'The commission withdrawal request being processed does not exist',
    'withdrawal_processing_completed' => 'Current commission withdrawal is being processed or has been completed',
    'payment_success_server_error' => 'Payment successful, server exception, please retry through exception handling',
    'system_error_try_later' => 'System error, please try again later',
    'withdrawal_amount_exceeded' => 'Withdrawal amount exceeded! Available amount:',
    'yuan_period' => ' yuan.',
];

// Business Descriptions
$business = [
    'commission_withdrawal' => 'Commission withdrawal',
];

return array_merge($error, $business); 