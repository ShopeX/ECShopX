<?php

/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace PaymentBundle\Services;

use Dingo\Api\Exception\ResourceException;

class PaymentOrderOwnershipGuard
{
    /**
     * 在线支付仅允许订单买家本人发起；收银/店务 operator 无 user_id 时跳过归属比对。
     *
     * @param array $order 须含 user_id
     * @param array $authInfo member 须含 user_id；operator 可无 user_id（含 operator_id 或 operator_type≠user）
     */
    public static function assertBelongsToAuthUser(array $order, array $authInfo): void
    {
        if (!isset($authInfo['user_id'])) {
            if (!empty($authInfo['operator_id'])
                || (($authInfo['operator_type'] ?? '') !== 'user')) {
                return;
            }

            throw new ResourceException(trans('OrdersBundle/Order.operation_failed'));
        }

        if ($order['user_id'] != $authInfo['user_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.operation_failed'));
        }
    }
}
