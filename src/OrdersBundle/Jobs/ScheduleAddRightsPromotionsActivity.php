<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Jobs;

use EspierBundle\Jobs\Job;

use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\Orders\GroupsServiceOrderService;

class ScheduleAddRightsPromotionsActivity extends Job
{
    // 开团id
    public $teamId = '';
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($teamId)
    {
        $this->teamId = $teamId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $orderService = new OrderService(new GroupsServiceOrderService());
        $orderService->addGroupsRights($this->teamId);
    }
}
