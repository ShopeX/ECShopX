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

namespace ThirdPartyBundle\Services\SaasErpCentre;

// use Dingo\Api\Exception\ResourceException;

use OrdersBundle\Services\Orders\NormalOrderService;
// use GoodsBundle\Services\ItemsService;
// use MembersBundle\Services\MemberService;

use OrdersBundle\Traits\GetOrderServiceTrait;

use AftersalesBundle\Services\AftersalesRefundService;

// use OrdersBundle\Services\Orders\AbstractNormalOrder;


use Exception;

class OrderRefundService
{
    use GetOrderServiceTrait;

    /**
     * 生成发给ERP退款申请单数据
     *
     */
    public function getOrderRefundInfo($refundBn, $companyId, $orderId, $sourceType = 'normal', $aftersalesBn = null, $refundType = 'apply', $status = 'APPLY')
    {
        $orderService = $this->getOrderService($sourceType);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        app('log')->debug("saaserp ".__FUNCTION__."===".__LINE__." orderData=>:".json_encode($orderData)."\n");

        if (!$orderData) {
            throw new Exception("获取订单信息失败");
        }
        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];
        unset($orderData);
        if ($status == 'APPLY' && !in_array($orderInfo['order_status'], ['WAIT_BUYER_CONFIRM', 'PAYED', 'DONE', 'REVIEW_PASS'])) {
            throw new Exception("订单不是已支付状态");
        }

        if ($status == 'APPLY' && (float)$tradeInfo['payFee'] <= 0) {
            throw new Exception("已支付金额为0，无需退款");
        }

        $aftersalesRefundService = new AftersalesRefundService();
        // 备注
        $memo = '';
        //单货品退款 获取单个货品的退款金额
        if (isset($aftersalesBn) && $aftersalesBn) {
            //获取售后申请单
            $afterInfo = $aftersalesRefundService->aftersalesRepository->get(['aftersales_bn' => $aftersalesBn]);
            app('log')->debug("saaserp ".__FUNCTION__."===".__LINE__." afterInfo=>:".json_encode($afterInfo)."\n");
            if (!$afterInfo) {
                throw new Exception("售后获取失败");
            }

            //获取已经生成的退款单
            $filter = ['aftersales_bn' => $aftersalesBn, 'company_id' => $companyId, 'order_id' => $orderId];
            if ($refundBn) {
                $filter['refund_bn'] = $refundBn;
            }
            $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($filter);
            $memo = $afterInfo['reason'];
        } else {
            //直接取消订单产生的退款
            $refundFilter = [
                'order_id' => $orderInfo['order_id'],
                'company_id' => $orderInfo['company_id'],
                'user_id' => $orderInfo['user_id']
            ];
            if ($status == 'APPLY') {
                $refundFilter['refund_status'] = 'READY';
            } elseif ($status == 'SUCC') {
                $refundFilter['refund_bn'] = $refundBn;
            }
            $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($refundFilter);

            $cancelFilter = [
                'company_id' => $orderInfo['company_id'],
                'order_id' => $orderInfo['order_id'],
                'order_type' => $sourceType,
            ];
            $cancelInfo = $orderService->getCancelInfo($cancelFilter);
            $memo = $cancelInfo['cancel_reason'];
        }

        app('log')->debug("saaserp ".__FUNCTION__."===".__LINE__." refundInfo=>:".json_encode($refundInfo)."\n");


        //组织erp 退款申请单
        $refundData = [
            'refund_id' => $refundInfo['refund_bn'],
            'tid' => $orderInfo['order_id'],
            'buyer_bank' => '',
            'buyer_id' => $orderInfo['user_id'],
            'buyer_account' => '',
            'buyer_name' => '',
            'refund_fee' => bcdiv($refundInfo['refund_fee'], 100, 2),
            'currency' => $refundInfo['currency'],
            'currency_fee'  => bcdiv($refundInfo['pay_type'] == 'point' ? 0 : $refundInfo['cur_pay_fee'], 100, 2),
            'pay_type' => 'online',
            'payment_tid' => '',
            'payment_type' => '',
            'seller_account' => '',
            't_begin' => date('Y-m-d H:i:s', $refundInfo['create_time']),
            't_sent' => '',
            't_received' => '',
            'refund_type' => $refundType,
            'status' => $status,
            'memo' => $memo,
        ];
        if ($status == 'SUCC') {
            $refundData['t_received'] = date('Y-m-d H:i:s');
        }

