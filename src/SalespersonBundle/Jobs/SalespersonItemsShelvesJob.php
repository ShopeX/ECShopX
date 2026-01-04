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
