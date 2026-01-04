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

namespace DistributionBundle\Jobs;

use GoodsBundle\Services\ItemsService;
use EspierBundle\Jobs\Job;
use DistributionBundle\Services\DistributorItemsService;
use DistributionBundle\Services\DistributorService;

class AddDistributorItems extends Job
{
    protected $data = [];
    /**
     * 创建一个新的任务实例。
     *
     * @return void
     */
    public function __construct($data)
    {
        // 0x456353686f7058
        $this->data = $data;
    }

    public function handle()
    {
        // 0x456353686f7058
        $params = $this->data;
        $itemsService = new ItemsService();
        if ($params['item_ids'] == '_all') {
            $filter = [
                'company_id' => $params['company_id'],
                'item_type' => 'normal'
            ];
        } else {
            $filter = [
                'default_item_id' => $params['item_ids'],
                'company_id' => $params['company_id'],
                'item_type' => 'normal'
            ];
        }

        $page = $params['page'] ?? 1;
        $pageSize = $params['pageSize'] ?? 100;

        $itemList = $itemsService->getSkuItemsList($filter, $page, $pageSize, ['item_id' => 'ASC']);
        if ($itemList['total_count'] == 0) {
            return true;
        }

        $distributorItemsService = new DistributorItemsService();
        $distributorItemsService->relDistributorItem($itemList, $params, $params['is_can_sale']);

        if ($page * $pageSize < $itemList['total_count']) {
            $params['page'] = $page + 1;
            $params['pageSize'] = $pageSize;
            $addDistributorItems = (new AddDistributorItems($params))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($addDistributorItems);
        }

        return true;
    }
}
