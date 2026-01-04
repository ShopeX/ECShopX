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

class SalespersonGiveCouponsRecordStatisticsJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct()
    {
        // ShopEx framework
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
        $salespersons = $criteria
            ->select('company_id, salesperson_id')
            ->from('shop_salesperson')
            ->andWhere($criteria->expr()->eq('salesperson_type', $criteria->expr()->literal('shopping_guide')))
            ->execute()
            ->fetchAll();

        $statisticService = new CompanysStatisticsService();
        foreach ($salespersons as $v) {
            app('log')->debug('导购赠券统计开始=>:'.var_export($v, 1));
            try {
                $statisticService->recordSalespersonGiveCouponsStatistics($v['company_id'], $v['salesperson_id'], $yesterdayDate);
            } catch (\Exception $e) {
                app('log')->debug('导购赠券统计error:'  . $e);
            }
            app('log')->debug('导购赠券统计结束');
        }
        return true;
    }
}
