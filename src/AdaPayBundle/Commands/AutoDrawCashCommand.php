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
namespace AdaPayBundle\Commands;

use AdaPayBundle\Services\AdapayDrawCashService;
use AdaPayBundle\Services\MerchantService;
use AdaPayBundle\Services\SettleAccountService;
use AdaPayBundle\Services\SubMerchantService;
use Illuminate\Console\Command;

class AutoDrawCashCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'adapay:auto_draw_cash';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = 'adapay 自动提现';

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
        $adapayDrawCashService = new AdapayDrawCashService();
        var_dump('自动提现队列 - 开始');
        //查询所有的主商户
        $filter = [];
        $merchantService = new MerchantService();
        $merchantList = $merchantService->getLists($filter, $cols = '*');
        if (!$merchantList) {
            return true;
        }

        $subMerchantService = new SubMerchantService();
        $settleAccountService = new SettleAccountService();
        foreach ($merchantList as $v) {
            //自动提现设置开关
            $autoConfig = $subMerchantService->getAutoCashConfig($v['company_id']);
            $auto_draw_cash = $autoConfig['auto_draw_cash'] ?? 'N';
            if ($auto_draw_cash != 'Y') {
                var_dump('自动提现队列 - 未开启: company_id=' . $v['company_id']);
                //continue;//未开启
            }

            //是否在提现时间点
            $next_time = $autoConfig['next_time'] ?? strtotime('+10 days');
            if ($next_time > time()) {
                var_dump('自动提现队列 - 未到时间: company_id=' . $v['company_id'] . ', 下次提现时间=' . date('y-m-d H:i:s', $next_time));
                //continue;
            }

            //更新最后一次提现的时间节点
            if (!$adapayDrawCashService->getNextTime($autoConfig)) {
                var_dump('自动提现队列 - 自动提现类型错误: company_id=' . $v['company_id']);
                //continue;
            }
            $result = $subMerchantService->setAutoCashConfig($v['company_id'], $autoConfig);

            //主商户提现
            $adapayDrawCashService->drawCash($v['company_id'], 'D0', '0.01', '', '', true);

            //子商户提现
            $filter = ['company_id' => $v['company_id']];
            $accountList = $settleAccountService->getLists($filter, $cols = '*');
            foreach ($accountList as $account) {
                if (!$account['settle_account_id']) {
                    //var_dump('自动提现队列 - 结算账户未开通: member_id=' . $account['member_id']);
                }
                $adapayDrawCashService->drawCash($v['company_id'], 'D0', '0.01', $account['member_id'], '', true);
            }
        }

        var_dump('自动提现队列 - 完成');
    }

    public function createQueue()
    {
        $adapayDrawCashService = new AdapayDrawCashService();
        $adapayDrawCashService->autoDrawCashQueue();
    }
}
