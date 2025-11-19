<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Jobs;

use SalespersonBundle\Services\SalespersonStatisticsService;
use EspierBundle\Jobs\Job;

class SalespersonStatisticsJob extends Job
{
    public $companyId;
    public $distributorId;
    public $salespersonId;
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $distributorId, $salespersonId)
    {
        $this->companyId = $companyId;
        $this->distributorId = $distributorId;
        $this->salespersonId = $salespersonId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $salespersonStatisticsService = new SalespersonStatisticsService();
        $salespersonStatisticsService->saveSalespersonStatisticsJob($this->companyId, $this->distributorId, $this->salespersonId);
    }
}