        return $refundData;
    }


    /**
     * 退款结果 回打SaasErp
     *
     */
    public function refundSendSaasErp($data)
    {
        app('log')->debug('saaserp OrderRefundService_refundSendSaasErp_data=>:'.var_export($data, 1)."\n");
        //获取退款申请单
        $aftersalesRefundService = new AftersalesRefundService();

        $filter = [
            'order_id' => $data['order_id'],
            'company_id' => $data['company_id'],
            'aftersales_bn' => $data['aftersales_bn'],
        ];

        if (isset($data['item_id']) && $data['item_id']) {
            $filter['item_id'] = intval($data['item_id']);
        }
        $refundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo($filter);
        app('log')->debug("\nsaaserp ".__FUNCTION__.",".__LINE__.",refundInfo=>:".var_export($refundInfo, 1));
        $memo = "#".$data['refund_id']."#";
        //组织erp 退款申请单
        $refundData = [
            'refund_id' => $refundInfo['refund_bn'],
            'tid' => $refundInfo['order_id'],
            'buyer_bank' => '',
            'buyer_id' => $refundInfo['user_id'],
            'buyer_account' => '',
            'buyer_name' => '',
            'refund_fee' => bcdiv($refundInfo['refund_fee'], 100, 2),
            'currency' => $refundInfo['currency'],
            'currency_fee'  => bcdiv($refundInfo['pay_type'] == 'point' ? 0 : $refundInfo['cur_pay_fee'], 100, 2),
            'pay_type' => 'online',
            'payment_tid' => '',
            'payment_type' => '',
            'seller_account' => '',
            't_begin' => date('Y-m-d H:i:s', $refundInfo['create_time']),
            't_sent' => '',
            't_received' => date('Y-m-d H:i:s'),
            'refund_type' => 'refund',
            'status' => 'SUCC',
            'memo' => $memo,
        ];

        return $refundData;
    }

    /**
     * 无售后单的情况下 同意/拒绝 退款
     *
     */
    public function toRefund($type, $params, $afterInfo)
    {
        $normalOrderService = new NormalOrderService();
        $orderData = $normalOrderService->getOrderInfo($afterInfo['company_id'], $params['order_id']);
        app('log')->debug("\n saaserp 无售后单退款 ".__FUNCTION__.",".__LINE__.",type===>".$type.",,orderData=>:".var_export($orderData, 1));

        if (!$orderData) {
            throw new Exception("订单信息获取失败");
        }

        $orderType = 'normal';
        // $orderType = $orderData['orderInfo']['order_class'];

        if ($type == 'refuseRefund') {
            //拒绝退款
            $refundData = [
                'check_cancel' => 0,
                'company_id' => $afterInfo['company_id'],
                'order_id' => $params['order_id'],
                'refund_bn' => $afterInfo['refund_bn'],
                'shop_reject_reason' => "OMS拒绝退款",
                'order_type' => $orderType,

            ];
        } else {
            //同意退款
            $refundData = [
                'check_cancel' => 1,
                'company_id' => $afterInfo['company_id'],
                'order_id' => $params['order_id'],
                'refund_bn' => $afterInfo['refund_bn'],
                'shop_reject_reason' => isset($params['memo']) ? trim($params['memo']) : 'OMS确认退款',
                'order_type' => $orderType,
            ];
        }
        app('log')->debug("\nsaaserp 无售后单退款，".__FUNCTION__.",".__LINE__.",refundData=>:".var_export($refundData, 1)."\n");

        $orderService = $this->getOrderService($orderType);
        $result = $orderService->confirmCancelOrder($refundData);
        app('log')->debug("\nsaaserp 无售后单退款，".__FUNCTION__.",".__LINE__.",result=>:".var_export($result, 1)."\n");
        return $result;
    }

    public function getAftersalesInfo($aftersales_bn)
    {
        if (!$aftersales_bn) {
            return [];
        }

        $aftersalesRefundService = new AftersalesRefundService();

        $afterInfo = $aftersalesRefundService->aftersalesRepository->get(['aftersales_bn' => $aftersales_bn]);

        return $afterInfo;
    }

    public function getAftersalesRefundInfoByRefundBn($refund_bn)
    {
        if (!$refund_bn) {
            return [];
        }

        $aftersalesRefundService = new AftersalesRefundService();

        $aftersalesRefundInfo = $aftersalesRefundService->aftersalesRefundRepository->getInfo(['refund_bn' => $refund_bn]);

        return $aftersalesRefundInfo;
    }
}
