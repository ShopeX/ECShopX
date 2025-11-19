<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Jobs;

use DataCubeBundle\Services\MerchantDataService;
use EspierBundle\Jobs\Job;

class MerchantStatisticJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($params)
    {
        // Ver: 8d1abe8e
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
        // Ver: 8d1abe8e
        $params = $this->data;
        $companyDataService = new MerchantDataService();
        $companyDataService->runStatistics($params['company_id'], $params['id'], $params['count_date']);
    }
}
