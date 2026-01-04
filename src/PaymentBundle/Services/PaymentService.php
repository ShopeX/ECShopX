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

namespace PaymentBundle\Services;

use DepositBundle\Services\DepositTrade;
use Dingo\Api\Exception\ResourceException;
use OrdersBundle\Services\PrescriptionService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderAssociationService;
use OrdersBundle\Traits\GetPaymentServiceTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\TradeService;

class PaymentService
{
    use GetOrderServiceTrait;
    use GetPaymentServiceTrait;

    /**
     * 商品支付订单
     * @param $authInfo
     * @param $params
     * @return array|void
     * @throws \Exception
     */
    public function payment($authInfo, $params)
    {
        $params['pay_type'] = ($params['pay_type'] ?? '') ? $params['pay_type'] : 'wxpayh5';
        $params['order_type'] = ($params['order_type'] ?? '') ? $params['order_type'] : 'normal';

        //根据支付码判断支付方式
        if (isset($params['auth_code']) && $params['auth_code']) {
            $params['auth_code'] = trim($params['auth_code']);
            if (preg_match('/^1[0-5][0-9]{16}$/', $params['auth_code'])) {
                $params['pay_type'] = 'wxpaypos';
            } elseif (preg_match('/^(25|26|27|28|29|30)[0-9]{14,22}$/', $params['auth_code'])) {
                $params['pay_type'] = 'alipaypos';
            }
        }

        $orderAssociationService = new OrderAssociationService();
        $orderInfo = $orderAssociationService->getOrder($authInfo['company_id'], $params['order_id']);
        if (!in_array($orderInfo['order_status'], ['NOTPAY', 'PART_PAYMENT'])) {
            throw new BadRequestHttpException('当前订单不需要支付');
        }

        $orderService = $this->getOrderService($orderInfo['order_type']);
        $result = $orderService->getOrderInfo($authInfo['company_id'], $params['order_id']);
        if (!$result) {
            throw new BadRequestHttpException('当前订单不存在');
        }
        if (method_exists($orderService, 'updatePayType')) {
            $orderService->updatePayType($params['order_id'], $params['pay_type'], $params['pay_channel'] ?? null);
        }
        // 处方药订单是否已开方
        if ($result['orderInfo']['prescription_status'] != 0) {
            // 查询订单处方信息
            $prescriptionService = new PrescriptionService();
            $prescriptionService->checkOrderPrescriptionStatus($result['orderInfo']);
        }

        //获取门店信息
        $distributorInfo = [];
        if ($result['orderInfo']['distributor_id']) {
            $distributorInfo = $result['distributor'] ?? [];
        }

        // 区分订单类型
        $trade_source_type = $params['order_type'];
        if (($result['orderInfo']['order_class'] ?? '') == 'pointsmall') {
            $trade_source_type = $params['order_type'].'_'.$result['orderInfo']['order_class'];
        }
        $data = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'] ?? 0,
            'total_fee' => $result['orderInfo']['total_fee'] ?? '',
            'detail' => $result['orderInfo']['title'] ?? '',
            'order_id' => $result['orderInfo']['order_id'] ?? '',
            'body' => $result['orderInfo']['title'] ?? '',
            'open_id' => isset($authInfo['open_id']) && !empty($authInfo['open_id']) ? $authInfo['open_id'] : ($params['open_id'] ?? ''),
            'wxa_appid' => $authInfo['wxapp_appid'] ?? '',
            'mobile' => $authInfo['mobile'] ?? '',
            'pay_type' => $params['pay_type'],
            'pay_fee' => 'point' == $params['pay_type'] ? $result['orderInfo']['point'] : $result['orderInfo']['total_fee'],
            'discount_fee' => $result['orderInfo']['discount_fee'] ?? '',
            'discount_info' => $result['orderInfo']['discount_info'] ?? '',
            'fee_rate' => $result['orderInfo']['fee_rate'] ?? '',
            'fee_type' => $result['orderInfo']['fee_type'] ?? '',
            'fee_symbol' => $result['orderInfo']['fee_symbol'] ?? '',
            'shop_id' => $result['orderInfo']['shop_id'] ?? '',
            'distributor_id' => $result['orderInfo']['distributor_id'] ?? '',
            'trade_source_type' => $trade_source_type,
            'return_url' => $params['return_url'] ?? '',
            'auth_code' => $params['auth_code'] ?? '',
            'distributor_info' => $distributorInfo,
            'point' => $result['orderInfo']['point'] ?? 0,
            'source' => $params['source'] ?? '',
        ];
        if ('deposit' == $params['pay_type']) {
            if (!isset($authInfo['user_card_code']) || !$authInfo['user_card_code']) {
                throw new BadRequestHttpException('请先登录');
            }
            $data['member_card_code'] = $authInfo['user_card_code'];
        }
        if (in_array($params['pay_type'], ['adapay', 'bspay', 'offline_pay', 'paypal'])) {
            $data['pay_channel'] = $params['pay_channel'] ?? null;
        }
        if ($params['pay_type'] == 'alipaymini') {
            if (!isset($authInfo['alipay_user_id']) || !$authInfo['alipay_user_id']) {
                throw new BadRequestHttpException('请在支付宝小程序授权登录');
            }
            $data['alipay_user_id'] = $authInfo['alipay_user_id'];
        }
        $authorizerAppId = $authInfo['woa_appid'] ?? '';
        $wxaAppId = $authInfo['wxapp_appid'] ?? '';

