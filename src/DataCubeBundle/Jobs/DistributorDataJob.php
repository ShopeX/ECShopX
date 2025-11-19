<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Jobs;

use DataCubeBundle\Services\DistributorDataService;
use EspierBundle\Jobs\Job;

class DistributorDataJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        $this->data = $params;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $params = $this->data;
        $companyDataService = new DistributorDataService();
        $companyDataService->runStatistics($params['company_id'], $params['distributor_id'], $params['count_date'], $params['merchant_id']);
    }
}
