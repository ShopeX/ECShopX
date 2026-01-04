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
use SystemLinkBundle\Services\WdtErpSettingService;
use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Traits\CheckPromotionsValid;
use GoodsBundle\Services\ItemStoreService;
use GoodsBundle\Entities\Items;
use CompanysBundle\Ego\CompanysActivationEgo;
use DistributionBundle\Services\DistributorService;
use DistributionBundle\Entities\DistributorItems;

use Exception;

class SyncInventoryCommand extends Command
{
    use CheckPromotionsValid;

    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'wdt:sync_inventory';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '同步旺店通ERP库存';

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

            $this->syncInventory($setting);
        }

        return true;
    }

    /**
     * @param $setting
     * @return void
     */
    private function syncInventory($setting)
    {
        $wdtErpClient = new WdtErpClient(config('wdterp.api_base_url'), $setting['sid'], $setting['app_key'], $setting['app_secret']);
        $companyId = $setting['company_id'];
        //获取参与活动中的货品ID
        $activityItems = $this->getActivityItems($companyId);
        $itemsService = new ItemsService();
        $itemStoreService = new ItemStoreService();

        $activityBns = [];
        if ($activityItems) {
            //获取活动商品BN
            $activityItemList = $itemsService->getItemsList(['item_id' => $activityItems, 'company_id' => $companyId]);
            //取出参与活动中的商品BN
            foreach ($activityItemList['list'] as $activityItem) {
                $activityBns[] = $activityItem['item_bn'];
            }
        }

        $company = (new CompanysActivationEgo())->check($companyId);
        $distributorService = new DistributorService();

        $conn = app('registry')->getConnection('default');
        $position = 0;
        do {
            $waitSyncResult = $this->getSelfWaitSyncIdListOpen($wdtErpClient, $position);
            if ($waitSyncResult['id_list']) {
                foreach ($waitSyncResult['id_list'] as $recId) {
                    $stockInfo = $this->getCalcStock($wdtErpClient, $recId);
                    if (empty($stockInfo)) {
                        continue;
                    }

                    // 活动内商品不更新
                    if (in_array($stockInfo->match_code, $activityBns)) {
                        continue;
                    }

                    $filter = ['company_id' => $companyId, 'item_bn' => $stockInfo->match_code];
                    $itemInfo = $itemsService->getItem($filter);
                    if (empty($itemInfo)) {
                        $this->setSyncAck($wdtErpClient, $recId, $stockInfo, true);
                        continue;
                    }

                    $itemId = $itemInfo['item_id'];
                    $quantity = $stockInfo->syn_stock;
                    $criteria = $conn->createQueryBuilder();
                    $criteria->select('sum(i.num)')
                        ->from('orders_normal_orders_items', 'i')
                        ->leftJoin('i', 'orders_normal_orders', 'o', 'i.order_id = o.order_id')
                        ->andWhere($criteria->expr()->eq('i.item_id', $itemId))
                        ->andWhere($criteria->expr()->andX(
                            $criteria->expr()->eq('o.order_status', $criteria->expr()->literal('NOTPAY')),
                            $criteria->expr()->gt('o.auto_cancel_time', time())
                        ));

                    $freeZ = $criteria->execute()->fetchColumn();
                    $quantity -= $freeZ;

                    if ($stockInfo->shop_id == $setting['shop_id'] || $company['product_model'] != 'standard') {
                        $result = $itemStoreService->saveItemStore($itemId, $quantity);
                        if ($result) {
                            $itemsRepository = app('registry')->getManager('default')->getRepository(Items::class);
                            $result = $itemsRepository->updateStore($itemId, $quantity);
                        }
                    } else {
                        $distributorInfo = $distributorService->getInfoSimple(['company_id' => $companyId, 'wdt_shop_id' => $stockInfo->shop_id]);
                        if (empty($distributorInfo)) {
                            $this->setSyncAck($wdtErpClient, $recId, $stockInfo, true);
                            continue;
                        }
                        $result = $itemStoreService->saveItemStore($itemId, $quantity, $distributorInfo['distributor_id']);
                        if ($result) {
                            $distributorItemsRepository = app('registry')->getManager('default')->getRepository(DistributorItems::class);
                            $filter = [
                                'item_id' => $itemId,
                                'distributor_id' => $distributorInfo['distributor_id']
                            ];
                            $result = $distributorItemsRepository->updateOneBy($filter, ['store' => $quantity]);
                        }
                    }

                    $this->setSyncAck($wdtErpClient, $recId, $stockInfo, $result);
                }
            }
            $position = $waitSyncResult['position'];
        } while (count($waitSyncResult['id_list']) > 0);
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $recId
     * @param $stockInfo
     * @param $result
     * @return void|null
     */
    private function setSyncAck(WdtErpClient $wdtErpClient, $recId, $stockInfo, $result)
    {
        $method = $result ? config('wdterp.methods.store_sync_success') : config('wdterp.methods.store_sync_fail');
        $infoMap = new \stdClass();
        $infoMap->syn_stock = $stockInfo->syn_stock;
        $infoMap->stock_change_count = $stockInfo->stock_change_count;
        $infoMap->stock_syn_rule_id = $stockInfo->stock_syn_rule_id;
        $infoMap->stock_syn_rule_no = $stockInfo->stock_syn_rule_no;
        $infoMap->stock_syn_other = '';
        $infoMap->stock_syn_warehouses = $stockInfo->stock_syn_warehouses;
        $infoMap->stock_syn_mask = $stockInfo->stock_syn_mask;
        $infoMap->stock_syn_percent = $stockInfo->stock_syn_percent;
        $infoMap->stock_syn_plus = $stockInfo->stock_syn_plus;
        $infoMap->stock_syn_min = $stockInfo->stock_syn_min;
        $infoMap->stock_syn_max = $stockInfo->stock_syn_max;
        $infoMap->is_auto_listing = $stockInfo->is_auto_listing;
        $infoMap->is_auto_delisting = $stockInfo->is_auto_delisting;
        $infoMap->is_syn_success = $result ? 1 : 0;
        $infoMap->is_manual = 1;
        $infoMap->syn_result = $result ? '库存同步成功' : '库存同步失败';
        try {
            app('log')->debug('SyncInventoryCommand=>method:'.$method.",request:\r\n". var_export($infoMap, 1));
            $result = $wdtErpClient->call($method, $recId, $infoMap);
            app('log')->debug('SyncInventoryCommand=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
            return null;
        }
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $recId
     * @return null
     */
    private function getCalcStock(WdtErpClient $wdtErpClient, $recId)
    {
        $method = config('wdterp.methods.store_query');
        try {
            app('log')->debug('SyncInventoryCommand=>method:'.$method.",request:\r\n". var_export(['recId' => $recId], 1));
            $result = $wdtErpClient->call($method, $recId, false);
            app('log')->debug('SyncInventoryCommand=>method:'.$method.",result:\r\n". var_export($result, 1));
            return $result;
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
            return null;
        }
    }

    /**
     * @param WdtErpClient $wdtErpClient
     * @param $position
     * @param $count
     * @return array
     */
    private function getSelfWaitSyncIdListOpen(WdtErpClient $wdtErpClient, $position, $count = 100)
    {
        $id_list = [];
        try {
            $method = config('wdterp.methods.stock_get_wait_sync');
            app('log')->debug('SyncInventoryCommand=>method:'.$method.",request:\r\n". var_export(['count' => $count, 'position' => $position], 1));
            $result = $wdtErpClient->call($method, $count, $position);
            $id_list = $result->id_list;
            $position = $result->position;
            app('log')->debug('SyncInventoryCommand=>method:'.$method.",result:\r\n". var_export($result, 1));
        } catch (Exception $e) {
            app('log')->debug('旺店通请求失败:'. $e->getMessage());
        }

        return [
            'id_list' => $id_list,
            'position' => $position,
        ];
    }
}

