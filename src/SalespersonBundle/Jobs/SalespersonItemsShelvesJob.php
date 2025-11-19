<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Jobs;

use EspierBundle\Jobs\Job;
use SalespersonBundle\Services\SalespersonItemsShelvesService;

class SalespersonItemsShelvesJob extends Job
{
    public $companyId;
    public $activityId;
    public $activityType;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $activityId, $activityType)
    {
        $this->companyId = $companyId;
        $this->activityId = $activityId;
        $this->activityType = $activityType;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        app('log')->info('SalespersonItemsShelvesJob开始执行->companyId:' . $this->companyId . '-activityId:' . $this->activityId . '-activityType:' . $this->activityType);
        $SalespersonItemsShelvesService = new SalespersonItemsShelvesService();
        $SalespersonItemsShelvesService->addSalespersonItemsShelves($this->companyId, $this->activityId, $this->activityType);
        return true;
    }
}
