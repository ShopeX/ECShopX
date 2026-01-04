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

namespace SystemLinkBundle\Console\WdtErp;

use Illuminate\Console\Command;
use SystemLinkBundle\Services\WdtErp\Client\WdtErpClient;
use SystemLinkBundle\Services\WdtErp\Client\Pager;
use SystemLinkBundle\Services\WdtErp\OrderAfterSaleService;
use SystemLinkBundle\Services\WdtErpSettingService;
use DistributionBundle\Services\DistributorService;

use Exception;

class SyncAfterSaleCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'wdt:sync_aftersales';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '同步旺店通ERP售后单';

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
        $wdtErpSettingService = new WdtErpSettingService();
        $wdtSettingKeys = app('redis')->keys($wdtErpSettingService->getRedisPrefix().'*');
        if (empty($wdtSettingKeys)) {
            return true;
        }

        $endTime = date('Y-m-d H:i:00');
        $beginTime = date('Y-m-d H:i:00', strtotime($endTime) - 6 * 60);
        $orderAfterSaleService = new OrderAfterSaleService();
        foreach ($wdtSettingKeys as $wdtSettingKey) {
            $setting = $wdtErpSettingService->getWdtErpSetting(0, $wdtSettingKey);
            if ($setting['is_open'] !== true) {
                continue;
            }

            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['company_id' => $setting['company_id'], 'wdt_shop_id|gt' => 0], 'wdt_shop_no');
            $shopNos = array_column($distributorList, 'wdt_shop_no');
            $shopNos[] = $setting['shop_no'];
            $shopNos = array_filter($shopNos);

            $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $setting['sid'], $setting['app_key'], $setting['app_secret']);
            foreach ($shopNos as $shopNo) {
                $pageNo = 0;
                do {
                    $orderList = $this->getAfterSaleList($wdtErpClient, $shopNo, $beginTime, $endTime, $pageNo);
                    if (!empty($orderList)) {
                        foreach ($orderList as $order) {
                            $orderAfterSaleService->aftersaleStatusUpdate($order->raw_refund_nos, $order->src_tids, $order->status, $order->stockin_status);
                        }
                    }
                    $pageNo += 1;
                } while(count($orderList) > 0);
            }
        }

        return true;
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $shopNo
     * @param $beginTime
     * @param $endTime
     * @param $pageNo
     * @return array
     */
    private function getAfterSaleList(WdtErpClient $wdtErpClient, $shopNo, $beginTime, $endTime, $pageNo)
    {
        // 53686f704578
        $order = [];
        $method = config('wdterp.methods.after_sale_query');
        try {
            $pager = new Pager(50, $pageNo);
            $parMap = new \stdClass();
            $parMap->shop_nos = $shopNo;
            $parMap->modified_from = $beginTime;
            $parMap->modified_to = $endTime;
            app('log')->debug('SyncAfterSaleCommand=>method:'.$method.",request:\r\n". var_export(['pager' => $pager, 'parMap' => $parMap], 1));
            $result = $wdtErpClient->pageCall($method, $pager, $parMap);
            $order = $result->order;
            app('log')->debug('SyncAfterSaleCommand=>method:'.$method.",request:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
        return $order;
    }
}

