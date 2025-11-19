<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace HfPayBundle\Console;

use HfPayBundle\Services\HfpayDistributorStatisticsDayService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use OrdersBundle\Services\OrderProfitSharingService;

class HfpayStatisticsInitCommand extends Command
{
    /**
         * 命令行执行命令
         * @var string
         */
    protected $signature = 'hfpay:statistics_init {company_id}';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '初始化汇付统计; 参数：companyId';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $companyId = $this->argument('company_id');

        $befordate = '2021-03-01';
        $nowdate = date('Y-m-d');
        DB::table('hfpay_distributor_transaction_statistics')->truncate();
        DB::table('order_profit_sharing_details')->truncate();

        $hfpayDistributorStatisticsDayService = new HfpayDistributorStatisticsDayService();

        for ($befordate; strtotime($nowdate) > strtotime($befordate); $befordate = date('Y-m-d', strtotime($befordate.' +1 day'))) {
            $hfpayDistributorStatisticsDayService->initStatistics($companyId, $befordate);
        }

        $orderProfitSharingService = new OrderProfitSharingService();
        $orderProfitSharingService->initLists($companyId);

        return true;
    }
}
