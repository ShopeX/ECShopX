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

namespace SystemLinkBundle\Services\WdtErp;

use Illuminate\Support\Facades\DB;
use OrdersBundle\Traits\GetOrderServiceTrait;
use MembersBundle\Services\MemberService;

class OrderService
{
    use GetOrderServiceTrait;

    /**
     * @param $companyId
     * @param $orderId
     * @param $sourceType
     * @return array|false
     */
    public function getOrderStruct($companyId, $orderId, $sourceType='normal')
    {
        $orderService = $this->getOrderService($sourceType);
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        app('log')->debug('wdtErp orderStruct===>:'. json_encode($orderData));

        if(!$orderData) {
            return false;
        }

        // 获取买家信息
        $memberInfo = $this->__formatMemberInfo($orderData['orderInfo']);

        // 获取订单明细
        $tradeOderList = $this->__formatOrderItems($orderData);

        // 获取推广员信息
        $association = $this->__getAssociation($companyId, $orderId);

        $rawTrade = new \stdClass();
        $rawTrade->tid = $orderData['orderInfo']['order_id']; // 原始订单号
        $rawTrade->process_status = $this->getProcessStatus($orderData['orderInfo']['order_status']); // 处理状态
        $rawTrade->trade_status = $this->getTradeStatus($orderData['orderInfo']['order_status']); // 平台状态
        $rawTrade->refund_status = $this->getRefundStatus($orderData['cancelData']['refund_status'] ?? ''); // 退款状态
        $rawTrade->pay_status = $this->getPayStatus($orderData['orderInfo']['pay_status']); // 支付状态
        $rawTrade->pay_method = $this->getPayMethod($orderData['tradeInfo']);  // 支付方式
        $rawTrade->trade_time = date('Y-m-d H:i:s', $orderData['orderInfo']['create_time']); // 下单时间
        $rawTrade->end_time = date('Y-m-d H:i:s', $orderData['tradeInfo']['timeExpire']); // 交易结束时间
        $rawTrade->buyer_nick = $memberInfo['username'];
        $rawTrade->receiver_name = $orderData['orderInfo']['receiver_name'];
        $rawTrade->receiver_area = $orderData['orderInfo']['receiver_state'] . ' ' . $orderData['orderInfo']['receiver_city'] . ' '. $orderData['orderInfo']['receiver_district'];
        $rawTrade->receiver_address = $orderData['orderInfo']['receiver_address'];
        $rawTrade->receiver_mobile = $orderData['orderInfo']['receiver_mobile'];
        $rawTrade->post_amount = floatval(bcdiv($orderData['orderInfo']['freight_fee'], 100, 2)); // 邮费
        $rawTrade->discount = floatval(bcdiv($orderData['orderInfo']['discount_fee'], 100, 2)); // 优惠金额
        $rawTrade->receivable = floatval(bcdiv($orderData['tradeInfo']['totalFee'], 100, 2)); // 应收金额
        $rawTrade->delivery_term = 1; // 发货条件 1款到发货 2货到付款(包含部分货到付款) 3分期付款
        $rawTrade->is_auto_wms = false;
        $rawTrade->warehouse_no = '';
        $rawTrade->order_count = count($orderData['orderInfo']['items']);
        $rawTrade->goods_count = array_sum(array_column($orderData['orderInfo']['items'], 'num'));
        if ($association != '') {
            $rawTrade->remark = '推广员:' .$association;
        }

        $orderStruct = [
            'rawTrade' => $rawTrade,
            'tradeOderList' => $tradeOderList,
        ];

        app('log')->debug('wdtErp orderStruct===>:' . var_export($orderStruct, 1));
        return $orderStruct;
    }

    private function __getAssociation($companyId, $orderId)
    {
        $associationInfo = DB::table('orders_associations')
            ->where([
                'company_id' => $companyId,
                'order_id' => $orderId,
            ])
            ->first();

        if (empty($associationInfo) || empty($associationInfo->promoter_user_id)) {
            return '';
        }

        $memberInfo = DB::table('members')->where('user_id', $associationInfo->promoter_user_id)->first();
        return $memberInfo ? $memberInfo->mobile : '';
    }

    /**
     * @param $tradeInfo
     * @return int
     */
    private function getPayMethod($tradeInfo)
    {
        // 1在线转帐 2现金，3银行转账，4邮局汇款 5预付款 6刷卡 7支付宝 8微信支付
        switch ($tradeInfo['payType']) {
            case 'amorepay':
            case 'wxpay':
                return 8;
            default:
                return 5;
        }
    }

    /**
     * @param $payStatus
     * @return int|void
     */
    private function getPayStatus($payStatus)
    {
        // 0未付款1部分付款2已付款
        switch ($payStatus) {
            case 'PAYED':
                return 2;
            case 'ADVANCE_PAY':
            case 'TAIL_PAY':
                return 1;
            case 'NOTPAY':
            default:
                return 0;
        }
    }

    /**
     * @param $refundStatus
     * @return int
     */
    private function getRefundStatus($refundStatus)
    {
        // 0无退款 1申请退款 2部分退款 3全部退款
        switch ($refundStatus) {
            case 'READY': // 待审核
            case 'WAIT_CHECK': // 待商家审核
            case 'PROCESSING': // 已发起退款等待到账
                return 1;
            case 'SUCCESS': // 退款成功
            case 'AUDIT_SUCCESS': // 审核成功待退款
                return 3;
            default:
                return 0;
        }
    }

