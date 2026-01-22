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

use OrdersBundle\Interfaces\Trade;
use PaymentBundle\Interfaces\Payment;
use PaymentBundle\Traits\PaymentSubjectTrait;
use WechatBundle\Services\OpenPlatform;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use OrdersBundle\Services\TradeService;
use GuzzleHttp\Client as Client;

use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Traits\GetOrderServiceTrait;
use ChinaumsPayBundle\Services\ClientAPIs\UmsClient;
use AftersalesBundle\Services\AftersalesRefundService;

/**
 * 银联商务，支付
 */
class ChinaumsPayService implements Payment
{
    use GetOrderServiceTrait;
    use PaymentSubjectTrait;

    private $subKey = ''; // 子商户配置key

    public function __construct($subKey = '')
    {
        $this->subKey = $subKey;
        $this->umsClientServ = new UmsClient;
    }

    /**
     * 设置银联商务支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        if (isset($data['is_open']) && $data['is_open']) {
            app('redis')->set('paymentTypeOpenConfig:' . sha1($companyId), 'chinaumspay');
        }

        $redisData = app('redis')->get($this->genReidsId($companyId));
        if ($redisData) {
            $redisData = json_decode($redisData, true);
        }
        if (isset($data['rsa_private']) && $data['rsa_private']) {
            $data['rsa_private'] = file_get_contents($data['rsa_private']);
            $privateFile = 'chinaumsPayment/' . $data['mid'] . '/rsa_private.pfx';
            app('filesystem')->put($privateFile, $data['rsa_private']);
            unset($data['rsa_private']);
        }

        if (isset($data['rsa_public']) && $data['rsa_public']) {
            $data['rsa_public'] = file_get_contents($data['rsa_public']);
            //公钥
            $publicFile = 'chinaumsPayment/' . $data['mid'] . '/rsa_public.cer';
            app('filesystem')->put($publicFile, $data['rsa_public']);
            unset($data['rsa_public']);
        }
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId, $subKey = '')
    {
        if ($subKey) {
            $this->subKey = $subKey;
        }
        
        // 根据店铺收款主体类型决定实际使用的subKey
        $actualSubKey = $this->getActualSubKey($this->subKey, $companyId);
        if ($actualSubKey != $this->subKey) {
            $this->subKey = $actualSubKey;
        }
        
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            //私钥
            $privateFile = 'chinaumsPayment/' . $data['mid'] . '/rsa_private.pfx';
            if (app('filesystem')->exists($privateFile)) {
                $data['rsa_private_name'] = 'rsa_private.pfx';
                $data['rsa_private_path'] = app('filesystem')->path($privateFile);
            }

            //公钥
            $publicFile = 'chinaumsPayment/' . $data['mid'] . '/rsa_public.cer';
            if (app('filesystem')->exists($publicFile)) {
                $data['rsa_public_name'] = 'rsa_public.cer';
                $data['rsa_public_path'] = app('filesystem')->path($publicFile);
            }
            return $data;
        }
        return [];
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        $key = 'chinaumsPaymentSetting:' . sha1($companyId);
        return ($this->subKey ? ($key.'_'.$this->subKey) : $key);
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     * 支付文档 https://res-mop.chinaums.com/upload_doc/%E9%97%A8%E6%88%B7%E6%96%87%E6%A1%A3/%E6%94%AF%E4%BB%98%E6%96%87%E6%A1%A3/20220228/42a857fc7b5d2a7a71775e70762f9d27e1400a7e65ecca86f24d1f4d1c45ae50.pdf
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // 检查店铺支付数据
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        $data['mch_id'] = $paymentSetting['mid'];
        //-----------------银联支付------------------------------
        
        //支付参数
        //预分账计算
        $subOrders = $this->division($data);
        if ($subOrders) {
            $platformOrder = array_shift($subOrders);
            $platformAmount = $platformOrder['totalAmount'];
            $divisionFlag = true;
        } else {
            $platformAmount = $data['pay_fee'];
            $divisionFlag = false;
        }
        
        $attributes = [
            'msgId' => $this->__genMsgId(),
            'requestTimestamp' => date('Y-m-d H:i:s', time()),
            'mid' => $data['mch_id'],
            'tid' => $paymentSetting['tid'],
            'merOrderId' => config('ums.pre').$data['trade_id'],
            'instMid' => 'MINIDEFAULT',
            'divisionFlag' => $divisionFlag,
            'platformAmount' => $platformAmount,
            'subOrders' => $subOrders,
            'notifyUrl' => config('common.chinaums_payment_notify'),
            'totalAmount' => $data['pay_fee'], // 单位：分
            'expireTime' => date('Y-m-d H:i:s', (time() + 300)),
            // 'out_trade_no' => $data['trade_id'] ?? 0,
            'subOpenId' => $data['open_id'],
            'subAppId' => $wxaAppId,
            'tradeType' => 'MINI'
        ];
        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id']);
    }

    /**
     * 对微信进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $authorizerAppId, $wxaAppId, $companyId, $isRecharge = false)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        
        //发起银联支付 
        $result = $this->umsClientServ->unifiedOrder($attributes);

        //todo
        // if (!$isRecharge) {
        //     $tradeService = new TradeService();
        //     $tradeService->updateOneBy(['trade_id' => $attributes['out_trade_no']], ['inital_request' => json_encode($attributes)]);
        // }
        $result = json_decode($result, true);
        if ( $result['errCode'] == 'SUCCESS' ) {

            //返回数据
            $pay_info = $result['miniPayRequest'];
            return [
                'appId' => $pay_info['appId'],
                'nonceStr' => $pay_info['nonceStr'],
                'package' => $pay_info['package'],
                'paySign' => $pay_info['paySign'],
                'signType' => $pay_info['signType'],//根据文档 
                'timeStamp' => $pay_info['timeStamp'],
                'team_id' => null,
            ];

        } else {
            app('log')->debug('ums payment params:' . json_encode($attributes));
            app('log')->debug('ums payment Message Error result:' . json_encode($result));
            throw new BadRequestHttpException('支付失败');
        }
    }

    /**
     * 商家退款到指定账号
     */
    public function doRefund($companyId, $wxaAppId, $data, $resubmit = false)
    {
        // 检查店铺支付数据
        $paymentSetting = $this->getPaymentSetting($companyId);
        $data['mch_id'] = $paymentSetting['mid'];
        app('log')->debug('wechat doRefund start order_id=>' . $data['order_id'].',distributor_id=>'.$data['distributor_id']);
        $orderNo = $data['trade_id'];
        $refundNo = isset($data['refund_bn']) ? $data['refund_bn'] : $data['trade_id'];
        $totalFee = $data['pay_fee'];
        $refundFee = isset($data['refund_fee']) ? $data['refund_fee'] : null;

        $subOrders = $this->divisionRefund($data);
        if ($subOrders) {
            $platformOrder = array_shift($subOrders);
            $platformAmount = $platformOrder['totalAmount'];
        } else {
            $platformAmount = $refundFee;
        }

        $refundData = [
            'msgId' => $this->__genMsgId(),
            'requestTimestamp' => date('Y-m-d H:i:s', time()),
            'mid' => $data['mch_id'],
            'tid' => $paymentSetting['tid'],
            'merOrderId' => config('ums.pre').$data['trade_id'],
            'instMid' => 'YUEDANDEFAULT',
            'platformAmount' => $platformAmount,//默认平台分账0
            'refundAmount' => $refundFee,
            'refundOrderId'=> config('ums.pre').$refundNo,
            'subOrders' => $subOrders,//无多商户一起下单，因此该处只设计当前退款的商户
            'billDate' => date('Y-m-d', time()),
        ];

        //银联重试修改订单号
        $this->changeMerOrd($refundData, $data, $resubmit);
        $result = $this->umsClientServ->refund($refundData);
        app('log')->debug('wechat doRefund end');
        app('log')->debug('wechat doRefund result 1:' . var_export($result, 1));
        $result = json_decode($result, true);
        //场景1 status 异常返回情况暂保留
        if (isset($result['status']) && !isset($result['errCode'])) {
            app('log')->debug('wechat doRefund 异常数据，查看result');
            // $orderProcessLog = [
            //     'order_id' => $data['order_id'],
            //     'company_id' => $companyId,
            //     'operator_type' => 'system',
            //     'remarks' => '订单退款',
            //     'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（银联支付渠道）',
            // ];
            // $return['status'] = 'SUCCESS';
            // $return['refund_id'] = $result['refund_id'];

            if ($result['status'] == 'SUCCESS' ) {
                $orderProcessLog = [
                    'order_id' => $data['order_id'],
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（银联支付渠道）',
                ];
                $return['status'] = 'SUCCESS';
                $return['refund_id'] = $result['refund_id'];//{"status":"SUCCESS","refund_id":"32C22202206295557545151"}异常示例，待重现
            } else {
                $orderProcessLog = [
                    'order_id' => $data['order_id'],
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（银联支付渠道），失败原因：' . $result['error_desc'],
                ];
                $return['status'] = 'FAIL';
                $return['error_code'] = $result['error_code'];
                $return['error_desc'] = $result['error_desc'];
            }
        } 

        //场景2 errCode
        if (isset($result['errCode'])) {

            if ($result['errCode'] == 'SUCCESS' ) {
                $orderProcessLog = [
                    'order_id' => $data['order_id'],
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（银联支付渠道）',
                ];
                $return['status'] = 'SUCCESS';
                $return['refund_id'] = $result['refundTargetOrderId'];
            }else{
                app('log')->debug('wechat doRefund '.$result['errCode'] . 'errMsg'.$result['errMsg'] );
                $return['status'] = 'FAIL';
                $return['error_code'] = $result['errCode'];
                $return['error_desc'] = $result['errMsg'];
                $orderProcessLog = [
                    'order_id' => $data['order_id'],
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（银联支付渠道），失败原因：' . $result['errMsg'],
                ];
            }
            
        }
        event(new OrderProcessLogEvent($orderProcessLog));
        return $return;
    }

