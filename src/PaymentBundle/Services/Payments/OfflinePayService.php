<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PaymentBundle\Services\Payments;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Dingo\Api\Exception\ResourceException;
use PaymentBundle\Interfaces\Payment;
use OrdersBundle\Traits\GetOrderServiceTrait;

class OfflinePayService implements Payment
{
    use GetOrderServiceTrait;

    private $payType = 'offline_pay';
    public const PAY_TYPE_NAME = '线下转账';

//    public function __construct($companyId = 0)
//    {
//        parent::init($companyId);
//    }

    /**
     * 设置支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        $redisKey = $this->genReidsId($companyId);
        $result = app('redis')->set($redisKey, json_encode($data));
        return $result;
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            return $data;
        } else {
            return [];
        }
    }
    
    public function getAutoCancelTime($companyId, &$errMsg = '')
    {
        $autoCancelTime = 0;
        $setting = $this->getPaymentSetting($companyId);
        $isOpen = $setting['is_open'] ?? 0;
        if (!$isOpen) {
            $errMsg = '暂不支持' . $setting['pay_name'] ?? self::PAY_TYPE_NAME;
            return false;
        }
        if ($setting && isset($setting['auto_cancel_time'])) {
            $autoCancelTime = intval($setting['auto_cancel_time']) * 60;//小时转换成分钟
        } else {
            $errMsg = '支付超时时间设置错误';
            return false;
        }
        return $autoCancelTime;
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return $this->payType . 'Setting:' . sha1($companyId);
    }

    /**
     * 获取支付实例
     */
    public function getPayment($authorizerAppId, $wxaAppId, $companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
        } else {
            throw new BadRequestHttpException(self::PAY_TYPE_NAME . '信息未配置，请联系商家');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        return [];
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        return $data;
    }

    /**
     * 线下支付退款
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        return [
            'return_code' => 'SUCCESS',
            'status' => 'SUCCESS',
            'refund_id' => $data['refund_bn']
        ];
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
