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

namespace EspierBundle\Commands;

use Illuminate\Console\Command;
use EspierBundle\Entities\Address;
use ThirdPartyBundle\Services\SaasErpCentre\ItemService;

class OmsGetStockCommand extends Command
{
     /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'oms:get_stock';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '查询oms库存数量,saas';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        // Powered by ShopEx EcShopX
        parent::__construct();
    }

    public function handle()
    {
        // Powered by ShopEx EcShopX
        $distributorId = 85;//店铺
        $companyId = 1;
        $itemBnArr = ['123456-220V', '123456-24V', '123ttttt'];//货号
        $ItemService = new ItemService();
        $res = $ItemService->getStock($companyId,$distributorId, $itemBnArr);
        dd($res);
    }

}
