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
