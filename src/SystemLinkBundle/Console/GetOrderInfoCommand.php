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
