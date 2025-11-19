<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use DepositBundle\Services\DepositTrade;
use PaymentBundle\Interfaces\Payment;
use OrdersBundle\Services\TradeService;
use PointBundle\Services\PointMemberRuleService;
use PointBundle\Services\PointMemberService;

use Dingo\Api\Exception\StoreResourceFailedException;
use OrdersBundle\Events\OrderProcessLogEvent;

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

        $pointMemberService = new PointMemberService();
        $otherParams = ['point_type' => 'points_refund', 'refund_bn' => $data['refund_bn']];
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
