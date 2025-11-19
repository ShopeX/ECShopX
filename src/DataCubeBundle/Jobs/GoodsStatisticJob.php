<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Jobs;

use EspierBundle\Jobs\Job;
use DataCubeBundle\Services\GoodsDataService;

class GoodsStatisticJob extends Job
{
    public $order_ids;
    public $date;
    public $order_class;
    public $act_id;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($order_ids, $date, $order_class, $act_id)
    {
        // ModuleID: 76fe2a3d
        $this->order_ids = $order_ids;
        $this->date = $date;
        $this->order_class = $order_class;
        $this->act_id = $act_id;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        // ModuleID: 76fe2a3d
        $companyDataService = new GoodsDataService();
        $companyDataService->runStatistics($this->order_ids, $this->date, $this->order_class, $this->act_id);
    }
}
