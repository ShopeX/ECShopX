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

namespace OrdersBundle\Console;

use Illuminate\Console\Command;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\Trade;
use OrdersBundle\Listeners\PrinterOrder;

class PrinterOrderCommand extends Command
{
    /**
     * 命令行执行命令
     * @var string
     */
    protected $signature = 'order:print {company_id} {distributor_id} {--order_id= : 订单号} {--time_start= : 起始时间} {--time_end= : 结束时间}';

    /**
     * 命令描述
     *
     * @var string
     */
    protected $description = '打印商家订单';

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
        $filter['company_id'] = $this->argument('company_id');
        if (!$filter['company_id']) {
            $this->info('请输入company_id!');
            exit;
        }

        $filter['distributor_id'] = $this->argument('distributor_id');
        if (!$filter['distributor_id']) {
            $this->info('请输入distributor_id!');
            exit;
        }
        $filter['order_status'] = 'PAYED';
        $filter['order_type'] = 'normal';
        $filter['receipt_type'] = 'logistics';
        $filter['order_class|notin'] = ['drug', 'pointsmall'];
        $filter['cancel_status|in'] = ['NO_APPLY_CANCEL', 'FAILS'];

        if ($orderId = $this->option('order_id')) {
            $filter['order_id'] = $orderId;
        }

        if ($timeStart = $this->option('time_start')) {
            $filter['create_time|gte'] = strtotime($timeStart);
        }

        if ($timeEnd = $this->option('time_end')) {
            $filter['create_time|lte'] = strtotime($timeStart);
        }

        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $tradeRepository = app('registry')->getManager('default')->getRepository(Trade::class);
        $printerOrder = new PrinterOrder();
        $offset = 0;
        $limit = 20;
        $total = $normalOrdersRepository->count($filter);
        do {
            $orderList = $normalOrdersRepository->getList($filter, $offset, $limit, ['create_time' => 'ASC'], 'company_id,order_id');
            foreach ($orderList as $order) {
                $tradeFilter = [
                    'company_id' => $order['company_id'],
                    'order_id' => $order['order_id'],
                ];
                $trade = $tradeRepository->findOneBy($tradeFilter);
                if ($trade) {
                    $printerOrder->handle($trade);
                    $this->info($order['order_id'].'✅');
                }
            }
            $offset += $limit;
        } while($offset < intval($total));

        $this->info('打印完成');
    }
}