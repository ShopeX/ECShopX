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

namespace TbItemsBundle\Commands;

use TbItemsBundle\Services\TbItemsService;
use Illuminate\Console\Command;

class SyncTbitemsCommand extends Command
{

    protected $signature = 'tbitems:sync {company_id}';

    protected $description = '同步淘宝商品';

    public function handle()
    {
        // FIXME: check performance
        $companyId = $this->argument('company_id');
        try {
            (new TbItemsService($companyId))->syncItemsCategory() // 分类、类目 
                ->newSyncTbItems() // 同步淘宝商品
                ->newSyncTbSkus() // 同步淘宝商品sku
                ->getItemsAttributes() // 商品属性 
                ->getItemsCategory() // 店铺自定义分类
                ->getItemsBaseData() // 基础数据 
                ->syncItemsRelation() // 同步商品和绑定关系 
                ->fillItemsBaseData(); // 填充基础数据
        } catch (\Exception $e) {
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $e->getFile() . $e->getLine() . $e->getMessage());
        }

        return true;
    }

}
