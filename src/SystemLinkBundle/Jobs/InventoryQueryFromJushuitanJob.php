<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
