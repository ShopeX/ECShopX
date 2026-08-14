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
     * 在线支付仅允许订单买家本人发起。
     *
     * @param array $order 须含 user_id
     * @param array $authInfo 须含 user_id
     */
    public static function assertBelongsToAuthUser(array $order, array $authInfo): void
    {
        if ($order['user_id'] != $authInfo['user_id']) {
            throw new ResourceException(trans('OrdersBundle/Order.operation_failed'));
        }
    }
}
