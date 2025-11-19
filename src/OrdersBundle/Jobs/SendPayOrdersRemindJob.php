<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;
use OrdersBundle\Entities\OrderAssociations;
use OrdersBundle\Traits\GetOrderServiceTrait;

class SendPayOrdersRemindJob extends Job
{
    use GetOrderServiceTrait;

    public $orderData;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($orderData)
    {
        // FIXME: check performance
        $this->orderData = $orderData;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // FIXME: check performance
        $orderAssociationsRepository = app('registry')->getManager('default')->getRepository(OrderAssociations::class);
        $order = $orderAssociationsRepository->get(['order_id' => $this->orderData['order_id']]);
        if (!$order || $order['order_status'] != 'NOTPAY') {
            return true;
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);
        $orderService->sendPayOrdersRemind($this->orderData);

        return true;
    }
}
