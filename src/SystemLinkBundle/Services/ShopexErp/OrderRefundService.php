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

namespace SystemLinkBundle\Services\ShopexErp;

use OrdersBundle\Services\Orders\NormalOrderService;

use OrdersBundle\Traits\GetOrderServiceTrait;

use AftersalesBundle\Services\AftersalesService;
use AftersalesBundle\Services\AftersalesRefundService;



use Exception;

class OrderRefundService
{
    use GetOrderServiceTrait;

    /**
     * 生成发给 oms 退款申请单数据
     *
     */
    public function getOrderRefundInfo($companyId, $orderId, $refundBn, $userId)
    {
        if ($refundBn) {
            //售后单产生的退款
            $filter = ['refund_bn' => $refundBn, 'company_id' => $companyId];
        } else {
            //直接取消订单产生的退款
            $filter = ['order_id' => $orderId, 'company_id' => $companyId, 'user_id' => $userId];
        }
        $aftersalesService = new AftersalesService();
        $refundInfo = $aftersalesService->aftersalesRefundRepository->getInfo($filter);
        if (!$refundInfo) {
            throw new Exception("退款单获取失败");
        }

        //单货品退款 获取单个货品的退款金额
        if (isset($refundInfo) && $refundInfo['aftersales_bn']) {
            //获取售后申请单
            $afterInfo = $aftersalesService->aftersalesRepository->get(['aftersales_bn' => $refundInfo['aftersales_bn']]);
            app('log')->debug('OrderRefundService_afterInfo=>:'.var_export($afterInfo, 1));
            if (!$afterInfo) {
                throw new Exception("售后获取失败");
            }
        }

        app('log')->debug('ome_refundInfo=>:'.var_export($refundInfo, 1));

        //组织ome 退款申请单
        $refundOmeData = [
            'refund_type' => 'apply',
            'status' => '0',
            'node_version' => '2.0',
            't_received' => '', //买家确认收款时间
            'paymethod' => '',
            'pay_account' => '',
            'memo' => isset($afterInfo['reason']) ? $afterInfo['reason'].'('.$afterInfo['description'].')' : $refundInfo['refunds_memo'],
            'currency' => $refundInfo['currency'],
            'payment' => '',
            'bank' => '',
            't_sent' => $refundInfo['refund_success_time'],  //商家发款时间
            't_ready' => $refundInfo['create_time'], //创建退款单时间
            'trade_no' => $refundInfo['trade_id'],
            // 'pay_type' => $refundInfo['pay_type'],
            'pay_type' => 'online',
            'account' => '',
            'cur_money' => bcdiv($refundInfo['refund_fee'], 100, 2),
            'refund_bn' => $refundInfo['refund_bn'],
            'order_bn' => $refundInfo['order_id'],
            'money' => bcdiv($refundInfo['refund_fee'], 100, 2),
        ];

        return $refundOmeData;
    }

    /**
     * 无售后单的情况下拒绝退款
     *
     */
    public function toRefund($type, $params, $afterInfo)
    {
        // $refundFilter = [
        //     'company_id' => $refundData['company_id'],
        //     'refund_bn' => $refundData['refund_bn'],
        // ];

        // $refundUpdate = [
        //     'refund_status' => 'REFUNDCLOSE',
        //     'update_time' => time(),
        //     'refunds_memo' => $refundData['refunds_memo']
        // ];

        // $aftersalesRefundService = new AftersalesRefundService();

        // return $aftersalesRefundService->aftersalesRefundRepository->updateOneBy($refundFilter, $refundUpdate);

        $normalOrderService = new NormalOrderService();
        $orderData = $normalOrderService->getOrderInfo($afterInfo['company_id'], $params['tid']);

        if (!$orderData) {
            throw new Exception("订单信息获取失败");
        }

        $orderType = 'normal';

        if ($type == 'refuseRefund') {
            //拒绝退款
            $refundData = [
                'check_cancel' => 0,
                'company_id' => $afterInfo['company_id'],
                'order_id' => $params['tid'],
                'refund_bn' => $afterInfo['refund_bn'],
                'shop_reject_reason' => $params['refuse_message'],
                'order_type' => $orderData['orderInfo']['order_type'],

            ];
        } else {
            //同意退款
            $refundData = [
                'check_cancel' => 1,
                'company_id' => $afterInfo['company_id'],
                'order_id' => $params['tid'],
                'refund_bn' => $params['refund_id'],
                'shop_reject_reason' => isset($params['memo']) ? trim($params['memo']) : '',
                'order_type' => $orderData['orderInfo']['order_type']
            ];
        }

        $orderService = $this->getOrderService($orderType);
        $result = $orderService->confirmCancelOrder($refundData);
        app('log')->debug('OrderRefundService_toRefund_refundData=>:'.var_export($refundData, 1));
        app('log')->debug('OrderRefundService_toRefund_result=>:'.var_export($result, 1));
        return $result;
    }

    /**
     * 退款结果 回打OMS
     *
     */
    public function refundSendOme($data)
    {
        app('log')->debug('OrderRefundService_refundSendOme_data=>:'.var_export($data, 1));
        //获取退款申请单
        $aftersalesRefundService = new AftersalesRefundService();

        $filter = [
            'refund_bn' => $data['refund_bn'],
            'company_id' => $data['company_id'],
        ];

        $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($filter);
        app('log')->debug('OrderRefundService_refundSendOme_refundInfo=>:'.var_export($refundInfo, 1));
        //组织ome 确认退款单
        $refundData = [
            'refund_type' => 'refund',
            'status' => '4',
            'node_version' => '2.0',
            't_received' => '', //买家确认收款时间
            'paymethod' => '',
            'pay_account' => '',
            'memo' => $refundInfo['refunds_memo'],
            'currency' => $refundInfo['currency'],
            'payment' => '',
            'bank' => '',
            't_sent' => $refundInfo['refund_success_time'],  //商家发款时间
            't_ready' => $refundInfo['create_time'], //创建退款单时间
            'trade_no' => '',
            // 'pay_type' => $refundInfo['pay_type'],
            'pay_type' => 'online',
            'account' => '',
            'cur_money' => bcdiv($refundInfo['refund_fee'], 100, 2),
            'refund_bn' => $refundInfo['refund_bn'],
            'order_bn' => $refundInfo['order_id'],
            'money' => bcdiv($refundInfo['refund_fee'], 100, 2),
        ];

        return $refundData;
    }

    public function getAftersalesInfo($aftersales_bn)
    {
        if (!$aftersales_bn) {
            return false;
        }

        $aftersalesRefundService = new AftersalesRefundService();

        $afterInfo = $aftersalesRefundService->aftersalesRepository->get(['aftersales_bn' => $aftersales_bn]);

        return $afterInfo;
    }
}
