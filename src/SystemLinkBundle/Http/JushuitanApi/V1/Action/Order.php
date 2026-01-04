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

namespace SystemlinkBundle\Http\JushuitanApi\V1\Action;

use Illuminate\Http\Request;
use SystemLinkBundle\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Traits\GetOrderServiceTrait;

class Order extends Controller
{
    use GetOrderServiceTrait;

    /**
     * 订单发货
     */
    public function orderDelivery($companyId, Request $request)
    {
        $params = $request->post();
        app('log')->debug('jushuitan::callback::orderDelivery::params=>:', $params);
        $rules = [
            'so_id'   => ['required', '订单号缺少！'],
            'l_id' => ['required', '缺少物流单号'],
            'lc_id' => ['required', '缺少物流公司编码'],
            'items' => ['required', '缺少发货商品'],
        ];

        $errorMessage = validator_params($params, $rules);
        if($errorMessage) {
            $this->api_response_shuyun('fail', $errorMessage);
        }

        $result = $this->doOrderDelivery($companyId, $params);
        app('log')->debug('jushuitan::callback::orderDelivery::result=>:', $result);
        $this->api_response_shuyun('true', '发货成功');
    }

    public function doOrderDelivery($companyId, $params)
    {
        try {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $params['so_id']);
            if (!$order)
            {
                app('log')->debug('jushuitan::callback::orderDelivery::此订单不存在:so_id:'.$params['so_id']);
                $this->api_response_shuyun('fail', '此订单不存在');
            }

            if ($order['delivery_status'] == 'DONE')
            {
                app('log')->debug('jushuitan::callback::orderDelivery::订单已发货，请勿重复发货:so_id:'.$params['so_id']);
                $this->api_response_shuyun('fail', '订单已发货，请勿重复发货');
            }

            $orderService = $this->getOrderServiceByOrderInfo($order);
            $orderList = $orderService->getOrderList(['company_id'=>$order['company_id'], 'order_id'=>$order['order_id']], -1);
            $order = $orderList['list'][0];

            // $productBn = array_column($params['items'], null, 'sku_id');
            $outerOiId = array_column($params['items'], null, 'outer_oi_id');
            $deliveryCode = $params['l_id'];
            $deliveryCorp = $params['lc_id'];

            $sepInfo = $isDelivery = $noDelivery = $emptyDelivery = [];
            foreach ($order['items'] as $items) {
                if($items['delivery_status'] == 'PENDING'){
                    // if(in_array($items['item_bn'], $productBn)){
                    // if(isset($productBn[$items['item_bn']])){
                    if(isset($outerOiId[$items['id']])){
                        $items['delivery_code'] = $deliveryCode;
                        $items['delivery_corp'] = $deliveryCorp;
                        $items['delivery_num'] = $outerOiId[$items['id']]['qty'];
                        $noDelivery[] = $items;
                    }else{
                        $emptyDelivery[] = $items;
                    }
                     
                }elseif($items['delivery_status'] == 'DONE'){
                    $isDelivery[] = $items;
                }
            }
            if(empty($noDelivery) && !empty($emptyDelivery)){
                app('log')->debug("jushuitan::callback::orderDelivery::emptyDelivery=>", $emptyDelivery);
                $this->api_response_shuyun('fail', '发货商品有误');
            }
            // if(empty($isDelivery)){
            //     $sepInfo = $noDelivery;
            // }else{
            //     $sepInfo = array_merge($noDelivery, $isDelivery);
            // }
            $sepInfo = $noDelivery;
            if(empty($sepInfo)){
                app('log')->debug("jushuitan::callback::orderDelivery::没有未发货的商品");
                $this->api_response_shuyun('fail', '发货商品有误');
            }
            $deliveryParams = [
                'type' => 'new',
                'company_id' => $order['company_id'],
                'delivery_code' => $deliveryCode,
                'delivery_corp' => $deliveryCorp,
                'delivery_type' => 'sep',
                'order_id' => $order['order_id'],
                'sepInfo' => json_encode($sepInfo),
            ];
            app('log')->debug("jushuitan::callback::orderDelivery::".__FUNCTION__.__LINE__. "::delivery_params=>", $deliveryParams);
            $result = $orderService->delivery($deliveryParams);
            app('log')->debug("jushuitan::callback::orderDelivery::".__FUNCTION__.__LINE__. "::result=>", $result);
            return $result;
        } catch (\Exception $e) {
            $msg = $e->getLine().",msg=>".$e->getMessage();
            app('log')->debug("jushuitan::callback::orderDelivery::发货失败:".__FUNCTION__.__LINE__. "::msg=>", $msg);
            return false;
        }
    }
}
