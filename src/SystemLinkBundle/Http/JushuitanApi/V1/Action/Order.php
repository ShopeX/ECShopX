<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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

        $this->api_response_shuyun('true', '发货成功');
    }

    public function doOrderDelivery($companyId, $params)
    {
        try {
            $orderAssociationService = new OrderAssociationService();
            $order = $orderAssociationService->getOrder($companyId, $params['so_id']);
            if (!$order)
            {
                $this->api_response_shuyun('fail', '此订单不存在');
            }

            if ($order['delivery_status'] == 'DONE')
            {
                $this->api_response_shuyun('fail', '订单已发货，请勿重复发货');
            }

            $orderService = $this->getOrderServiceByOrderInfo($order);
            $orderList = $orderService->getOrderList(['company_id'=>$order['company_id'], 'order_id'=>$order['order_id']], -1);
            $order = $orderList['list'][0];

            $productBn = array_column($params['items'], null, 'sku_id');
            $deliveryCode = $params['l_id'];
            $deliveryCorp = $params['lc_id'];

            $sepInfo = $isDelivery = $noDelivery = $emptyDelivery = [];
            foreach ($order['items'] as $key => $items) {
                if($items['delivery_status'] == 'PENDING'){
                    // if(in_array($items['item_bn'], $productBn)){
                    if(isset($productBn[$items['item_bn']])){
                        $items['delivery_code'] = $deliveryCode;
                        $items['delivery_corp'] = $deliveryCorp;
                        $items['delivery_num'] = $productBn[$items['item_bn']]['qty'];
                        $noDelivery[] = $items;
                    }else{
                        $emptyDelivery[] = $items;
                    }
                     
                }elseif($items['delivery_status'] == 'DONE'){
                    $isDelivery[] = $items;
                }
            }
            if(empty($noDelivery) && !empty($emptyDelivery)){
                app('log')->debug("聚水潭 ".$order['order_id']." 没有发货信息 ".__FUNCTION__.__LINE__.",emptyDelivery=>".json_encode($emptyDelivery) );
                $this->api_response_shuyun('fail', '发货商品有误');
            }
            // if(empty($isDelivery)){
            //     $sepInfo = $noDelivery;
            // }else{
            //     $sepInfo = array_merge($noDelivery, $isDelivery);
            // }
            $sepInfo = $noDelivery;
            if(empty($sepInfo)){
                app('log')->debug("聚水潭 ".$order['order_id']." 没有发货信息 ".__FUNCTION__.__LINE__ );
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
            app('log')->debug("聚水潭 去发货 ".__FUNCTION__.__LINE__. " delivery_params=>".var_export($deliveryParams,1) );

            $result = $orderService->delivery($deliveryParams);
            return $result;
        } catch (\Exception $e) {
            $msg = $e->getLine().",msg=>".$e->getMessage();
            app('log')->debug("聚水潭 发货失败 ".__FUNCTION__.__LINE__. " msg=>".$msg );
            return false;
        }
    }
}
