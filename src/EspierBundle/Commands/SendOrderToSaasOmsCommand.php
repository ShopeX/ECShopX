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

use ThirdPartyBundle\Events\TradeUpdateEvent as SaasErpUpdateEvent;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;
use OrdersBundle\Services\OrderService;
use OrdersBundle\Services\Orders\NormalOrderService;

class SendOrderToSaasOmsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'oms:send_order_saas {company_id} {order_id} {type}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '推送订单到oms，矩阵oms';

    const METHOD     = 'store.trade.update';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $company_id = $this->argument('company_id');//获取命令参数
        $order_id = $this->argument('order_id');//获取命令参数
        $type = $this->argument('type');//获取命令参数
        
        if ($type == 'event') {
            $OrderService = new OrderService(new NormalOrderService());
            $detail = $OrderService->getOrderInfo($company_id, $order_id);
            event(new SaasErpUpdateEvent($detail['orderInfo']));
            return;
        }

        if( ! $company_id) exit('company_id required');
        if( ! $order_id) exit('order_id required');
        $order_class = 'normal';

        $params = [
            'company_id' => $company_id,
            'order_id' => $order_id,
            'order_class' => $order_class,
            'user_id' => 0,
        ];
        $this->send($params);
    }

    public function send($params)
    {
        $companyId = $params['company_id'];
        $orderId = $params['order_id'];

        // 判断是否绑定了erp
        $certService = new CertService(false,$companyId);
        $erp_node_id = $certService->getErpBindNode();
        if (!$erp_node_id)
        {
            echo('saaserp TradeUpdateSendSaasErp companyId:'.$companyId.",orderId:".$orderId.",msg:未开启SaasErp\n");
            return true;
        }

        $orderService = new \ThirdPartyBundle\Services\SaasErpCentre\OrderService();
        $sourceType = ($params['order_class'] != 'normal' ? 'normal_' : '').$params['order_class'];
        switch ($sourceType)
        {
            case 'normal_seckill':
            case 'normal':
                $orderStruct = $orderService->getOrderStruct($companyId, $orderId, $sourceType);
                if (!$orderStruct )
                {
                    echo('saaserp TradeUpdateSendSaasErp 获取订单信息失败:companyId:'.$companyId.",orderId:".$orderId.",sourceType:".$sourceType."\n");
                    return true;
                }

                self::request($orderStruct, $companyId);
                break;
        }
    }

    static function request($orderStruct=[], $companyId=null)
    {
        try {
            $request = new \ThirdPartyBundle\Services\SaasErpCentre\Request($companyId);
            $result = $request->call(self::METHOD, $orderStruct);
        } catch ( \Exception $e){
            $errorMsg = "saaserp TradeUpdateSendSaasErp method=>".self::METHOD." Error on line ".$e->getLine()." in ".$e->getFile().": <b>".$e->getMessage()."\n";
            echo('saaserp TradeUpdateSendSaasErp 请求失败:'. $errorMsg);
        }
        return $result;
    }

}
