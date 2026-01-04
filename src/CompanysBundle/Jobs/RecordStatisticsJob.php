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

namespace CompanysBundle\Jobs;

use EspierBundle\Jobs\Job;
use CompanysBundle\Services\CompanysStatisticsService;

class RecordStatisticsJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * 运行任务。
     *
     * @param  Mailer  $mailer
     * @return void
     */
    public function handle()
    {
        //从redis转存订单每天的统计数据（前一天的数据）
        $yesterdayDate = date('Ymd', strtotime(date('Y-m-d')) - 24 * 3600);
        // $companyIds = app('redis')->smembers("companyIds:".$yesterdayDate);
        // app('redis')->expireat("companyIds:".$yesterdayDate, time() + 3*24*3600); // 冗余一天

        $conn = app('registry')->getConnection('default');
        $criteria = $conn->createQueryBuilder();
        $companys = $criteria->select('company_id')->from('companys')->execute()->fetchAll();

        $statisticService = new CompanysStatisticsService();
        foreach ($companys as $v) {
            $statisticService->recordStatistics($v['company_id'], 'service', $yesterdayDate);
            $statisticService->recordStatistics($v['company_id'], 'normal', $yesterdayDate);
        }
        return true;
    }
}
