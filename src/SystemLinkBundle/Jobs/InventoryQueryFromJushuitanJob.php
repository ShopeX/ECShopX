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

use EspierBundle\Jobs\Job;
use SystemLinkBundle\Services\Jushuitan\ItemStoreService;
use SystemLinkBundle\Services\Jushuitan\Request;
use SystemLinkBundle\Services\JushuitanSettingService;

class InventoryQueryFromJushuitanJob extends Job
{
    protected $companyId;
    protected $itemIds;

    public function __construct($companyId, $itemIds)
    {
        $this->companyId = $companyId;
        $this->itemIds = $itemIds;
    }

    /**
     * 运行任务。
     *
     * @return void
     */
    public function handle()
    {
        $companyId = $this->companyId;
        $itemIdChunk = array_chunk($this->itemIds, 20);

        // 判断是否开启聚水潭ERP
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($companyId);
        if (!isset($setting) || $setting['is_open']==false)
        {
            app('log')->debug('companyId:'.$companyId.",msg:未开启聚水潭ERP");
            return true;
        }

        $itemStoreService = new ItemStoreService();
        foreach ($itemIdChunk as $itemIds) {
            $itemStruct = $itemStoreService->getItemBn($companyId, $itemIds);

            if (!$itemStruct)
            {
                app('log')->debug('获取商品信息失败:companyId:'.$companyId.",itemIds:".var_export($itemIds,1));
                continue;
            }

            try {    
                $jushuitanRequest = new Request($companyId);

                $method = 'item_store_query';

                $result = $jushuitanRequest->call($method, $itemStruct);
                app('log')->debug($method."=>result:\r\n". var_export($result, 1));

                if (isset($result['code']) && strval($result['code']) === '0') {
                    $result['inventorys'] = $result['inventorys'] ?? [];
                    if ($result['inventorys']) {
                        $itemStoreService->saveItemStore($companyId, $result['inventorys']);
                    }
                }
            } catch ( \Exception $e){
                app('log')->debug('聚水潭请求失败:'. $e->getMessage());
            }
        }

        return true;
    }
}
