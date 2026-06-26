<?php

/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 */

namespace CompanysBundle\Services;

use Dingo\Api\Exception\ResourceException;

/**
 * 店务立即购买：物流收货地址校验（与 OrderService::checkCreateOrderNeedParams 物流字段对齐）。
 * checkout（getOrderTempInfo）不强制调用；创建订单且 receipt_type 为 logistics 时仍由 OrderService 校验。
 */
final class ShopadminFastBuyCheckoutAddressValidator
{
    /**
     * @param array<string, mixed> $params
     *
     * @throws ResourceException
     */
    public static function assertLogisticsAddressPresent(array $params): void
    {
        $rules = [
            'receiver_name' => ['required|zhstring', '请填写正确的收货人姓名'],
            'receiver_mobile' => ['required', '请填写联系方式'],
            'receiver_zip' => ['required|postcode', '请填写正确的邮编'],
            'receiver_state' => ['required|zhstring', '请填写正确的省份'],
            'receiver_city' => ['required|zhstring', '请填写正确的城市'],
            'receiver_district' => ['required|zhstring', '请填写正确的地区'],
            'receiver_address' => ['required', '请填写正确的详细地址'],
        ];
        if (!isset($params['receiver_zip']) || !preg_match('/^\d{6}$/', (string) $params['receiver_zip'])) {
            $params['receiver_zip'] = '000000';
        }
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
    }
}
