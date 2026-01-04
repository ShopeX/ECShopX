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
use SystemLinkBundle\Services\WdtErpSettingService;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use DistributionBundle\Services\DistributorService;

use Exception;

class SyncLogisticsCommand extends Command
{
    use GetOrderServiceTrait;

    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'wdt:sync_logistics';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '同步旺店通ERP物流';

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

        foreach ($wdtSettingKeys as $wdtSettingKey) {
            $setting = $wdtErpSettingService->getWdtErpSetting(0, $wdtSettingKey);
            if ($setting['is_open'] !== true) {
                continue;
            }

            $companyId = $setting['company_id'];

            $distributorService = new DistributorService();
            $distributorList = $distributorService->getLists(['company_id' => $companyId, 'wdt_shop_id|gt' => 0], 'wdt_shop_no');
            $shopNos = array_column($distributorList, 'wdt_shop_no');
            $shopNos[] = $setting['shop_no'];
            $shopNos = array_filter($shopNos);

            $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $setting['sid'], $setting['app_key'], $setting['app_secret']);

            foreach ($shopNos as $shopNo) {
                $pageNo = 0;
                do {
                    $logisticsList = $this->syncLogistics($wdtErpClient, $shopNo, $pageNo);
                    if (!empty($logisticsList)) {
                        foreach ($logisticsList as $key => $logistics) {
                            // 同步物流
                            $syncStatus = $this->doOrderDelivery($companyId, $logistics);
                            $logisticsList[$key]->status = $syncStatus === false ? 2 : 0;
                        }
                        $this->setSyncAck($wdtErpClient, $logisticsList);
                    }
                    $pageNo += 1;
                } while(count($logisticsList) > 0);
            }
        }

        return true;
    }

    /**
     * @param $companyId
     * @param $logistics
     * @return bool
     */
    private function doOrderDelivery($companyId, $logistics)
    {
        try {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $logistics->tid);
            if (empty($order)) {
                app('log')->debug('订单不存在');
                return false;
            }

            if ($order['delivery_status'] == 'DONE') {
                app('log')->debug('订单已发货，请勿重复发货');
                return true;
            }

            $orderService = $this->getOrderServiceByOrderInfo($order);
            $orderList = $orderService->getOrderList(['company_id' => $order['company_id'], 'order_id' => $order['order_id']], -1);
            $order = $orderList['list'][0];

            // 是否拆单发货
            $isPartSync = $logistics->is_part_sync === 1;
            $oidArr = [];
            if ($isPartSync) {
                $oidArr = explode(',', $logistics->oids);
            }

            $sepInfo = [];
            foreach ($order['items'] as $item) {
                if ($item['delivery_status'] == 'DONE') {
                    continue;
                }

                if($item['delivery_status'] == 'PENDING'){
                    $item['delivery_code'] = $logistics->logistics_no;
                    $item['delivery_corp'] = $logistics->logistics_code;
                    $item['delivery_num'] = $item['num'];
                    if ($isPartSync) {
                        if (in_array($item['order_id'], $oidArr)) {
                            $sepInfo[] = $item;
                        }
                    } else {
                        $sepInfo[] = $item;
                    }
                }
            }

            if (empty($sepInfo)) {
                app('log')->debug('没有发货信息');
            }

            $deliveryParams = [
                'type' => 'new',
                'company_id' => $order['company_id'],
                'delivery_code' => $logistics->logistics_no,
                'delivery_corp' => $logistics->logistics_code,
                'delivery_type' => 'sep',
                'order_id' => $order['order_id'],
                'sepInfo' => json_encode($sepInfo),
            ];

            app('log')->debug("旺店通 去发货 ".__FUNCTION__.__LINE__. " delivery_params=>".var_export($deliveryParams,1) );
            $result = $orderService->delivery($deliveryParams);
            return $result;
        } catch (Exception $e) {
            $msg = $e->getLine().",msg=>".$e->getMessage();
            app('log')->debug("旺店通 发货失败 ".__FUNCTION__.__LINE__. " msg=>".$msg );
            return false;
        }
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $logisticsList
     * @return void
     */
    private function setSyncAck(WdtErpClient $wdtErpClient, $logisticsList)
    {
        $syncList = [];
        foreach ($logisticsList as $logistics) {
            $item = new \stdClass();
            $item->sync_id = $logistics->sync_id;
            $item->status = $logistics->status;
            $syncList[] = $item;
        }
        $method = config('wdterp.methods.logistics_sync_success');
        try {
            app('log')->debug('SyncLogisticsCommand=>method:'.$method.",request:\r\n". var_export($syncList, 1));
            $result = $wdtErpClient->call($method, $syncList);
            app('log')->debug('SyncLogisticsCommand=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $shopNo
     * @param $pageNo
     * @return void
     */
    private function syncLogistics(WdtErpClient $wdtErpClient, $shopNo, $pageNo)
    {
        $method = config('wdterp.methods.logistics_get_wait_sync');
        $parMap = new \stdClass();
        $parMap->shop_no = $shopNo;
        $parMap->is_own_platform = true;
        $pager = new Pager(10, $pageNo, true);
        $logisticsList = [];
        try {
            app('log')->debug('SyncLogisticsCommand=>method:'.$method.",request:\r\n". var_export(['pager' => $pager, 'parMap' => $parMap], 1));
            $result = $wdtErpClient->pageCall($method, $pager, $parMap);
            $logisticsList = $result->data;
            app('log')->debug('SyncLogisticsCommand=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }
        return $logisticsList;
    }
}

