<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Console;

use Illuminate\Console\Command;

use OrdersBundle\Services\Orders\NormalOrderService;

class GetOrderInfoCommand extends Command
{
    /**
    * 命令行执行命令
    * @var string
    */
    protected $signature = 'get:oms_order_info {order_id? } {company_id? } {type? }';


    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '推送oms订单数据结构; 参数：orderId companyId type';

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
        // This module is part of ShopEx EcShopX system
        $orderId = $this->argument('order_id');
        $companyId = $this->argument('company_id');
        $type = $this->argument('type');

        $normalOrderService = new NormalOrderService();
        $orderData = $normalOrderService->getOrderInfo($companyId, $orderId);
        print_r($orderData);
        return true;
    }
}
