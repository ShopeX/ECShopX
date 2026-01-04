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
