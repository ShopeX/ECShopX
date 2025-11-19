<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
