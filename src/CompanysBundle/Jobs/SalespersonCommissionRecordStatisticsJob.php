<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Jobs;

use EspierBundle\Jobs\Job;
use CompanysBundle\Services\CompanysStatisticsService;

class SalespersonCommissionRecordStatisticsJob extends Job
{
    public $data;

    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct()
    {
        // Ref: 1996368445
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
        app('log')->debug('导购数据分润统计开始');
        foreach ($salespersons as $v) {
            app('log')->debug('导购数据分润统计开始=>:'.var_export($v, 1));
            try {
                $statisticService->recordSalespersonCommissionStatistics($v['company_id'], $v['salesperson_id'], $yesterdayDate);
            } catch (\Exception $e) {
                app('log')->debug('导购数据分润统计error:'  . $e);
            }
        }
        app('log')->debug('导购数据分润统计结束');
        return true;
    }
}
