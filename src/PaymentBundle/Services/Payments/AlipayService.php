<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use PaymentBundle\Interfaces\Payment;

use OrdersBundle\Events\OrderProcessLogEvent;

class AlipayService implements Payment
{

    private $distributorId = 0; // 店铺ID
    private $getDefault = true; //是否取平台默认配置

    public function __construct($distributorId = 0, $getDefault = true)
    {
        $this->distributorId = $distributorId;
        $this->getDefault = $getDefault;
    }

    /**
     * 设置微信支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));

        //不存在店铺配置取平台的配置
        if (!$data && $this->getDefault && $this->distributorId > 0) {
            $this->distributorId = 0;
            $data = app('redis')->get($this->genReidsId($companyId));
        }

        $data = json_decode($data, true);
        if ($data) {
            return $data;
        } else {
            return [];
        }
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        $key = 'alipayPaymentSetting:' . sha1($companyId);
        return ($this->distributorId ? ($this->distributorId . $key) : $key);
    }

    /**
     * 获取支付实例
     */
    public function getPayment($companyId, $returnUrl = '')
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            return app('alipay.app.payment')->payment($paymentSetting, $returnUrl);
        } else {
            throw new BadRequestHttpException('支付宝信息未配置，请联系商家');
        }
    }

    public function getRefund($companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            return app('alipay.app.payment')->payment($paymentSetting);
        } else {
            throw new BadRequestHttpException('支付宝信息未配置，请联系商家');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'alipay',
            'attach' => 'depositRecharge',
        ];
        $attributes = [
            'out_trade_no' => $data['deposit_trade_id'],
            'total_amount' => bcdiv($data['money'], 100, 2),
            'subject' => $data['shop_name'] . '充值',
            'passback_params' => urlencode(http_build_query($passbackParams)),
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['deposit_trade_id']);
    }

    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'alipay',
        ];
        $attributes = [
            'out_trade_no' => $data['trade_id'],
            'total_amount' => bcdiv($data['pay_fee'], 100, 2),
            'subject' => $data['body'],
            'passback_params' => urlencode(http_build_query($passbackParams)),
        ];

        return $this->configForPayment($attributes, $data['company_id'], $data['return_url']);
    }

    public function doRefund($companyId, $wxaAppId, $data)
    {
        $merchantPayment = $this->getRefund($companyId);
        app('log')->debug('alipay doRefund start order_id=>' . $data['order_id']);
        $refundFee = isset($data['refund_fee']) ? $data['refund_fee'] : null;
        $order = [
            'out_trade_no' => $data['trade_id'],
            'out_request_no' => $data['refund_bn'],
            'refund_amount' => bcdiv($refundFee, 100, 2),
        ];
        $result = $merchantPayment->refund($order);
        app('log')->debug('alipay doRefund end');
        app('log')->debug('alipay doRefund result:' . $result);

        if (strtoupper($result->msg) == 'SUCCESS') {
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $result->trade_no;
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（支付宝渠道）',
            ];
        } else {
            $return['status'] = 'FAIL';
            $return['error_code'] = '';
            $return['error_desc'] = $result->sub_msg;
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（支付宝渠道），失败原因：' . $result->sub_msg,
            ];
        }

        event(new OrderProcessLogEvent($orderProcessLog));
        return $return;
    }

    private function configForPayment($attributes, $companyId, $returnUrl)
    {
        $payment = $this->getPayment($companyId, $returnUrl);

        $result['payment'] = $payment->web($attributes)->getContent();

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

    /**
     * Convert encoding.
     *
     * @param array  $array
     * @param string $to
     * @param string $from
     *
     * @return array
     */
    public static function encoding($array, $to, $from = 'gb2312')
    {
        $encoded = [];

        foreach ($array as $key => $value) {
            $encoded[$key] = is_array($value) ? self::encoding($value, $to, $from) :
                                                mb_convert_encoding($value, $to, $from);
        }

        return $encoded;
    }
}
