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

use AdaPayBundle\Services\MemberService as AdaPayMemberService;
use AdaPayBundle\Services\OpenAccountService;
use AftersalesBundle\Entities\AftersalesRefund;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\MultiLang\MagicLangTrait;
use OrdersBundle\Services\TradeService;
use OrdersBundle\Services\MerchantTradeService;
use PaymentBundle\Interfaces\Payment;
use PaymentBundle\Services\Payments\AdaPaymentService;
use PaymentBundle\Services\Payments\AlipayAppService;
use PaymentBundle\Services\Payments\AlipayH5Service;
use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\AlipayMiniService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\OfflinePayService;
use PaymentBundle\Services\Payments\PointPayService;
use PaymentBundle\Services\Payments\WechatAppPayService;
use PaymentBundle\Services\Payments\WechatH5PayService;
use PaymentBundle\Services\Payments\WechatJSPayService;
use PaymentBundle\Services\Payments\WechatWebPayService;
use PaymentBundle\Services\Payments\BsPayService;
use PointBundle\Services\PointMemberRuleService;
use SupplierBundle\Services\SupplierOrderService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use PaymentBundle\Services\Payments\WechatPayService;
use OrdersBundle\Services\RefundErrorLogsService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use OrdersBundle\Jobs\TradeRefundStatistics;
use PaymentBundle\Services\Payments\PaypalService;

// 支付服务
class PaymentsService
{
    use MagicLangTrait;
    /**
     * 支付方式具体实现类
     */
    public $paymentService;

    public function __construct($paymentService = null)
    {
        if ($paymentService && $paymentService instanceof Payment) {
            $this->paymentService = $paymentService;
        }
    }

    /**
     * 保存支付方式配置
     */
    public function setPaymentSetting($companyId, $configData)
    {
        return $this->paymentService->setPaymentSetting($companyId, $configData);
    }

    /**
     * 获取支付方式配置信息 function
     *
     * @return array
     */
    public function getPaymentSetting($companyId)
    {
        return $this->paymentService->getPaymentSetting($companyId);
    }

    /**
     * 获取支付配置并判断是否成功 function
     *
     * @return array
     */
    private function getPayConfig($companyId, $distributorId = 0)
    {
        $payConf = $this->paymentService->getPaymentSetting($companyId, $distributorId);
        if (!$payConf) {
            throw new BadRequestHttpException('不支持支付服务，请联系商家');
        }

        return $payConf;
    }

