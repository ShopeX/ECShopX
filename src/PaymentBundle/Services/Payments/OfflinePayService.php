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

use GoodsBundle\Services\MultiLang\MagicLangTrait;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Dingo\Api\Exception\ResourceException;
use PaymentBundle\Interfaces\Payment;
use OrdersBundle\Traits\GetOrderServiceTrait;

class OfflinePayService implements Payment
{
    use MagicLangTrait;
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
        $lang = $this->getLang();
        
        if(!empty($data['pay_name'])){
            app('redis')->set($companyId.'_lang_pay_name_offline_pay:'.$lang,$data['pay_name']);
        }
        $redisKey = $this->genReidsId($companyId);
        $result = app('redis')->set($redisKey, json_encode($data));
        return $result;
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        $lang = $this->getLang();
        $exitLangName = app('redis')->get($companyId.'_lang_pay_name_offline_pay:'.$lang);
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            if(!empty($exitLangName)){
                $data['pay_name'] = $exitLangName;
            }
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
            $errMsg = trans('payment.not_supported') . ($setting['pay_name'] ?? self::PAY_TYPE_NAME);
            return false;
        }
        if ($setting && isset($setting['auto_cancel_time'])) {
            $autoCancelTime = intval($setting['auto_cancel_time']) * 60;//小时转换成分钟
        } else {
            $errMsg = trans('payment.payment_timeout_error');
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
            throw new BadRequestHttpException(self::PAY_TYPE_NAME . trans('payment.info_not_configured'));
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
