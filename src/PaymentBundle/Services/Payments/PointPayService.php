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

use DepositBundle\Services\DepositTrade;
use Dingo\Api\Exception\StoreResourceFailedException;
use MembersBundle\Services\MemberService;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Interfaces\Payment;
use PointBundle\Services\PointMemberRuleService;
use PointBundle\Services\PointMemberService;
use ThirdPartyBundle\Services\DmCrm\DmCrmSettingService;
use ThirdPartyBundle\Services\DmCrm\PointService;

class PointPayService implements Payment
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
        $pointMemberService = new PointMemberService();
        $pointMemberInfo = $pointMemberService->getInfo(['user_id' => $data['user_id'], 'company_id' => $data['company_id']]);
        // 达摩crm, 会员积分
        $ns = new DmCrmSettingService();
        if ($ns->getDmCrmSetting($data['company_id'])['is_open'] ?? '') {
            $memberSerivce = new MemberService();
            $pointService = new PointService($data['company_id']);
            $filterMember = [
                'user_id' => $data['user_id'],
                'company_id' => $data['company_id'],
            ];
            $memberInfo = $memberSerivce->getMemberInfo($filterMember, false);
            $paramsData = [
                'mobile' => $memberInfo['mobile'],
            ];
            $point = $pointService->getPoint($paramsData);
            $pointMemberInfo['point'] = $point['integral'] ?? 0;
        }
        if (!isset($pointMemberInfo['point']) || $pointMemberInfo['point'] < $data['pay_fee']) {
            throw new StoreResourceFailedException("积分不足！");
        }

        $depositTrade = new DepositTrade();
        $deposit = (int)$depositTrade->getUserDepositTotal($data['company_id'], $data['user_id']);
        $pointMemberRuleService = new PointMemberRuleService();
        $money = ($pointMemberRuleService->getUsePointRule($data['company_id']));
        if ($deposit < $money) {
            $money /= 100;
            throw new StoreResourceFailedException("充值满{$money}元才能使用积分！");
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $options['bank_type'] = '积分';
            $options['pay_type'] = 'point';
            $otherParams = ['point_type' => 'points_off_cash'];
            $pointMemberService->addPoint($data['user_id'], $data['company_id'], $data['pay_fee'], 6, false, '支付单号:' . $data['trade_id'] . '消耗积分', $data['order_id'], $otherParams);
            $tradeService = new TradeService();
            $tradeService->updateStatus($data['trade_id'], 'SUCCESS', $options);
            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw new StoreResourceFailedException("积分扣除失败");
        }
        return ['pay_status' => true];
    }

    /**
     * 商家退款到指定账号
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        app('log')->debug('point doRefund start order_id=>' . $data['order_id']);
        $result = $this->refund($data);
        app('log')->debug('point doRefund end');
        app('log')->debug('point doRefund result:' . var_export($result, 1));

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
     * 退还积分记录
     * @param $wxaAppId
     * @param $data
     * @return array
     * @throws \Exception
     */
    public function refund($data)
    {
        // $pointMemberRuleService = new PointMemberRuleService();
        // $point = $pointMemberRuleService->moneyToPoint($data['company_id'], $data['refund_fee']);
        app('log')->debug('point doRefund start refund data=>' . var_export($data, 1));
        $pointMemberService = new PointMemberService();
        $otherParams = ['point_type' => 'points_refund', 'refund_bn' => $data['refund_bn'], 'aftersales_bn' => $data['aftersales_bn'] ?? ''];
        $pointMemberService->addPoint($data['user_id'], $data['company_id'], $data['refund_point'], 10, true, '退款单号:' . $data['refund_bn'], $data['order_id'], $otherParams);
        $result = [
            'return_code' => 'SUCCESS',
            'refund_id' => $data['refund_bn']
        ];
        $orderProcessLog = [
            'order_id' => $data['order_id'],
            'company_id' => $data['company_id'] ?? 0,
            'operator_type' => 'system',
            'remarks' => '订单退款',
            'detail' => '订单号：' . $data['order_id'] . '，订单退还积分成功',
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