    /**
     * 用户对储值卡进行储值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        if (!$this->paymentService) {
//            $payType = app('redis')->get('paymentTypeOpenConfig:'. sha1($data['company_id']));
            if ($data['pay_type'] == 'wxpay') {
                $data['pay_type'] = 'wxpay';
                $this->paymentService = new WechatPayService();
            }
        }
        $this->getPayConfig($data['company_id']);
        $result = $this->paymentService->depositRecharge($authorizerAppId, $wxaAppId, $data);

        $result['trade_info'] = [
            'order_id' => $data['deposit_trade_id'],
            'trade_id' => $data['deposit_trade_id'],
        ];

        return $result;
    }

    /**
     * 用户进行付款支付
     */
    public function doPayment($authorizerAppId, $wxaAppId, array $data, $isDiscount = false)
    {
        $data['authorizer_appid'] = $authorizerAppId;
        $data['point'] = $data['point'] ?? 0;

        // 添加PayPal支持
        if ($data['pay_type'] == 'paypal') {
            $this->paymentService = new PaypalService($data['distributor_id'] ?? 0);
            $payConf = $this->getPayConfig($data['company_id'], $data['distributor_id'] ?? 0);
        } else if ('wxpay' == substr($data['pay_type'], 0, 5)) {
            $payConf = $this->getPayConfig($data['company_id'], $data['distributor_id']);
            app('log')->info('payConf===>'.var_export($payConf,1));
            $data['mch_id'] = $payConf['merchant_id'];
        } else {
            $payConf = $this->getPayConfig($data['company_id']);
        }

        // 添加交易单号
        $tradeService = new TradeService();
        // $isDiscount 是否需要计算优惠
        // 门店直接支付需要计算优惠信息
        // 创建订单已经在订单中计算好了优惠信息，那么则不需要在计算了
        $returnUrl = '';
        if (isset($data['return_url'])) {
            $returnUrl = $data['return_url'];
        }

        $authCode = '';
        if (isset($data['auth_code'])) {
            $authCode = trim($data['auth_code']);
        }

        $distributorInfo = $data['distributor_info'] ?? [];

        $newData = [];
        if (isset($data['order_id']) && $data['order_id']) {
            if (in_array($data['pay_type'], ['adapay', 'bspay'])) {
                $newData = $tradeService->getInfo(['order_id' => $data['order_id'], 'pay_type' => $data['pay_type'], 'pay_channel' => $data['pay_channel'], 'pay_fee' => $data['pay_fee']]);
                if ($newData && $newData['payment_params']) {
                    $result = json_decode($newData['payment_params'], true);
                    $result['trade_info'] = [
                        'order_id' => $data['order_id'],
                        'trade_id' => $newData['trade_id'],
                        'trade_source_type' => $newData['trade_source_type'],
                    ];
                    return $result;
                }
                $newData = [];
            } else {
                $newData = $tradeService->getInfo(['order_id' => $data['order_id'], 'pay_type' => $data['pay_type'], 'pay_fee' => $data['pay_fee']]);
            }
        }
        if (!$newData) {
            $newData = $tradeService->create($data, $isDiscount);
        }

        $attributes = [
            'body' => $data['body'],
            'order_id' => $data['order_id'] ?? '',
            'detail' => $data['detail'] ? $data['detail'] : $data['body'],
            'trade_id' => $newData['trade_id'],
            'pay_fee' => $newData['pay_fee'],
            'open_id' => $data['open_id'],
            'company_id' => $data['company_id'],
            'mobile' => $data['mobile'],
            'user_id' => $data['user_id'],
            'shop_id' => isset($data['shop_id']) ? $data['shop_id'] : '',
            'member_card_code' => isset($data['member_card_code']) ? $data['member_card_code'] : '' ,
            'return_url' => $returnUrl,
            'auth_code' => $authCode,
            'trade_source_type' => $newData['trade_source_type'],
            'distributor_info' => $distributorInfo,
            'client_ip' => $data['client_ip']??0,
        ];

        if (in_array($data['pay_type'], ['adapay', 'bspay'])) {
            $attributes['pay_channel'] = $data['pay_channel'];
            $attributes['source'] = $data['source'] ?? '';

            //获取汇付的合单支付参数
            $supplierOrderService = new SupplierOrderService();
            $supplierOrderService->getSubOrders($attributes);
        }
        if ($data['pay_type'] == 'alipaymini') {
            $attributes['alipay_user_id'] = $data['alipay_user_id'];
        }
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            if ($data['pay_type'] != 'point' && isset($data['point']) && $data['point'] > 0 && ! empty($pointNewData)) {
                $pointPaymentService = new PointPayService();
                $pointAttribute = $attributes;
                $pointAttribute['trade_id'] = $pointNewData['trade_id'];
                $pointAttribute['pay_fee'] = $pointNewData['pay_fee'];
                $pointPaymentService->doPay($authorizerAppId, $wxaAppId, $pointAttribute);
            }

            //如果为0元订单，直接支付成功
            if (isset($newData['pay_status']) && $newData['pay_status']) {
                $result = ['pay_status' => true];
            } else {
                $result = [];
                if (!($data['is_create_prescription_order'] ?? 0)) { // 处方药订单创建完订单后不支付，需补充处方信息
                    $result = $this->paymentService->doPay($authorizerAppId, $wxaAppId, $attributes);
                }

                $result['trade_info'] = [
                    'order_id' => $attributes['order_id'],
                    'trade_id' => $attributes['trade_id'],
                    'trade_source_type' => $newData['trade_source_type'],
                ];
            }

            $conn->commit();
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
        return $result;
    }