    /**
     * 预存款充值 todo
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        throw new BadRequestHttpException('预存款暂不支持使用银联支付充值');
        
    }

    /**
     * 获取订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {

            $tradeService = new TradeService();
            $trade = $tradeService->getInfo(['company_id' => $companyId, 'trade_id' => $trade_id]);
            $qureyData = [
                'msgId' => $this->__genMsgId(),
                'requestTimestamp' => date('Y-m-d H:i:s', time()),
                'mid' => $paymentSetting['mid'],
                'tid' => $paymentSetting['tid'],
                'instMid' => 'YUEDANDEFAULT',
                'merOrderId' => config('ums.pre').$trade_id,
                'targetOrderId' => $trade['transaction_id']
            ];
            return $this->umsClientServ->queryOrder($qureyData);
        } else {
            throw new BadRequestHttpException('请检查银联支付相关配置是否完成');
        }
    }

    /**
     * 获取退款订单状态信息 
     */
    public function getRefundOrderInfo($companyId, $refund_bn)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            $refund_filter = [
                'refund_bn' => $refund_bn,
                'company_id' => $companyId
            ];
            $aftersalesRefundService = new AftersalesRefundService();
            $refundData = $aftersalesRefundService->getInfo($refund_filter);
            $qureyData = [
                'msgId' => $this->__genMsgId(),
                'requestTimestamp' => date('Y-m-d H:i:s', time()),
                'mid' => $paymentSetting['mid'],
                'tid' => $paymentSetting['tid'],
                'instMid' => 'YUEDANDEFAULT',
                'merOrderId' => config('ums.pre').$refund_bn,
                'targetOrderId' => $refundData['refund_id']
            ];
            return $this->umsClientServ->queryRefund($qureyData);
        } else {
            throw new BadRequestHttpException('请检查银联支付相关配置是否完成');
        }
    }

    public function verify($data , $return = true)
    {
        //签名
        $sign = $data['sign'];
        unset($data['sign']);
        $gensign = $this->umsClientServ->genSign($data);
        if ($gensign != $sign) {
            throw new BadRequestHttpException('验签失败，请检查银联支付相关配置是否有修改');
        }

        //不需要返回支付单信息
        if (!$return) {
            return true;
        }
        
        //返回
        return [
            'status' => $data['status'],
            'pay_type' => 'chinaums',
            'out_trade_no' =>  substr($data['merOrderId'], 4), //去前缀 32C2;
            'trade_no' => $data["targetOrderId"]
        ];
    }

    private function division($params)
    {
        $result = [];
        try {
            $orderService = $this->getOrderService('normal');
            $result = $orderService->divisionOrderByDistribution($params);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        return $result;
    }
    
    private function divisionRefund($params)
    {

        $result = [];
        try {
            $orderService = $this->getOrderService('normal');
            $result = $orderService->divisionRefundByDistribution($params);
        } catch (Exception $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        return $result;
    }

    private function changeMerOrd(&$refundData, $data, $resubmit)
    {
        app('log')->debug('wechat changeMerOrd start:' . $resubmit);
        //非重试直接返回
        if(!$resubmit){
            return $refundData;
        }

        //重置主订单号 config('ums.pre').$data['trade_id']，refundOrderId
        $refundNo = isset($data['refund_bn']) ? $data['refund_bn'] : $data['trade_id'];
        $refundData['refundOrderId'] = config('ums.pre').$refundNo.mt_rand(10,99);//添加随机数

        //重置子订单号 config('ums.pre').substr($params['order_id'],0,4).$order['sub_order_id']
        foreach ($refundData['subOrders'] as &$order) {
            $order['refundOrderId'] = config('ums.pre').substr($data['order_id'],0,4).mt_rand(10,99);
        }

        app('log')->debug('wechat changeMerOrd result:' . var_export($refundData, 1));
    }

    private function __genMsgId()
    {
        return implode(null, array_map('ord', str_split(substr(uniqid(), 5, 13), 1)));
    }

}
