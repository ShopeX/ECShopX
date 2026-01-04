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

use OrdersBundle\Services\Orders\NormalOrderService;
use GoodsBundle\Services\ItemsService;
use MembersBundle\Services\MemberService;
use CompanysBundle\Services\CompanysService;

use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrdersPromotionService;
use PromotionsBundle\Services\PromotionSeckillActivityService;

class OrderService
{
    use GetOrderServiceTrait;

    public function __construct()
    {

    }

    /**
     * 生成发给聚水潭订单结构体
     *
     */
    public function getOrderStruct($companyId, $orderId, $shopId, $sourceType='normal')
    {
        // ID: 53686f704578
        $orderService = $this->getOrderService($sourceType);

        // 获取订单信息
        $orderData = $orderService->getOrderInfo($companyId, $orderId);

        if( !$orderData)
        {
            return false;
        }

        // 自提订单暂不推送
        // if ($orderData['orderInfo']['receipt_type'] == 'ziti')
        // {
        //     return false;
        // }

        $status = '';
        $pay_status = '';
        switch($orderData['orderInfo']['order_status'])
        {
            case 'DONE':
                $status = 'TRADE_FINISHED';
                break;
            case 'NOPTAY':
                $status = 'WAIT_BUYER_PAY';
                break;
            case 'PAYED':
                $status = 'WAIT_SELLER_SEND_GOODS';
                break;
            case 'CANCEL':
                $status = 'TRADE_CLOSED';
                break;
            case 'WAIT_BUYER_CONFIRM_GOODS':
                $status = 'TRADE_CLOSED';
                break;
        }

        // 获取订单明细
        $orderItems = $this->__formatOrderItems($orderData['orderInfo']);

        // 获取支付单信息
        $payInfo = $this->__formatPayInfo($orderData['tradeInfo']);

        // 获取买家信息
        $memberInfo = $this->__formatMemberInfo($orderData['orderInfo']);

        // 组织聚水潭订单数据
        $orderStruct = [
            'shop_id' => intval($shopId),
            'so_id' => $orderData['orderInfo']['order_id'],
            'order_date' => date('Y-m-d H:i:s', $orderData['orderInfo']['create_time']),
            'shop_status' => $status,
            'shop_buyer_id' => $memberInfo['mobile'] ?? $orderData['orderInfo']['user_id'],
            'receiver_state' => $orderData['orderInfo']['receiver_state'],
            'receiver_city' => $orderData['orderInfo']['receiver_city'],
            'receiver_district' => $orderData['orderInfo']['receiver_district'],
            'receiver_address' => $orderData['orderInfo']['receiver_address'],
            'receiver_name' => $orderData['orderInfo']['receiver_name'],
            'receiver_mobile' => $orderData['orderInfo']['receiver_mobile'],
            'pay_amount' => floatval(bcdiv($orderData['tradeInfo']['totalFee'], 100, 2)),
            'freight' => floatval(bcdiv($orderData['orderInfo']['freight_fee'], 100, 2)),
            'shop_modified' => date('Y-m-d H:i:s', $orderData['orderInfo']['update_time']),
            'items' => $orderItems,
        ];

        if ($payInfo) {
            $orderStruct['pay'] = $payInfo;
        }

        app('log')->debug('jushuitan orderStruct===>:'.var_export($orderStruct,1));
        return $orderStruct;
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
     * 组织支付单信息 转换成聚水潭结构
     */
    private function __formatPayInfo($payments)
    {
        // ID: 53686f704578
        if (!$payments || $payments['tradeState']!='SUCCESS')
        {
            return false;
        }

        $payType = [
            'amorepay' => '微信支付(amorepay)',
            'wxpay' => '微信支付',
            'deposit' => '预存款支付',
            'pos' => '刷卡',
            'point' => '积分',
            'dhpoint' => '积分',
            'localPay' => '零元订单',
        ];
        if ($payments['payType'] == 'point') {
            $payments['payType'] = 'wxpay';
            $payments['payFee'] = 0;
        }

        $payInfo = [
            'outer_pay_id' => $payments['tradeId'],
            'pay_date' => date('Y-m-d H:i:s', $payments['timeStart']),
            'payment' => $payType[$payments['payType']],
            'seller_account' => $payments['mchId'] ?: strval($payments['companyId']),
            'buyer_account' => $payments['openId'] ?: $payments['mobile'],
            'amount' => floatval(bcdiv($payments['payFee'], 100, 2)),
        ];

        return $payInfo;
    }

    /**
     * 组织商品明细数据 转换成聚水潭结构
     */
    private function __formatOrderItems($orderInfo)
    {
        $orderItems = [];

        foreach ($orderInfo['items'] as $key=>$value)
        {

            $orderItems[] = [
                'sku_id' => $value['item_bn'],
                'shop_sku_id' => $value['item_bn'],
                'amount' => floatval(bcdiv($value['total_fee'], 100, 2)),
                'base_price' => floatval(bcdiv($value['price'], 100, 2)),
                'qty' => $value['num'],
                'name' => $value['item_name'],
                'outer_oi_id' => $value['id'],
            ];

        }

        return $orderItems;
    }
}