    /**
     * 订单退款
     * 订单退款到指定账户
     */
    public function doRefund($companyId, $wxaAppId, array $data)
    {
        $data['company_id'] = $companyId;

        if ('wxpay' == substr($data['pay_type'], 0, 5)) {
            $payConf = $this->getPayConfig($data['company_id'], $data['distributor_id']);
            if (!$payConf || !isset($payConf['cert_url']) || !isset($payConf['cert_key_url'])) {
                $result['status'] = 'FAIL';
                $result['error_desc'] = '请检查微信支付相关配置是否完成';
                return $result;
            }
        } elseif ('alipay' == substr($data['pay_type'], 0, 6)) {
            $payConf = $this->getPayConfig($data['company_id']);
            if (!$payConf) {
                $result['status'] = 'FAIL';
                $result['error_desc'] = '请检查支付宝支付相关配置是否完成';
                return $result;
            }
        }

        try {
            // 执行付款
            if (!isset($data['refund_fee'])) {
                $data['refund_fee'] = $data['pay_fee'];
            }
            $result = $this->paymentService->doRefund($companyId, $wxaAppId, $data);
            if ($result['status'] == 'FAIL') {
                $this->saveRefundError($companyId, $wxaAppId, $data, $result);

                // $result['status'] = 'SUCCESS';
                $result['refund_id'] = 0;
            } else {
                if (isset($data['refund_bn'])) {
                    $refundFilter = [
                        'company_id' => $data['company_id'],
                        'refund_bn' => $data['refund_bn'],
                    ];
                    $aftersalesRefundRepository = app('registry')->getManager('default')->getRepository(AftersalesRefund::class);
                    $aftersalesRefundRepository->updateOneBy($refundFilter, ['refund_id' => $result['refund_id']]);
                }
            }
        } catch (\Exception $e) {
            $result['status'] = 'FAIL';
            $result['error_code'] = $e->getCode();
            $result['error_desc'] = $e->getMessage();
            app('log')->debug('wechat doRefund result:' . var_export($result, true));

            $this->saveRefundError($companyId, $wxaAppId, $data, $result);
            // $result['status'] = 'SUCCESS';
            $result['refund_id'] = 0;
        }



        if ($result['status'] == 'SUCCESS') {
            $job = (new TradeRefundStatistics($data))->delay(5);
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($job);
        }
        return $result;
    }

    public function saveRefundError($companyId, $wxaAppId, $data, $result)
    {
        $refundErrorLogsService = new RefundErrorLogsService();
        $errorLogsData = [
            'company_id' => $companyId,
            'order_id' => $data['order_id'],
            'wxa_appid' => $wxaAppId,
            'data_json' => json_encode($data),
            'status' => $result['status'],
            'error_code' => $result['error_code'],
            'error_desc' => $result['error_desc'],
            'merchant_id' => $data['merchant_id'],
            'supplier_id' => $data['supplier_id'] ?? 0,
            'distributor_id' => $data['distributor_id'],
        ];
        $refundErrorLogsService->create($errorLogsData);
        return true;
    }

    /**
     * 商家支付
     * 商家打款到指定账户
     */
    public function merchantPayment($companyId, $wxaAppId, array$data)
    {
        $data['company_id'] = $companyId;

        $payConf = $this->getPayConfig($data['company_id']);
        if (!$payConf || !isset($payConf['cert_url']) || !isset($payConf['cert_key_url'])) {
            $result['status'] = 'FAIL';
            $result['error_desc'] = '请检查微信支付相关配置是否完成';
            return $result;
        }

        // 暂时为微信企业付款
        $data['payment_action'] = 'WECHAT';
        $data['check_name'] = 'NO_CHECK';
        $data['mchid'] = $payConf['merchant_id'];
        $data['mch_appid'] = $wxaAppId;
        $merchantTradeService = new MerchantTradeService();
        $data = $merchantTradeService->create($data);

        try {
            // 执行付款
            $result = $this->paymentService->merchantPayment($companyId, $wxaAppId, $data);
        } catch (\Exception $e) {
            $result['status'] = 'FAIL';
            $result['error_code'] = $e->getCode();
            $result['error_desc'] = $e->getMessage();
            app('log')->debug('wechat merchantPayment result:' . var_export($result, true));
        }

        $filter['merchant_trade_id'] = $data['merchant_trade_id'];
        $filter['company_id'] = $companyId;
        return $merchantTradeService->updateStatus($filter, $result);
    }

