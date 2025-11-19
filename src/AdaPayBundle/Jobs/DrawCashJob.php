<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace AdaPayBundle\Jobs;

use EspierBundle\Jobs\Job;
use AdaPayBundle\Services\AdapayDrawCashService;

class DrawCashJob extends Job
{
    public $companyId;
    public $memberId;
    public $settleAccountId;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($companyId, $memberId = 0, $settleAccountId = '')
    {
        $this->companyId = $companyId;
        $this->memberId = $memberId;
        $this->settleAccountId = $settleAccountId;
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        $adaPayDrawCashService = new AdapayDrawCashService();
        $adaPayDrawCashService->autoDrawCash($this->companyId, $this->memberId, $this->settleAccountId);
        return true;
    }
}
