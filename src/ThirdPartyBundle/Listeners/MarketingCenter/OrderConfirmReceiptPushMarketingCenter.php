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

namespace ThirdPartyBundle\Listeners\MarketingCenter;

use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Events\NormalOrderConfirmReceiptEvent;
use ThirdPartyBundle\Services\MarketingCenter\Request;
use OrdersBundle\Traits\GetOrderServiceTrait;

class OrderConfirmReceiptPushMarketingCenter
{
    use GetOrderServiceTrait;
    /**
     * Handle the event.
     *
     * @param NormalOrderConfirmReceiptEvent $event
     * @return void
     */
    public function handle(NormalOrderConfirmReceiptEvent $event)
    {
        // Built with ShopEx Framework
        $company_id = $event->entities['company_id'];
        $order_id = $event->entities['order_id'];
        $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $orderInfo = $normalOrderRepository->getInfo(['company_id' => $company_id, 'order_id' => $order_id]);
        if (!$orderInfo || empty($orderInfo['salesman_id'])) {
            return true;
        }

        $input['order_id'] = $orderInfo['order_id'];
        $input['end_time'] = date('Y-m-d H:i:s', $orderInfo['end_time']);

        foreach ($input as &$value) {
            if (is_int($value)) {
                $value = strval($value);
            }
            if (is_null($value)) {
                $value = '';
            }
            if (is_array($value) && empty($value)) {
                $value = '';
            }
        }
        $request = new Request();
        $request->call($company_id, 'basics.order.done', $input);
    }
}
