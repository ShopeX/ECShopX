<?php

// 错误消息
$error = [
    'distributor_invalid' => '店铺无效',
    'minimum_withdrawal_amount' => '最少申请提现',
    'yuan' => '元',
    'withdrawal_request_not_exist' => '处理的佣金提现申请不存在',
    'withdrawal_processing_completed' => '当前佣金提现正在处理或已完成',
    'payment_success_server_error' => '付款成功，服务器异常，请通过异常处理重试',
    'system_error_try_later' => '系统错误，请稍后再试',
    'withdrawal_amount_exceeded' => '提现金额超过!可提现金额:',
    'yuan_period' => '元。',
];

// 业务描述
$business = [
    'commission_withdrawal' => '佣金提现',
];

return array_merge($error, $business); 