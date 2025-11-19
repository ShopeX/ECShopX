<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