    /**
     * 用户进行付款支付
     */
    public function query($data)
    {
        try {
            return $this->paymentService->query($data);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 获取支付订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        return $this->paymentService->getPayOrderInfo($companyId, $trade_id);
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $refund_bn)
    {
        return $this->paymentService->getRefundOrderInfo($companyId, $refund_bn);
    }

    public function getPaymentSettingList($type, $company_id, $distributorId, $orderType = null)
    {
        $lang = $this->getLang();
    
        //adapay
        $service = new AdaPaymentService();
        $adaPay = $service->getPaymentSetting($company_id);
        $adaPayPayment = [];
        if ($adaPay && $adaPay['is_open']) {
            $adaPayPayment = $adaPay['pay_channel'];
        }

        // 汇付斗拱支付
        $bsPayService = new BsPayService();
        $bsPaySetting = $bsPayService->getPaymentSetting($company_id);
        $bsPayPayment = [];
        if ($bsPaySetting && $bsPaySetting['is_open']) {
            $bsPayPayment = $bsPaySetting['pay_channel'];
        }

        // PayPal支付
        $paypalService = new \PaymentBundle\Services\Payments\PaypalService($distributorId);
        $paypalSetting = $paypalService->getPaymentSetting($company_id);
        $hasPaypal = !empty($paypalSetting) && ($paypalSetting['is_open'] ?? false);

        $offlinePayService = new OfflinePayService();
        $offlinePaySetting = $offlinePayService->getPaymentSetting($company_id);
        $result = [];

        // 如果是国际支付场景，添加PayPal支付选项
        if ($type == 'international' || $type == 'h5' || $type == 'pc') {
            if ($hasPaypal) {
                $result[] = [
                    'pay_type_code' => 'paypal',
                    'pay_type_name' => 'PayPal'
                ];
            }
        }

        switch ($type) {
            case 'wxPlatform':
                if (in_array('wx_pub', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_pub',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } elseif (in_array('wx_pub', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'wx_pub',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } else {
                    $service = new WechatJSPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpayjs',
                            'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                        ];
                    }
                }
                break;
            case 'h5':
                if (in_array('wx_lite', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } elseif (in_array('wx_lite', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } else {
                    $service = new WechatH5PayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpayh5',
                            'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                        ];
                    }
                }

                if (in_array('alipay_wap', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'alipay_wap',
                        'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                    ];
                } elseif (in_array('alipay_wap', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'alipay_wap',
                        'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                    ];
                } else {
                    $service = new AlipayH5Service($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'alipayh5',
                            'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                        ];
                    }
                }
                // 线下转账
                if (!empty($offlinePaySetting) && $offlinePaySetting['is_open']) {
                    $tmpPay = [
                        'pay_type_code' => 'offline_pay',
                        'pay_type_name' => $offlinePaySetting['pay_name'],
                    ];
                    $langName = app('redis')->get('lang_pay_name_offline_pay:'.$lang);
                    if(!empty($langName)){
                        $tmpPay['pay_type_name'] = $langName;
                    }
                    $result[] = $tmpPay;
                }
                break;
            case 'app':
                if (in_array('wx_lite', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } elseif (in_array('wx_lite', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } else {
                    $service = new WechatAppPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpayapp',
                            'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                        ];
                    }
                }

                if (in_array('alipay', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'alipay',
                        'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                    ];
                } else {
                    $service = new AlipayAppService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'alipayapp',
                            'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                        ];
                    }
                }
                // 线下转账
                if (!empty($offlinePaySetting) && $offlinePaySetting['is_open']) {
                    $result[] = [
                        'pay_type_code' => 'offline_pay',
                        'pay_type_name' => $offlinePaySetting['pay_name'],
                    ];
                }
                break;
            case 'pc':
                if (in_array('wx_pub', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_qr',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } elseif (in_array('wx_qr', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'wx_qr',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } else {
                    $service = new WechatWebPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpaypc',
                            'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                        ];
                    }
                }

                if (in_array('alipay_qr', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'alipay_qr',
                        'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                    ];
                } elseif (in_array('alipay_qr', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'alipay_qr',
                        'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                    ];
                } else {
                    $service = new AlipayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'alipay',
                            'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                        ];
                    }
                }
                // 线下转账
                if (!empty($offlinePaySetting) && $offlinePaySetting['is_open']) {
                    $result[] = [
                        'pay_type_code' => 'offline_pay',
                        'pay_type_name' => $offlinePaySetting['pay_name'],
                    ];
                }
                break;
            case 'alipaymini':
                $service = new AlipayMiniService($distributorId);
                $setting = $service->getPaymentSetting($company_id);
                if (!empty($setting) && $setting['is_open'] == 'true') {
                    $result[] = [
                        'pay_type_code' => 'alipaymini',
                        'pay_type_name' => $lang == 'en-CN' ? 'Alipay' : '支付宝'
                    ];
                }
                break;
            case 'wxMiniProgram':
            default:
                if (in_array('wx_lite', $adaPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } elseif (in_array('wx_lite', $bsPayPayment)) {
                    $result[] = [
                        'pay_type_code' => 'bspay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                    ];
                } else {
                    //微信设置
                    $service = new WechatPayService($distributorId);
                    $setting = $service->getPaymentSetting($company_id);
                    if (!empty($setting) && $setting['is_open'] == 'true') {
                        $result[] = [
                            'pay_type_code' => 'wxpay',
                            'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay' : '微信支付'
                        ];
                    }
                }

                //银行支付
                $umservice = new ChinaumsPayService();
                $ums = $umservice->getPaymentSetting($company_id);
                if ( !empty($ums) && $ums['is_open']) {
                    $result[] = [
                        'pay_type_code' => 'chinaums',
                        'pay_type_name' => $lang == 'en-CN' ? 'WeChat Pay-UnionPay' : '微信支付-银联'
                    ];
                }

                // 线下转账
                if (!empty($offlinePaySetting) && $offlinePaySetting['is_open'] == 'true') {
                    $result[] = [
                        'pay_type_code' => 'offline_pay',
                        'pay_type_name' => $offlinePaySetting['pay_name'],
                    ];
                }

                break;
        }

        if ($orderType != 'normal_employee_purchase') {
            // 预存款
            // $result[] = [
            //     'pay_type_code' => 'deposit',
            //     'pay_type_name' => '预存款支付'
            // ];
        }
        // 数云模式
        if (config('common.oem-shuyun')) {
            $pointRuleService = new PointMemberRuleService($company_id);
            if ($pointRuleService->getIsOpenPoint()) {
                $pointRule = $pointRuleService->getPointRule($company_id);
                $pointPayMethod = [
                    'pay_type_code' => 'point',
                    'pay_type_name' => $lang == 'en-CN' ? 'Points Payment' : '积分支付'
                ];
                if ($pointRule['point_pay_first']) {
                    array_unshift($result, $pointPayMethod);
                } else {
                    $result[] = $pointPayMethod;
                }
            }
        }

        return $result;
    }

    /**
     * 获取支付配置开关状态列表
     * 
     * @param int $companyId 公司ID
     * @param int $distributorId 店铺ID，0表示平台
     * @param string $operatorType 操作员类型
     * @param int $operatorId 操作员ID
     * @return array 返回各支付方式的开关状态
     */
    public function getPaymentOpenStatusList($companyId)
    {
        $result = [];
        
        // 微信支付配置
        // 使用 getDefault = true，会根据 payment_subject 自动调整到正确的配置
        $wxpayService = new WechatPayService(0, true);
        $wxpayServiceWrapper = new PaymentsService($wxpayService);
        $wxpayConfig = $wxpayServiceWrapper->getPaymentSetting($companyId);
        $result['wxpay'] = [
            'is_open' => $this->normalizeIsOpen($wxpayConfig['is_open'] ?? false),
        ];
        
        // 支付宝配置
        // 使用 getDefault = true，会根据 payment_subject 自动调整到正确的配置
        $alipayService = new AlipayService(0, true);
        $alipayServiceWrapper = new PaymentsService($alipayService);
        $alipayConfig = $alipayServiceWrapper->getPaymentSetting($companyId);
        $result['alipay'] = [
            'is_open' => $this->normalizeIsOpen($alipayConfig['is_open'] ?? false),
        ];
        
        $chinaumsService = new ChinaumsPayService();
        $chinaumsServiceWrapper = new PaymentsService($chinaumsService);
        $chinaumsConfig = $chinaumsServiceWrapper->getPaymentSetting($companyId);
        $result['chinaumspay'] = [
            'is_open' => $this->normalizeIsOpen($chinaumsConfig['is_open'] ?? false),
        ];
        
        return $result;
    }

    /**
     * 统一is_open字段格式为bool类型
     * 
     * @param mixed $value
     * @return bool
     */
    private function normalizeIsOpen($value)
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        if (is_numeric($value)) {
            return (bool)$value;
        }
        
        return false;
    }
}
