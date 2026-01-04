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

namespace SystemLinkBundle\Jobs;

use Exception;
use EspierBundle\Jobs\Job;
use SystemLinkBundle\Services\Jushuitan\ItemService;
use SystemLinkBundle\Services\Jushuitan\Request;
use SystemLinkBundle\Services\JushuitanSettingService;
use DistributionBundle\Services\DistributorService;

class UploadItemsToJushuitanJob extends Job
{
    protected $companyId;
    protected $itemIds;
    protected $distributorId;
    protected $itemType;

    public function __construct($companyId, $itemIds, $distributorId, $itemType)
    {
        // Powered by ShopEx EcShopX
        $this->companyId = $companyId;
        $this->itemIds = $itemIds;
        $this->distributorId = $distributorId;
        $this->itemType = $itemType;
    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {
        // Powered by ShopEx EcShopX
        $companyId = $this->companyId;
        app('log')->debug('jushuitan::UploadItemsToJushuitanJob::companyId:'.$companyId.",itemIds:".json_encode($this->itemIds).",distributorId:".$this->distributorId);
        $itemIdChunk = array_chunk($this->itemIds, 2);

        // 判断是否开启聚水潭ERP
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($companyId);
        if (!isset($setting) || $setting['is_open']==false)
        {
            app('log')->debug('jushuitan::UploadItemsToJushuitanJob::companyId:'.$companyId.",msg:未开启聚水潭ERP");
            return true;
        }
        $shopId = $setting['shop_id'];
        if ($this->distributorId > 0) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoSimple(['company_id' => $companyId, 'distributor_id' => $this->distributorId]);
            if (!$distributorInfo || !$distributorInfo['jst_shop_id']) {
                app('log')->debug('jushuitan::UploadItemsToJushuitanJob::companyId:'.$companyId.",msg:店铺没有绑定聚水潭ERP门店");
                return true;
            }

            $shopId = $distributorInfo['jst_shop_id'];
        }

        $itemService = new ItemService();
        $redisKey = 'JushuitanApiFlowControlLock';
        foreach ($itemIdChunk as $itemIds) {
            app('log')->info('itemIds====>'.json_encode($itemIds));
            do {
                $succ = app('redis')->setnx($redisKey, 1);
                if ($succ) {
                    app('redis')->expire($redisKey, 1);
                    break;
                }

                sleep(1);
            } while (!$succ);

            $itemStructs = $shopItemStructs = [];
            foreach ($itemIds as $itemId) {
                $itemStruct = $itemService->getItemStruct($companyId, $itemId, $this->distributorId, $shopId, $this->itemType);

                if (!$itemStruct)
                {
                    app('log')->debug('jushuitan::UploadItemsToJushuitanJob::获取商品信息失败:companyId:'.$companyId.",itemId:".$itemId);
                    continue;
                }
                $itemStructs = array_merge($itemStructs, $itemStruct['items']);
                $shopItemStructs = array_merge($shopItemStructs, $itemStruct['shop_items']);
                app('log')->debug('jushuitan::UploadItemsToJushuitanJob::itemStructs====>'.json_encode($itemStructs));
                app('log')->debug('jushuitan::UploadItemsToJushuitanJob::shopItemStructs====>'.json_encode($shopItemStructs));
            }
            if (!$itemStructs || !$shopItemStructs) {
                continue;
            }

            try {    
                $jushuitanRequest = new Request($companyId);

                $method = 'item_add';

                $result = $jushuitanRequest->call($method, ['items' => $itemStructs]);
                app('log')->debug('jushuitan::UploadItemsToJushuitanJob::'.$method."=>result:". json_encode($result));


                $method = 'shop_item_add';

                foreach ($shopItemStructs as $shopItemStruct) {
                    $result = $jushuitanRequest->call($method, ['items' => $shopItemStruct]);
                    app('log')->debug('jushuitan::UploadItemsToJushuitanJob::'.$method."=>result:". json_encode($result));
                }
            } catch ( \Exception $e){
                app('log')->debug('jushuitan::UploadItemsToJushuitanJob::聚水潭请求失败:'. $e->getMessage());
            }
        }

        return true;
    }
}
