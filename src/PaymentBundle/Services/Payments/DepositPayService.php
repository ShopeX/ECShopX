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

namespace PaymentBundle\Services\Payments;

use Dingo\Api\Exception\StoreResourceFailedException;

use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\TradeService;

use MembersBundle\Services\MemberService;

use PaymentBundle\Interfaces\Payment;
// use WechatBundle\Services\OpenPlatform;

use DepositBundle\Services\DepositTrade;

class DepositPayService implements Payment
{
    /**
     * 设置微信支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        // 无需配置
        return true;
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        // 无需配置
        return true;
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        return null;
    }

    /**
     * 退款
     */
    public function getRefund($wxaAppId, $companyId)
    {
        return true;
    }

    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $depositTrade = new DepositTrade();
        $depositMemberInfo = $depositTrade->getUserDepositTotal($data['company_id'], $data['user_id']);
        if (!$depositMemberInfo || $depositMemberInfo < $data['pay_fee']) {
            throw new StoreResourceFailedException('储值金额不足，请充值！');
        }
        $consumeData['company_id'] = $data['company_id'];
        $consumeData['member_card_code'] = $data['member_card_code'];
        $consumeData['shop_id'] = $data['shop_id'] ?? '';
        $consumeData['shop_name'] = $data['shop_name'] ?? '';
        $consumeData['user_id'] = $data['user_id'];
        $consumeData['mobile'] = $data['mobile'];
        $consumeData['open_id'] = $data['open_id'];
        $consumeData['money'] = $data['pay_fee'];
        $consumeData['trade_type'] = 'consume';
        $consumeData['trade_status'] = 'SUCCESS';
        $consumeData['wxa_appid'] = $wxaAppId;
        $consumeData['detail'] = '购买商品';
        $consumeData['time_start'] = time();
        $consumeData['cur_pay_fee'] = $data['pay_fee'];
        $consumeData['authorizer_appid'] = $authorizerAppId;
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($depositTradeData = $depositTrade->consume($consumeData)) {
                $options['bank_type'] = '储值卡';
                $options['transaction_id'] = $depositTradeData['deposit_trade_id'];
                $options['pay_type'] = 'deposit';
                $tradeService = new TradeService();
                $tradeService->updateStatus($data['trade_id'], 'SUCCESS', $options);
                $conn->commit();
            }
        } catch (\Exception $e) {
            $conn->rollback();
            throw new StoreResourceFailedException("余额扣除失败");
        }
        return ['pay_status' => true];
    }

    /**
     * 商家退款到指定账号
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        app('log')->debug('deposit doRefund start order_id=>' . $data['order_id']);
        $result = $this->refund($wxaAppId, $data);
        app('log')->debug('deposit doRefund end');
        app('log')->debug('deposit doRefund result:' . var_export($result, 1));

        if ($result['return_code'] == 'SUCCESS') {
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $result['refund_id'];
        } else {
            $return['status'] = 'FAIL';
            $return['error_code'] = '';
            $return['error_desc'] = '退款失败';
        }

        return $return;
    }

    /**
     * 退还储存金额记录
     * @param $wxaAppId
     * @param $data
     * @return array
     */
    public function refund($wxaAppId, $data)
    {
        $memberService = new MemberService();
        $info = $memberService->getMemberInfo(['user_id' => $data['user_id']]);
        $depositTrade = new DepositTrade();
        $consumeData['company_id'] = $data['company_id'];
        $consumeData['member_card_code'] = $info['user_card_code'] ?? ''; //
        $consumeData['shop_id'] = $data['shop_id'] ?? '';
        $consumeData['shop_name'] = $data['shop_name'] ?? '';
        $consumeData['user_id'] = $data['user_id'];
        $consumeData['mobile'] = $info['mobile'] ?? ''; //
        $consumeData['open_id'] = $info['open_id'] ?? ''; //
        $consumeData['money'] = $data['refund_fee'];
        $consumeData['trade_type'] = 'refund';
        $consumeData['trade_status'] = 'SUCCESS';
        $consumeData['wxa_appid'] = $wxaAppId;
        $consumeData['detail'] = '订单' . $data['order_id'] . '退款';
        $consumeData['time_start'] = time();
        $consumeData['cur_pay_fee'] = $data['refund_fee'];
        $consumeData['authorizer_appid'] = $info['authorizer_appid'] ?? ''; //
        $depositTrade->doRefund($consumeData);
        $result = [
            'return_code' => 'SUCCESS',
            'refund_id' => $data['refund_bn']
        ];
        $orderProcessLog = [
            'order_id' => $data['order_id'],
            'company_id' => $data['company_id'],
            'operator_type' => 'system',
            'remarks' => '订单退款',
            'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（储值金额渠道)',
        ];
        event(new OrderProcessLogEvent($orderProcessLog));
        return $result;
    }

    /**
     * 获取订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        return [];
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data)
    {
        return [];
    }
}
