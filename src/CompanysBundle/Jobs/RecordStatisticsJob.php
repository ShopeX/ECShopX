<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
