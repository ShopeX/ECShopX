<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
