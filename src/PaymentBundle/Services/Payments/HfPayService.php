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

use AftersalesBundle\Entities\AftersalesRefund;
use DistributionBundle\Entities\Distributor;
use HfPayBundle\Entities\HfpayLedgerConfig;
use HfPayBundle\Events\HfpayRefundSuccessEvent;
use HfPayBundle\Services\HfBaseService;
use KaquanBundle\Services\VipGradeOrderService;
use OrdersBundle\Entities\NormalOrders;
use OrdersBundle\Entities\NormalOrdersItems;
use PaymentBundle\Interfaces\Payment;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use HfPayBundle\Services\HfpayService as HfpayTransService;

class HfPayService implements Payment
{
    public $orderItemsRepository;
    public $orderRepository;
    public $hfpayLedgerConfigRepository;
    public $distributorRepository;
    public $aftersalesRefundRepository;

    public function __construct()
    {
        $this->orderItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);
        $this->orderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $this->hfpayLedgerConfigRepository = app('registry')->getManager('default')->getRepository(HfpayLedgerConfig::class);
        $this->distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
        $this->aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
    }

    /**
     * 支付配置
     * @param $companyId
     * @param $params
     * @return mixed
     */
    public function setPaymentSetting($companyId, $data)
    {
        // ShopEx EcShopX Service Component
        if (isset($data['is_open']) && $data['is_open']) {
            app('redis')->set('paymentTypeOpenConfig:' . sha1($companyId), 'hfpay');
        }

        if ($pfx_file = $data['pfx_file']) {
            $data['pfx_file'] = base64_encode(file_get_contents($pfx_file));
        }

        if ($ca_pfx_file = $data['ca_pfx_file']) {
            $data['ca_pfx_file'] = file_get_contents($ca_pfx_file);
        }

        if ($oca31_pfx_file = $data['oca31_pfx_file']) {
            $data['oca31_pfx_file'] = file_get_contents($oca31_pfx_file);
        }

        app('redis')->set('hfPayment:companyId:' . $data['mer_cust_id'], $companyId);
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 获取支付配置
     * @param $companyId
     * @return mixed
     */
    public function getPaymentSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if (empty($data)) {
            return [];
        }
        $data = json_decode($data, true);
        $pfx_file_path = base64_decode($data['pfx_file']); //私钥
        $trusted_ca_cert_file_path = $data['ca_pfx_file'];//公钥
        $trusted_ca_cert_file_path_31 = $data['oca31_pfx_file']; //公钥31

        //商户私钥
        $certkeyFile = 'chinapnrPayment/' . $data['mer_cust_id'] . '/cfca.pfx';
        if (app('filesystem')->exists($certkeyFile)) {//本地有证书
            //比对新上传和旧证书是否相同
            if (md5($pfx_file_path) != md5(file_get_contents(storage_path() . '/' . $certkeyFile))) {
                app('filesystem')->put($certkeyFile, $pfx_file_path);
            }
        } else {
            app('filesystem')->put($certkeyFile, $pfx_file_path);
        }

        //商户公钥
        $certFile = 'chinapnrPayment/' . $data['mer_cust_id'] . '/CFCA_ACS_CA.cer';
        if (app('filesystem')->exists($certFile)) {//本地有证书
            //比对新上传和旧证书是否相同
            if (md5($trusted_ca_cert_file_path) != md5(file_get_contents(storage_path() . '/' . $certFile))) {
                app('filesystem')->put($certFile, $trusted_ca_cert_file_path);
            }
        } else {
            app('filesystem')->put($certFile, $trusted_ca_cert_file_path);
        }

        //商户公钥31
        $cert31File = 'chinapnrPayment/' . $data['mer_cust_id'] . '/CFCA_ACS_OCA31.cer';
        if (app('filesystem')->exists($cert31File)) {//本地有证书
            //比对新上传和旧证书是否相同
            if (md5($trusted_ca_cert_file_path_31) != md5(file_get_contents(storage_path() . '/' . $cert31File))) {
                app('filesystem')->put($cert31File, $trusted_ca_cert_file_path_31);
            }
        } else {
            app('filesystem')->put($cert31File, $trusted_ca_cert_file_path_31);
        }

        $data['pfx_file_name'] = 'cfca.pfx';
        $data['pfx_file_url'] = storage_path() . '/' . $certkeyFile;
        $data['ca_pfx_file_name'] = 'CFCA_ACS_CA.cer';
        $data['ca_pfx_file_url'] = storage_path() . '/' . $certFile;
        $data['oca31_pfx_file_name'] = 'CFCA_ACS_OCA31.cer';
        $data['oca31_pfx_file_url'] = storage_path() . '/' . $cert31File;
        return $data;
    }

    /**
     * 会员储值卡支付储值支付
     * @param $authorizerAppId
     * @param $wxaAppId
     * @param array $data
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        //汇付储值
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (!$paymentSetting) {
            throw new BadRequestHttpException('汇付天下支付配置缺失');
        }

        if ($paymentSetting['is_open'] == 'false') {
            throw new BadRequestHttpException('请检查汇付天下支付相关配置是否完成');
        }

        $dev_info_json = [
            'ipAddr' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'devType' => '2',
            'MAC' => 'D4-81-D7-F0-42-F8', //暂时写死无实际用处，客户端没有获取MAC地址
        ];
        $params = [
            'app_pay_type' => '07',
            'order_id' => $data['deposit_trade_id'],
            'order_date' => date('Ymd', $data['time_start']),
            'div_details' => '',
            'trans_amt' => (string)bcdiv($data['money'], 100, 2),
            'buyer_id' => $data['open_id'],
            'app_id' => $wxaAppId,
            'goods_tag' => $this->gbk_strlen($data['shop_name']) > 10 ? mb_substr($data['shop_name'], 0, 10) : $data['shop_name'],
            'goods_desc' => $data['detail'],
            'dev_info_json' => json_encode($dev_info_json),
            'mer_priv' => 'recharge'
        ];

        //资金收集到总部账户
        $params['in_cust_id'] = $paymentSetting['mer_cust_id'];
        $params['div_type'] = '';

        app('log')->debug('hfpay payment recharge params:' . json_encode($params));
        $service = new HfpayTransService($data['company_id']);
        $result = $service->pay012($params);
        app('log')->debug('hfpay recharge pay012 return data:'.__LINE__.json_encode($result));
        if ($result['resp_code'] == 'C00002') {
            $pay_info = json_decode($result['pay_info'], true);

            return [
                'appId' => $pay_info['appId'],
                'nonceStr' => $pay_info['nonceStr'],
                'package' => $pay_info['package'],
                'paySign' => $pay_info['paySign'],
                'signType' => $pay_info['signType'],
                'timeStamp' => $pay_info['timeStamp'],
                'team_id' => null,
            ];
        } else {
            app('log')->debug('hfpay payment recharge params:' . json_encode($params));
            app('log')->debug('hfpay payment recharge Message Error result:' . json_encode($result));

            throw new BadRequestHttpException("支付通道错误：".$result['resp_desc']);
        }
    }

    /**
     * 进行支付
     * @param $authorizerAppId
     * @param $wxaAppId
     * @param array $data
     * @throws \Exception
     * @return array
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (!$paymentSetting) {
            throw new BadRequestHttpException('汇付天下支付配置缺失');
        }

        if ($paymentSetting['is_open'] == 'false') {
            throw new BadRequestHttpException('请检查汇付天下支付相关配置是否完成');
        }

        //会员卡购买
        if (isset($data['trade_source_type']) && $data['trade_source_type'] == 'membercard') {
            $vip_grade_order_service = new VipGradeOrderService();
            $orderInfo = $vip_grade_order_service->getOrderInfo($data['company_id'], $data['order_id']);
            if (!$orderInfo) {
                throw new BadRequestHttpException('缺少订单信息!');
            }
            $order_date = date('Ymd', $orderInfo['orderInfo']['created']);
            $app_pay_type = '07';
        } else {
            $orderInfo = $this->getOrderInfo($data['order_id']);
            if (!$orderInfo) {
                throw new BadRequestHttpException('缺少订单信息!');
            }
            //订单下单时间用于退款时
            $order_date = date('Ymd', $orderInfo['create_time']);
            $app_pay_type = $orderInfo['app_pay_type'];
        }
        $dev_info_json = [
            'ipAddr' => $_SERVER['SERVER_ADDR'] ?? '127.0.0.1',
            'devType' => '2',
            'MAC' => 'D4-81-D7-F0-42-F8', //暂时写死无实际用处，客户端没有获取MAC地址
        ];
        $params = [
            'app_pay_type' => $app_pay_type == '00' ? '07' : $app_pay_type,
            'order_id' => $data['trade_id'],
            'order_date' => $order_date,
            'div_details' => '',
            'trans_amt' => (string)bcdiv($data['pay_fee'], 100, 2),
            'buyer_id' => $data['open_id'],
            'app_id' => $wxaAppId,
            'goods_tag' => $this->gbk_strlen($data['body']) > 10 ? mb_substr($data['body'], 0, 10) : $data['body'],
            'goods_desc' => $data['body'],
            'dev_info_json' => json_encode($dev_info_json),
            'mer_priv' => 'pay'
        ];
        //会员卡购买，资金归集到平台账户
        if (isset($data['trade_source_type']) && $data['trade_source_type'] == 'membercard') {
            $params['in_cust_id'] = $paymentSetting['mer_cust_id'];
            $params['div_type'] = '';
        } else {
            //资金收集到总部账户
            if ($orderInfo['is_profitsharing'] == 1) {
                $params['in_cust_id'] = $paymentSetting['mer_cust_id'];
                $params['div_type'] = '';
            }
            //订单需分账处理
            if ($orderInfo['is_profitsharing'] == 2) {
                $params['in_cust_id'] = '';
                $params['div_type'] = '1'; //延迟分账
            }
        }

        app('log')->debug('hfpay pay012 params:' . json_encode($params));
        $service = new HfpayTransService($data['company_id']);
        $result = $service->pay012($params);
        app('log')->debug('hfpay pay012 return data:'.__LINE__.json_encode($result));
        if ($result['resp_code'] == 'C00002') {
            $pay_info = json_decode($result['pay_info'], true);

            return [
                'appId' => $pay_info['appId'],
                'nonceStr' => $pay_info['nonceStr'],
                'package' => $pay_info['package'],
                'paySign' => $pay_info['paySign'],
                'signType' => $pay_info['signType'],
                'timeStamp' => $pay_info['timeStamp'],
                'team_id' => null,
            ];
        } else {
            app('log')->debug('hfpay payment params:' . json_encode($params));
            app('log')->debug('hfpay payment Message Error result:' . json_encode($result));

            throw new BadRequestHttpException("支付通道错误：".$result['resp_desc']);
        }
    }

    /**
     * @param $string
     * @return int
     *
     * 转换成gbk并返回字符串长度
     */
    public function gbk_strlen($string)
    {
        $encode = mb_detect_encoding($string, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
        if ($encode == 'UTF-8') {
            $string = mb_convert_encoding($string, 'GBK', 'UTF-8');
        }

        return strlen($string);
    }

    /**
     * 退款
     * @param $companyId
     * @param $wxaAppId
     * @param $data
     * @return mixed
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (empty($paymentSetting)) {
            throw new BadRequestHttpException('商户支付配置缺失');
        }
        app('log')->debug('hfpay doRefund start order_id=>' . $data['order_id']);
        $orderInfo = $this->getOrderInfo($data['order_id']);
        if (!$orderInfo) {
            throw new BadRequestHttpException('缺少订单信息!');
        }

        //生成退款汇付id
        $hf_base_service = new HfBaseService();
        $hf_order_id = $hf_base_service->getOrderId();
        //退款单更新汇付id
        $aftersales_refund_filter = [
            'refund_bn' => $data['refund_bn']
        ];
        $aftersales_refund_data = [
            'hf_order_id' => $hf_order_id
        ];
        $this->aftersalesRefundRepository->updateOneBy($aftersales_refund_filter, $aftersales_refund_data);
        //汇付参数组装
        $dev_info_json = [
            'ipAddr' => get_client_ip(),
            'devType' => '2',
            'MAC' => 'D4-81-D7-F0-42-F8', //暂时写死无实际用处，客户端没有获取MAC地址
        ];
        $params = [
            'order_id' => $hf_order_id,
            'order_date' => date('Ymd', $data['create_time']),
            'org_order_date' => date('Ymd', $orderInfo['create_time']),
            'org_order_id' => $data['trade_id'],
            'trans_amt' => (isset($data['refund_fee']) ? bcdiv($data['refund_fee'], 100, 2) : null),
            'dev_info_json' => json_encode($dev_info_json),
            'mer_priv' => 'refund'
        ];
        //非分账模式，钱从平台账户出
        if ($orderInfo['is_profitsharing'] == 1) {
            $params['in_cust_id'] = $paymentSetting['mer_cust_id'];
        } else {
            $params['in_cust_id'] = '';
        }
        app('log')->debug('hfpay reb001 params--->>>'.json_encode($params));
        $service = new HfpayTransService($data['company_id']);
        $result = $service->reb001($params);
        app('log')->debug('hfpay reb001 result--->>>'.json_encode($result));
        if (isset($result['resp_code']) && $result['resp_code'] == 'C00002') {
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $result['order_id'];

            $eventData = [
                'order_id' => $orderInfo['order_id'],
                'refund_bn' => $data['refund_bn']
            ];
            event(new HfpayRefundSuccessEvent($eventData));
        } else {
            $return['status'] = 'FAIL';
            $return['error_code'] = $result['resp_code'];
            $return['error_desc'] = $result['resp_desc'];
            app('log')->debug('hfpay refund fail Result:' . __LINE__ . ':' . json_encode($result));
        }

        return $return;
    }

    public function getOrderInfo($orderId)
    {
        $filter = [
            'order_id' => $orderId
        ];
        $orderInfo = $this->orderRepository->getInfo($filter);
        if (!$orderInfo) {
            return [];
        }
        $orderInfo['items'] = $this->orderItemsRepository->getList($filter);
        if (!$orderInfo['items']['total_count']) {
            return [];
        }

        return $orderInfo;
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'hfPaymentSetting:' . sha1($companyId);
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