        $service = $this->getPaymentService($params['pay_type'], $data['distributor_id']);
        $payResult = $service->doPayment($authorizerAppId, $wxaAppId, $data, false);

        if (isset($result['orderInfo']['pay_type'])) {
            $result['orderInfo']['pay_type'] = $payResult['pay_type'] ?? '';
        }
        $payResult['team_id'] = isset($result['orderInfo']['team_id']) ? $result['orderInfo']['team_id'] : null;
        $payResult['order_id'] = $result['orderInfo']['order_id'] ?? '';
        $payResult['order_info'] = $result['orderInfo'];
        return $payResult;
    }


    public function query($authInfo, $params)
    {
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfoById($params['trade_id']);
        if (!$tradeInfo) {
            throw new BadRequestHttpException('支付单不存在');
        }

        if ($tradeInfo['trade_state'] == 'SUCCESS') {
            return ['status' => 'SUCCESS', 'msg' => '支付成功'];
        }

        try {
            $service = $this->getPaymentService($tradeInfo['pay_type'], $tradeInfo['distributor_id']);
            $payResult = $service->query($tradeInfo);
            if ($payResult['status'] == 'SUCCESS') {
                $options['pay_type'] = $payResult['pay_type'];
                $options['transaction_id'] = $payResult['transaction_id'];
                $tradeService->updateStatus($tradeInfo['trade_id'], 'SUCCESS', $options);
            }
            return $payResult;
        } catch (\Exception $e) {
            throw new BadRequestHttpException('支付失败');
        }
    }

    /**
     * 储值支付订单
     * @param $authInfo
     * @param $params
     * @return mixed
     * @throws \Exception
     */
    public function depositPayment($authInfo, $params)
    {
        $params['pay_type'] = isset($params['pay_type']) ? $params['pay_type'] : 'wxpayh5';
        $params['order_type'] = 'recharge';

        $depositTrade = new DepositTrade();
        $result = $depositTrade->getDepositTradeInfo($params['order_id']);
        if (!$result) {
            throw new BadRequestHttpException('当前订单不存在');
        }
        if ($result['trade_status'] != 'NOTPAY') {
            throw new BadRequestHttpException('当前订单不需要支付');
        }

        $result['open_id'] = $params['open_id'] ?? '';
        $authorizerAppId = $authInfo['woa_appid'] ?? '';
        $wxaAppId = $authInfo['wxapp_appid'] ?? '';
        if ($params['pay_type'] == 'alipaymini') {
            if (!isset($authInfo['alipay_user_id']) || !$authInfo['alipay_user_id']) {
                throw new BadRequestHttpException('请在支付宝小程序授权登录');
            }
            $result['alipay_user_id'] = $authInfo['alipay_user_id'];
        }
        $service = $this->getDepositPaymentService($params['pay_type']);
        $payResult = $service->depositRecharge($authorizerAppId, $wxaAppId, $result);
        $data = $payResult;

        return $data;
    }
}
