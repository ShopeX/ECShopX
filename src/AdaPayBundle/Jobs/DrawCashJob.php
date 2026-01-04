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
