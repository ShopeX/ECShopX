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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;

use ThirdPartyBundle\Http\Controllers\Controller as Controller;


use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\OrderService;

use OrdersBundle\Traits\GetOrderServiceTrait;

class Delivery extends Controller
{
    use GetOrderServiceTrait;

    /**
     * SaasErp 发货/创建退货单（不做处理）
     */
    public function createDelivery(Request $request)
    {
        // ShopEx EcShopX Core Module
        app('log')->info("saaserp DeliveryAction,".__FUNCTION__.",".__LINE__."\n");
        $params = $request->all();
        app('log')->info("saaserp Delivery,".__FUNCTION__.",".__LINE__.",params=>".var_export($params, 1)."\n");
        $rules = [
            'order_id' => ['required', '订单号缺少！'],
            'logi_code' => ['required_if:delivery_type,delivery', '缺少物流编码'],
            'logi_no' => ['required', '缺少物流单号'],
            'struct' => ['required', '缺少发货商品'],
        ];
        app('log')->info("saaserp DeliveryAction,".__FUNCTION__.",".__LINE__."\n");
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            $this->api_response('fail', $errorMessage);
        }
        $params['struct'] = json_decode($params['struct'], 1);

        app('log')->info("saaserp DeliveryAction,".__FUNCTION__.",".__LINE__."\n");

        $orderService = new OrderService(new NormalOrderService());
        $filter = ['order_id' => $params['order_id']];
        if ($this->companyId) {
            $filter['company_id'] = $this->companyId;
        }
        $tradeInfo = $orderService->getInfo($filter);
        if (!$tradeInfo) {
            $this->api_response('fail', '此订单不存在');
        }
        app('log')->info("\n saaserp DeliveryAction,".__FUNCTION__.",".__LINE__.",tradeInfo====>".var_export($tradeInfo, 1));