    /**
     * @param $orderStatus
     * @return int
     */
    private function getProcessStatus($orderStatus)
    {
        //10: 待递交 20: 已递交，30: 部分发货，40: 已发货，60: 已完成，70: 已取消
        switch($orderStatus) {
            case 'CANCEL':
                return 70;
            case 'WAIT_BUYER_CONFIRM_GOODS':
                return 40;
            case 'PAYED':
            case 'NOPTAY':
            case 'DONE':
            default:
                return 10;
        }
    }

    /**
     * @param $orderStatus
     * @return int
     */
    private function getTradeStatus($orderStatus)
    {
        // 10未确认 20待尾款 30待发货 40部分发货 50已发货 60已签收 70已完成 80已退款 90已关闭(付款前取消
        switch($orderStatus) {
            case 'PAYED':
                return 30;
            case 'CANCEL':
                return 90;
            case 'WAIT_BUYER_CONFIRM_GOODS':
                return 50;
            case 'DONE':
                return 70;
            case 'NOPTAY':
            default:
                return 10;
        }
    }

    /**
     * 组织买家信息
     */
    private function __formatMemberInfo($orderInfo)
    {
        $memberService = new MemberService();

        $memberInfo = $memberService->getMemberInfo(['user_id'=>$orderInfo['user_id'], 'company_id'=>$orderInfo['company_id']]);

        if (!$memberInfo)
        {
            return false;
        }

        return $memberInfo;
    }


    /**
     * 组织商品明细数据 转换成旺店通结构
     */
    private function __formatOrderItems($orderData)
    {
        $tradeOderList = [];
        $orderInfo = $orderData['orderInfo'];
        foreach ($orderInfo['items'] as $value) {
            $tradeOrder = new \stdClass();
            $tradeOrder->tid = $orderInfo['order_id']; // 原始订单号
            $tradeOrder->oid = $orderInfo['order_id'] .'-'. $value['id']; // 原始子单号
            $tradeOrder->status = $this->getSubOrderStatus($orderInfo['order_status'], $value['delivery_status']); // 平台的状态
            $tradeOrder->refund_status = $this->getSubOrderRefundStatus($orderData['cancelData']['refund_status'] ?? '', $value['aftersales_status']); // 退款标记
            $tradeOrder->goods_id = $value['goods_id']; // 平台货品ID
            $tradeOrder->spec_id = $value['item_id']; // 平台规格ID
            $tradeOrder->goods_no = $value['item_bn']; // 货品编号
            $tradeOrder->spec_no = $value['item_bn']; // 规格编码
            $tradeOrder->goods_name = $value['item_name']; // 货品名称
            $tradeOrder->num = $value['num']; // 数量
            $tradeOrder->price = floatval(bcdiv($value['price'], 100, 2)); // 单价
            $tradeOrder->discount = floatval(bcdiv($value['discount_fee'], 100, 2)); // 优惠
            $tradeOrder->share_discount = 0; // 分摊优惠退款不变
            $tradeOrder->total_amount = floatval(bcdiv($value['total_fee'], 100, 2)); // 总价格
            $tradeOrder->adjust_amount = 0; // 手工调整的优惠金额
            $tradeOrder->refund_amount = floatval(bcdiv($value['refunded_fee'], 100, 2)); // 退款金额
            $tradeOrder->remark = '';
            $tradeOderList[] = $tradeOrder;
        }

        return $tradeOderList;
    }

    /**
     * @param $orderStatus
     * @param $deliveryStatus
     * @return int
     */
    private function getSubOrderStatus($orderStatus, $deliveryStatus)
    {
        // 10未确认 20待尾款 30待发货 40部分发货 50已发货 60已签收 70已完成 80已退款 90已关
        if ($orderStatus == 'NOPTAY' || $orderStatus == 'CANCEL') {
            return 90;
        }
        return $deliveryStatus === 'DONE' ? 50 : 30;
    }

    /**
     * @param $refundStatus
     * @param $aftersalesStatus
     * @return int
     */
    private function getSubOrderRefundStatus($refundStatus, $aftersalesStatus)
    {
        // 0无退款 1取消退款,2已申请退款,3等待退货,4等待收货,5退款成功,6未付款关闭
        if ($refundStatus) {
            switch ($refundStatus) {
                case 'READY': // 待审核
                case 'WAIT_CHECK': // 待商家审核
                case 'PROCESSING': // 已发起退款等待到账
                case 'FAILS': // 退款失败
                    return 2;
                case 'AUDIT_SUCCESS': // 审核成功待退款
                case 'SUCCESS': // 退款成功
                    return 5;
                case 'SHOP_CHECK_FAILS': // 商家审核不通过
                    return 0;
            }
        }

        switch ($aftersalesStatus) {
            case 'WAIT_SELLER_AGREE':
                return 2;
            case 'WAIT_BUYER_RETURN_GOODS':
            case 'SELLER_SEND_GOODS':
                return 3;
            case 'WAIT_SELLER_CONFIRM_GOODS':
                return 4;
            case 'REFUND_SUCCESS':
                return 5;
            case 'SELLER_REFUSE_BUYER':
            case 'REFUND_CLOSED':
            case 'CLOSED':
                return 1;
            default:
                return 0;
        }
    }
}
