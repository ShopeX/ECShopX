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

namespace SystemLinkBundle\Services\Jushuitan;

use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Entities\OrdersRelJushuitan;
use AftersalesBundle\Services\AftersalesRefundService;

use Exception;

class OrderCancelService
{
    use GetOrderServiceTrait;

    /**
     * 生成发给退款申请单数据
     *
     */
    public function getOrderInfo($companyId, $orderId, $cancelReason)
    {
        // Powered by ShopEx EcShopX
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);

        if( !$order)
        {
            throw new Exception("获取订单信息失败");
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        // app('log')->debug('jushuitan_orderData=>:'.var_export($orderData,1));

        if( !$orderData)
        {
            throw new Exception("获取订单信息失败");
        }

        if (!in_array($orderData['orderInfo']['order_status'], ['WAIT_BUYER_CONFIRM', 'PAYED', 'DONE', 'REVIEW_PASS']))
        {
            throw new Exception("订单不是已支付状态");
        }

        if ((float)$orderData['tradeInfo']['payFee']<=0)
        {
            // throw new Exception("已支付金额为0，无需退款");
        }
        $ordersRelJushuitanRepository = app('registry')->getManager('default')->getRepository(OrdersRelJushuitan::class);

        $relData = $ordersRelJushuitanRepository->getInfo(['company_id' => $companyId, 'order_id' => $orderId]);
        if (!$relData) {
            throw new Exception("获取订单信息失败");
        }
        $cancelOrder = [
            'o_ids' => [$relData['o_id']],
            'cancel_type' => $cancelReason
        ];

        return $cancelOrder;
    }

    public function confirmCancelOrder($companyId, $orderId)
    {
        $orderAssociationService = new OrderAssociationService();
        $order = $orderAssociationService->getOrder($companyId, $orderId);

        if (!$order)
        {
            throw new Exception("获取订单信息失败");
        }

        $orderService = $this->getOrderServiceByOrderInfo($order);

        //直接取消订单产生的退款
        $aftersalesRefundService = new AftersalesRefundService();
        $refundInfo = $aftersalesRefundService->getInfo(['order_id'=>$orderId, 'company_id'=>$companyId, 'user_id'=>$order['user_id']]);
        if (!$refundInfo)
        {
            throw new Exception("获取退款单失败");
        }

        //同意退款
        $refundData = [
            'check_cancel' => 1,
            'company_id' => $companyId,
            'order_id' => $orderId,
            'refund_bn' => $refundInfo['refund_bn'],
            'order_type' => $order['order_type']
        ];
        $result = $orderService->confirmCancelOrder($refundData);
        app('log')->debug('OrderRefundService_toRefund_refundData=>:'.var_export($refundData,1));
        app('log')->debug('OrderRefundService_toRefund_result=>:'.var_export($result,1));

        return $result;
    }

}
