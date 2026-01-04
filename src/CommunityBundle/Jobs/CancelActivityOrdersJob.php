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

namespace CommunityBundle\Jobs;

use EspierBundle\Jobs\Job;
use OrdersBundle\Traits\GetOrderServiceTrait;

class CancelActivityOrdersJob extends Job
{
    use GetOrderServiceTrait;

    /**
     * 基本信息
     */
    protected $rows;

    public function __construct($rows)
    {
        $this->rows = $rows;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return bool
     */
    public function handle()
    {
        $orderService = $this->getOrderService('normal_community');
        foreach ($this->rows as $row) {
            try {
                $orderService->cancelOrder($row);
            } catch (\Exception $e) {
                app('log')->info('订单取消失败：'.$e->getMessage().';ROW:'.var_export($row));
            }
        }
        return true;
    }
}