        // if ( $params['delivery_type'] == 'delivery' && $tradeInfo['delivery_status']=='DONE'){
        //     app('log')->info("saaserp 发货".__FUNCTION__.",".__LINE__."\n");
        //     $this->api_response('fail', '订单已发货，请勿重复发货');
        // }elseif( $params['delivery_type'] == 'return' && $tradeInfo['order_status'] == 'CANCEL'){
        //     $this->api_response('fail', '订单已经退款，请勿重复退款');
        // }
        switch ($params['delivery_type']) {
            case 'delivery':
                if ($tradeInfo['delivery_status'] == 'DONE') {
                    $this->api_response('fail', '订单已发货，请勿重复发货');
                }
                $order_list = $orderService->getOrderList(['company_id' => $tradeInfo['company_id'],'order_id' => $tradeInfo['order_id']], -1);
                $order = $order_list['list'][0];
                unset($order_list);
                app('log')->info("saaserp DeliveryAction,".__FUNCTION__.",".__LINE__."\n");
                if (!$order) {
                    $this->api_response('fail', '获取订单信息失败');
                }

                $result = $this->doOrderDelivery($order, $params);
                unset($order,$params);
                break;
            case 'return':
                // if($tradeInfo['order_status'] == 'CANCEL'){
                //     $this->api_response('fail', '订单已经退款，请勿重复退款');
                // }
                // $this->api_response('true', '退货，确认收货成功');
                app('log')->info("\nsaaserp DeliveryAction,".__FUNCTION__.",".__LINE__.",退货，确认收货成功");
                $result = true;
                break;
            default:
                $this->api_response('fail', '不支持发货类型：' . $params['delivery_type']);
                break;
        }
        if ($result) {
            $this->api_response('true', '操作成功', $result);
        } else {
            $this->api_response('fail', '操作失败');
        }
    }

    public function doOrderDelivery($order, $data)
    {
        // ModuleID: 76fe2a3d
        try {
            //[{"item_id": "", "item_type": "", "product_bn": "172817212", "product_name": "172817212-OMS\\u6d4b\\u8bd5...", "number": "2"}]
            $delivery_bn = array_column($data['struct'], 'product_bn');
            foreach ($data['struct'] as $row) {
                if (!isset($delivery_num[$row['product_bn']])) {
                    $delivery_num[$row['product_bn']] = 0;
                }
                $delivery_num[$row['product_bn']] += $row['number'];
            }
            $ship_mobile = $data['ship_mobile'];
            $logi_name = $data['logi_name'];
            $delivery_code = $data['logi_no'];
            $delivery_corp = $data['logi_code'];
            unset($data);
            $sepInfo = $isDelivery = $noDelivery = $emptyDelivery = [];
            foreach ($order['items'] as $key => $items) {
                if ($items['delivery_status'] == 'PENDING') {

                    //更新发货数量
                    $items['delivery_item_num'] = floatval($items['delivery_item_num']);
                    if (isset($delivery_num[$items['item_bn']]) && $delivery_num[$items['item_bn']] > 0) {
                        $ship_num = $delivery_num[$items['item_bn']];
                        if ($items['num'] - $items['delivery_item_num'] < $ship_num) {
                            $ship_num = $items['num'] - $items['delivery_item_num'];
                        }
                        $delivery_num[$items['item_bn']] -= $ship_num;
                        $items['delivery_item_num'] += $ship_num;
                        $items['delivery_num'] = $ship_num;
                    }

                    //判断发货状态
                    if ($items['delivery_item_num'] >= $items['num']) {
                        $items['delivery_status'] = 'DONE';
                    }

                    // 兼容老数据
                    if (in_array($items['item_bn'], $delivery_bn)) {
                        $items['delivery_code'] = $delivery_code;
                        $items['delivery_corp'] = $delivery_corp;
                        $noDelivery[] = $items;
                    } else {
                        $emptyDelivery[] = $items;
                    }
                } elseif ($items['delivery_status'] == 'DONE') {
                    //$isDelivery[] = $items;
                }
            }
            if (empty($noDelivery) && !empty($emptyDelivery)) {
                app('log')->debug("\nsaaserp ".$order['order_id']." 没有发货信息 ".__FUNCTION__.__LINE__.",emptyDelivery=>".json_encode($emptyDelivery));
                $this->api_response('fail', '发货商品有误');
            }
            /*
            if (empty($isDelivery)) {
                $sepInfo = $noDelivery;
            } else {
                $sepInfo = array_merge($noDelivery, $isDelivery);
            }*/
            $sepInfo = $noDelivery;
            if (empty($sepInfo)) {
                //app('log')->debug("\nsaaserp ".$order['order_id']." 没有发货信息 ".__FUNCTION__.__LINE__ );
                $this->api_response('fail', '订单已经发货完成');
                return false;
            }
            $delivery_params = [
                'company_id' => $order['company_id'],
                'logi_name' => $logi_name,
                'ship_mobile' => $ship_mobile,
                'delivery_corp' => $delivery_corp,
                'delivery_code' => $delivery_code,
                'delivery_type' => 'sep',
                'order_id' => $order['order_id'],
                'sepInfo' => json_encode($sepInfo, 256),
                'type' => 'new',
            ];
            app('log')->debug("\nsaaserp 去发货 ".__FUNCTION__.__LINE__. " delivery_params=>".var_export($delivery_params, 1));


            $orderService = new OrderService(new NormalOrderService());
            $result = $orderService->delivery($delivery_params);
            return $result;
        } catch (\Exception $e) {
            $msg = $e->getLine().",msg=>".$e->getMessage();
            app('log')->debug("\nsaaserp 发货失败 ".__FUNCTION__.__LINE__. " msg=>".$msg );
            $this->api_response('fail', $msg);
            return false;
        }
    }

    public function doOrderDeliveryOld($order, $data)
    {
        // ModuleID: 76fe2a3d
        try {
            $delivery_bn = array_column($data['struct'], 'product_bn');
            $delivery_code = $data['logi_no'];
            $delivery_corp = $data['logi_code'];
            unset($data);
            $sepInfo = $isDelivery = $noDelivery = $emptyDelivery = [];
            foreach ($order['items'] as $key => $items) {
                if ($items['delivery_status'] == 'PENDING') {
                    if (in_array($items['item_bn'], $delivery_bn)) {
                        $items['delivery_code'] = $delivery_code;
                        $items['delivery_corp'] = $delivery_corp;
                        $noDelivery[] = $items;
                    } else {
                        $emptyDelivery[] = $items;
                    }
                } elseif ($items['delivery_status'] == 'DONE') {
                    $isDelivery[] = $items;
                }
            }
            if (empty($noDelivery) && !empty($emptyDelivery)) {
                app('log')->debug("\nsaaserp ".$order['order_id']." 没有发货信息 ".__FUNCTION__.__LINE__.",emptyDelivery=>".json_encode($emptyDelivery));
                $this->api_response('fail', '发货商品有误');
            }
            if (empty($isDelivery)) {
                $sepInfo = $noDelivery;
            } else {
                $sepInfo = array_merge($noDelivery, $isDelivery);
            }
            if (empty($sepInfo)) {
                app('log')->debug("\nsaaserp ".$order['order_id']." 没有发货信息 ".__FUNCTION__.__LINE__);
                return false;
            }
            $delivery_params = [
                'company_id' => $order['company_id'],
                'delivery_corp' => '',
                'delivery_code' => '',
                'delivery_type' => 'sep',
                'order_id' => $order['order_id'],
                'sepInfo' => json_encode($sepInfo),
            ];
            app('log')->debug("\nsaaserp 去发货 ".__FUNCTION__.__LINE__. " delivery_params=>".var_export($delivery_params, 1));
            $orderService = new OrderService(new NormalOrderService());
            $result = $orderService->delivery($delivery_params);
            return $result;
        } catch (\Exception $e) {
            $msg = $e->getLine().",msg=>".$e->getMessage();
            app('log')->debug("\nsaaserp 发货失败 ".__FUNCTION__.__LINE__. " msg=>".$msg);
            return false;
        }
    }

    /*
     * 商家确认收货  暂时不需要此接口 写上只是为了防止报错
     */
    public function returnDelivery(Request $request)
    {
        $params = $request->all();
        $this->api_response('true', '确认收货成功', $params);
    }
}
