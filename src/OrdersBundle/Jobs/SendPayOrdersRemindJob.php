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
