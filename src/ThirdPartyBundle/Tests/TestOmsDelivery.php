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

namespace ThirdPartyBundle\Tests;

use EspierBundle\Services\TestBaseService;

use ThirdPartyBundle\Http\ThirdApi\V1\Action\Delivery;


use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;

//php phpunit src\ThirdPartyBundle\Tests\TestOmsDelivery
class TestOmsDelivery extends TestBaseService
{
    public function test()
    {
        echo("\n".date('Ymd H:i:s')."\n");

        $params = array(
          'ac' => 'f9ca20226f3092065ad131189f6f6faf',
          'logi_code' => '80898990',
          'ship_area' => '上海/上海市/徐汇区',
          'date' => '2020-12-10 17:38:52',
          'op_name' => 'admin',
          'member_name' => '17521302310',
          'lastmodify' => '1607593132',
          'struct' => '[{"item_id": "", "item_type": "", "product_bn": "172817212", "product_name": "172817212-OMS\\u6d4b\\u8bd5...", "number": "1"}]',
          'ship_addr' => '宜山路700号C1-12F',
          'logi_id' => '3',
          'ship_name' => '叶子',
          'api_version' => '1.0',
          'status' => 'succ',
          'delivery_type' => 'delivery',
          'ship_email' => '',
          'order_id' => '3266701000310337',
          'memo' => '',
          'ship_mobile' => '17521302310',
          'logi_name' => '韵达',
          'ship_tel' => '',
          'ship_zip' => '200000',
          't_end' => '1607593132',
          'delivery' => '',
          'task' => '160759313245341279294941',
          'logi_no' => '2012100000003',
          'delivery_id' => '2012100000003',
          'is_protect' => 'false',
          't_begin' => '1607593132',
          'act' => 'ome_create_delivery',
          'return_data' => 'json',
        );

        $delivery = new Delivery();

        $params['struct'] = json_decode($params['struct'], 1);

        $orderService = new OrderService(new NormalOrderService());
        $filter = ['order_id' => $params['order_id']];
        $tradeInfo = $orderService->getInfo($filter);
        if (!$tradeInfo) {
            $this->api_response('fail', '此订单不存在');
        }


        // if ( $params['delivery_type'] == 'delivery' && $tradeInfo['delivery_status']=='DONE'){
        //     app('log')->info("saaserp 发货".__FUNCTION__.",".__LINE__."\n");
        //     $this->api_response('fail', '订单已发货，请勿重复发货');
        // }elseif( $params['delivery_type'] == 'return' && $tradeInfo['order_status'] == 'CANCEL'){
        //     $this->api_response('fail', '订单已经退款，请勿重复退款');
        // }
        switch ($params['delivery_type']) {
            case 'delivery':
                if ($tradeInfo['delivery_status'] == 'DONE') {
                    //$this->api_response('fail', '订单已发货，请勿重复发货');
                }
                $order_list = $orderService->getOrderList(['company_id' => $tradeInfo['company_id'],'order_id' => $tradeInfo['order_id']], -1);
                $order = $order_list['list'][0];
                unset($order_list);
                if (!$order) {
                    $this->api_response('fail', '获取订单信息失败');
                }

                $result = $delivery->doOrderDelivery($order, $params);
                unset($order,$params);
                break;
            case 'return':
                $result = true;
                break;
            default:
                break;
        }
        if ($result) {
            $this->api_response('true', '操作成功', $result);
        } else {
            var_dump($result);
            $this->api_response('fail', '操作失败');
        }
    }

    public function api_response($code, $msg)
    {
        echo($msg);
        exit;
    }
}
