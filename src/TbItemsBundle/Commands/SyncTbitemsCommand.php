<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
