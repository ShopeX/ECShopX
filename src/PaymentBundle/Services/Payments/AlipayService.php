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

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use PaymentBundle\Interfaces\Payment;
use PaymentBundle\Traits\PaymentSubjectTrait;

use OrdersBundle\Events\OrderProcessLogEvent;

class AlipayService implements Payment
{
    use PaymentSubjectTrait;

    private $distributorId = 0; // 店铺ID
    private $getDefault = true; //是否取平台默认配置

    public function __construct($distributorId = 0, $getDefault = true)
    {
        $this->distributorId = $distributorId;
        $this->getDefault = $getDefault;
    }

    /**
     * 校验私钥（只做验证，不做格式转换）
     * @param string $privateKey 私钥字符串
     * @throws BadRequestHttpException 如果私钥内容无效
     */
    private function validatePrivateKey($privateKey)
    {
        if (empty($privateKey)) {
            return;
        }

        $privateKey = trim($privateKey);
        
        // 如果是 PEM 格式，提取 Base64 内容用于验证
        if (strpos($privateKey, '-----BEGIN') !== false) {
            $cleanKey = preg_replace('/-----BEGIN.*?-----/', '', $privateKey);
            $cleanKey = preg_replace('/-----END.*?-----/', '', $cleanKey);
            $cleanKey = preg_replace('/\s+/', '', $cleanKey);
        } else {
            // Base64 格式，清理空格和换行
            $cleanKey = preg_replace('/\s+/', '', $privateKey);
        }
        
        // 按 support.php 的处理方式，拼接首尾字符后验证
        $pemKey = "-----BEGIN RSA PRIVATE KEY-----\n" .
                  wordwrap($cleanKey, 64, "\n", true) .
                  "\n-----END RSA PRIVATE KEY-----";
        
        // 验证私钥是否可以正常解析
        $keyResource = @openssl_pkey_get_private($pemKey);
        if ($keyResource === false) {
            throw new BadRequestHttpException('私钥内容无效');
        }
        @openssl_free_key($keyResource);
    }

    /**
     * 设置支付宝支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        // 校验私钥（只做验证，不做格式转换）
        if (isset($data['private_key']) && !empty($data['private_key'])) {
            $this->validatePrivateKey($data['private_key']);
        }

        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        // 根据店铺收款主体类型决定实际使用的distributorId
        if ($this->getDefault && $this->distributorId > 0) {
            $actualDistributorId = $this->getActualDistributorId($this->distributorId, $companyId);
            if ($actualDistributorId != $this->distributorId) {
                $this->distributorId = $actualDistributorId;
            }
        }
        
        $data = app('redis')->get($this->genReidsId($companyId));
        // 增加了支付主体的字段，平台or店铺，无论配置是否为空，如果是店铺，则取店铺配置，如果是平台，则取平台配置
        //不存在店铺配置取平台的配置
        // if (!$data && $this->getDefault && $this->distributorId > 0) {
        //     $this->distributorId = 0;
        //     $data = app('redis')->get($this->genReidsId($companyId));
        // }

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
