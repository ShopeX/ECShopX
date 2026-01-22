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
use OrdersBundle\Services\MerchantTradeService;
use CommunityBundle\Services\CommunityChiefCashWithdrawalService;
use DistributionBundle\Services\CashWithdrawalService as DistributionCashWithdrawalService;
use PopularizeBundle\Services\CashWithdrawalService as PopularizeCashWithdrawalService;

class WechatPayService implements Payment
{
    use PaymentSubjectTrait;
    public $openid = '';
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
        if (isset($data['is_open']) && $data['is_open']) {
            app('redis')->set('paymentTypeOpenConfig:' . sha1($companyId), 'wxpay');
        }
        $redisData = app('redis')->get($this->genReidsId($companyId));
        if ($redisData) {
            $redisData = json_decode($redisData, 1);
        }
        if ($cert = $data['cert']) {
            if ($cert->getClientOriginalName() != 'apiclient_cert.pem') {
                throw new BadRequestHttpException('请上传apiclient_cert.pem');
            }
            //$this->getFileSystem()->putFileAs('wechatPayment/' . $data['merchant_id'], $cert, 'apiclient_cert.pem');
            $data['cert'] = file_get_contents($cert);
        } else {
            $data['cert'] = $redisData['cert'] ?? null;
        }

        if ($certKey = $data['cert_key']) {
            if ($certKey->getClientOriginalName() != 'apiclient_key.pem') {
                throw new BadRequestHttpException('请上传apiclient_key.pem');
            }
            //$this->getFileSystem()->putFileAs('wechatPayment/' . $data['merchant_id'], $certKey, 'apiclient_key.pem');
            $data['cert_key'] = file_get_contents($certKey);
        } else {
            $data['cert_key'] = $redisData['cert_key'] ?? null;
        }

        if (isset($data['app_id']) && $data['app_id']) {
            app('redis')->set('wechatPayment:companyId:' . $data['app_id'], $companyId);
        }

        if (isset($data['app_app_id']) && $data['app_app_id']) {
            app('redis')->set('wechatAppPayment:companyId:' . $data['app_app_id'], $companyId);
        }

        if (isset($data['is_servicer']) && $data['is_servicer'] == 'true') {
            if (!$data['servicer_merchant_id'] || !$data['servicer_app_id']) {
                throw new BadRequestHttpException('开启特约商户服务商APPID和服务商商户号必填！');
            }

            app('redis')->set('wechatServicerPayment:companyId:' . $data['servicer_app_id'], $companyId);
        }

        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    private function getFileSystem()
    {
        return app('filesystem')->disk('import-file');
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        app('log')->debug('payment::getPaymentSettingList::getDefault=>' . $this->getDefault.'distributorId=>' . $this->distributorId);
        // 根据店铺收款主体类型决定实际使用的distributorId
        if ($this->getDefault && $this->distributorId > 0) {
            $actualDistributorId = $this->getActualDistributorId($this->distributorId, $companyId);
            app('log')->debug('payment::getPaymentSettingList::actualDistributorId=>' . $actualDistributorId);
            if ($actualDistributorId != $this->distributorId) {
                $this->distributorId = $actualDistributorId;
            }
        }
        
        
        $data = app('redis')->get($this->genReidsId($companyId));
        app('log')->debug('payment::getPaymentSettingList::data=>' . $data);
        // 增加了支付主体的字段，平台or店铺，无论配置是否为空，如果是店铺，则取店铺配置，如果是平台，则取平台配置
        //不存在店铺配置取平台的配置
        // if (!$data && $this->getDefault && $this->distributorId > 0) {
        //     $this->distributorId = 0;
        //     $data = app('redis')->get($this->genReidsId($companyId));
        // }

        if ($data) {
            $data = json_decode($data, true);
            $data['app_id'] = $data['app_id'] ?? '';
            //商户证书
            $certFile = 'wechatPayment/' . $data['merchant_id'] . '/apiclient_cert.pem';
            if (isset($data['cert'])) { //redis存在
                if (app('filesystem')->exists($certFile)) {//本地有证书
                    //比对新上传和旧证书是否相同
                    if (md5($data['cert']) != md5(app('filesystem')->get($certFile))) {
                        app('filesystem')->put($certFile, $data['cert']);
                    }
                } else {
                    app('filesystem')->put($certFile, $data['cert']);
                }
            } else {//redis不存在，从七牛拉取
                if ($this->getFileSystem()->exists($certFile)) {//如果七牛存在证书
                    $url = $this->getFileSystem()->privateDownloadUrl($certFile);
                    $client = new Client();
                    $content = $client->get($url)->getBody()->getContents();
                    if (app('filesystem')->exists($certFile)) {//本地有证书
                        //比对新上传和旧证书是否相同
                        if (md5($content) != md5(app('filesystem')->get($certFile))) {
                            app('filesystem')->put($certFile, $content);
                        }
                    } else {
                        app('filesystem')->put($certFile, $content);
                    }
                    //把从七牛获取到的内容重新存入到redis
                    $data['cert'] = $content;
                    app('redis')->set($this->genReidsId($companyId), json_encode($data));
                }
            }
            $data['cert_name'] = 'apiclient_cert.pem';
            $data['cert_url'] = app('filesystem')->path($certFile);

            //商户证书秘钥
            $certKeyFile = 'wechatPayment/' . $data['merchant_id'] . '/apiclient_key.pem';
            if (isset($data['cert_key'])) { //redis存在
                if (app('filesystem')->exists($certKeyFile)) {//本地有证书秘钥
                    if (md5($data['cert_key']) != md5(app('filesystem')->get($certKeyFile))) {
                        app('filesystem')->put($certKeyFile, $data['cert_key']);
                    }
                } else {
                    app('filesystem')->put($certKeyFile, $data['cert_key']);
                }
            } else {//redis不存在，从七牛拉取
                if ($this->getFileSystem()->exists($certKeyFile)) {//如果七牛存在秘钥证书
                    //获取七牛秘钥证书内容
                    $url = $this->getFileSystem()->privateDownloadUrl($certKeyFile);
                    $client = new Client();
                    $content = $client->get($url)->getBody()->getContents();
                    if (app('filesystem')->exists($certKeyFile)) {//本地有秘钥证书
                        if (md5($content) != md5(app('filesystem')->get($certKeyFile))) {
                            app('filesystem')->put($certKeyFile, $content);
                        }
                    } else {
                        app('filesystem')->put($certKeyFile, $content);
                    }
                    //把从七牛获取到的内容重新存入到redis
                    $data['cert_key'] = $content;
                    app('redis')->set($this->genReidsId($companyId), json_encode($data));
                }
            }
            $data['cert_key_name'] = 'apiclient_key.pem';
            $data['cert_key_url'] = app('filesystem')->path($certKeyFile);
            $data['app_app_id'] = $data['app_app_id'] ?? '';
            $data['is_servicer'] = $data['is_servicer'] ?? 'false';
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
        $key = 'wxPaymentSetting:' . sha1($companyId);
        return ($this->distributorId ? ($this->distributorId . $key) : $key);
    }

    /**
     * 获取支付实例
     */
    public function getPayment($authorizerAppId, $wxaAppId, $companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                return app('easywechat.manager')->payment($wxaAppId, $paymentSetting['merchant_id'], $paymentSetting['key'], '', '', $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                return app('easywechat.manager')->payment($wxaAppId, $paymentSetting['merchant_id'], $paymentSetting['key']);
            }
        } else {
            throw new BadRequestHttpException('微信支付信息未配置，请联系商家');
        }
    }

    /**
     * 获取JsConfig
     * @param $companyId
     * @return mixed
     */
    public function getJsConfig($companyId, $url)
    {
        $jsApiList = ['chooseImage',
            'previewImage',
            'checkJsApi',
            'scanQRCode',
            'hideOptionMenu',
            'showOptionMenu',
            'hideMenuItems',
            'showMenuItems',
            'hideAllNonBaseMenuItem',
            'showAllNonBaseMenuItem'];
        $openPlatform = new OpenPlatform();
        $WoaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);

        $app = $openPlatform->getAuthorizerApplication($WoaAppid);

        $js = $app->jssdk;
        $js->setUrl($url);

        $config = $js->buildConfig($jsApiList, false, false, false);


        return $config;
    }

    /**
     * 退款
     */
    public function getRefund($wxaAppId, $companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (!$paymentSetting['cert_url'] || !$paymentSetting['cert_key_url']) {
                throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
            }
            $appId = isset($paymentSetting['app_id']) && !empty($paymentSetting['app_id']) ? $paymentSetting['app_id'] : $wxaAppId;
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                return app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url'], $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                return app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url']);
            }
        } else {
            throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
        }
    }

    /**
     * 企业付款到指定账号
     */
    public function getMerchantPayment($companyId, $wxaAppId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            $mchid = $paymentSetting['merchant_id'];
            $key = $paymentSetting['key'];
            if (!$paymentSetting['cert_url'] || !$paymentSetting['cert_key_url']) {
                throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
            }
            return app('easywechat.manager')->merchantPayment($wxaAppId, $mchid, $key, $paymentSetting['cert_url'], $paymentSetting['cert_key_url'], $wxaAppId);
        } else {
            throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'wxpay',
            'attach' => 'depositRecharge',
        ];
        $attributes = [
            'trade_type' => 'JSAPI',
            'body' => $data['shop_name'] . '充值',
            'detail' => $data['detail'],
            'out_trade_no' => $data['deposit_trade_id'],
            'total_fee' => $data['money'], // 单位：分
            'notify_url' => config('common.wechat_payment_notify'),
            'openid' => $data['open_id'],
            'attach' => urlencode(http_build_query($passbackParams)),
        ];

        $this->openid = $data['open_id'];

        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id'], true);
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if ($paymentSetting['is_open'] == false) {
            throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
        }

        $data['mch_id'] = $paymentSetting['merchant_id'];

        $passbackParams = [
            'company_id' => $data['company_id'],
            'pay_type' => 'wxpay',
        ];

        //场景信息
        $scene_info = [];
        //获取门店信息
        if (isset($data['distributor_info']) && $data['distributor_info']) {
            $distributorInfo = $data['distributor_info'];
            $scene_info['store_info'] = [
                'id' => $distributorInfo['shop_code'],
                'name' => $distributorInfo['name'],
                'area_code' => is_array($distributorInfo['regions_id']) && $distributorInfo['regions_id'] ? end($distributorInfo['regions_id']) : null,
                'address' => $distributorInfo['address'],
            ];
        }

        $attributes = [
            'trade_type' => 'JSAPI',
            'body' => $data['body'],
            'detail' => $data['detail'],
            'out_trade_no' => $data['trade_id'],
            'total_fee' => $data['pay_fee'], // 单位：分
            'notify_url' => config('common.wechat_payment_notify'),
            'openid' => $data['open_id'],
            'time_expire' => date('YmdHis', (time() + 300)),
            'attach' => urlencode(http_build_query($passbackParams)),
            'scene_info' => json_encode($scene_info, 320),
        ];

        $this->openid = $data['open_id'];

        return $this->configForPayment($attributes, $authorizerAppId, $wxaAppId, $data['company_id']);
    }

    /**
     * 对微信进行统一下单
     * 并且获取小程序支付需要的参数
     */
    private function configForPayment($attributes, $authorizerAppId, $wxaAppId, $companyId, $isRecharge = false)
    {
        //服务商模式openid要换成sub_openid
        $paymentSetting = $this->getPaymentSetting($companyId);
        if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
            $attributes['sub_openid'] = $attributes['openid'];
            unset($attributes['openid']);
        }
        $payment = $this->getPayment($authorizerAppId, $wxaAppId, $companyId);
        $result = $payment->order->unify($attributes);
        if (!$isRecharge) {
            $tradeService = new TradeService();
            $tradeService->updateOneBy(['trade_id' => $attributes['out_trade_no']], ['inital_request' => json_encode($attributes)]);
        }
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
            $config = $payment->jssdk->bridgeConfig($result['prepay_id'], false); // 返回数组

            //服务商模式小程序发起支付的appid为小程序的appid，需要重新签名
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                $config['appId'] = $wxaAppId;
                unset($config['paySign']);
                $config['paySign'] = $this->generateSign($config, $paymentSetting['key'], 'md5');
            }

            return $config;
        } else {
            app('log')->debug('wechat payment params:' . json_encode($attributes));
            app('log')->debug('wechat payment Message Error result:' . json_encode($result));
            throw new BadRequestHttpException($result['err_code_des'] ?? '支付失败');
        }
    }

    /**
     * 商家打款到指定账号
     */
    public function merchantPayment($companyId, $wxaAppId, $data)
    {
        $attributes = [
            'appid' => $wxaAppId,
            'out_batch_no' => $data['merchant_trade_id'],
            'batch_name' => '转账给'.$data['mobile'].'-'.date('Ymd'),
            'batch_remark' => $data['payment_desc'],
            'total_amount' => $data['amount'],
            'total_num' => 1,
            'transfer_detail_list' => [[
                'out_detail_no' => $data['merchant_trade_id'],
                'transfer_amount' => $data['amount'],
                'transfer_remark' => $data['payment_desc'],
                'openid' => $data['open_id'],
            ]],
        ];

        $merchantPayment = $this->getMerchantPayment($data['company_id'], $wxaAppId);
        app('log')->debug('wechat merchantPayment start merchant_trade_id=>' . $data['merchant_trade_id']);
        $result = $merchantPayment->batch_transfer->toBalance($attributes);
        // $result = $merchantPayment->send($attributes);
        app('log')->debug('wechat merchantPayment end');
        app('log')->debug('wechat merchantPayment result:' . json_encode($result));

        if (isset($result['batch_id']) && $result['batch_id']) {
            $return['status'] = 'PROCESS';
            $return['payment_no'] = $result['batch_id'];
            $return['payment_time'] = $result['create_time'];
        } else {
            $return['status'] = 'FAIL';
            $return['error_code'] = $result['code'] ?? '';
            $return['error_desc'] = $result['message'] ?? '';
        }

        return $return;
    }

    /**
     * 商家退款到指定账号
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        $merchantPayment = $this->getRefund($wxaAppId, $companyId);
        $orderNo = $data['trade_id'];
        $refundNo = isset($data['refund_bn']) ? $data['refund_bn'] : $data['trade_id'];
        $totalFee = $data['pay_fee'];
        $refundFee = isset($data['refund_fee']) ? $data['refund_fee'] : null;

        $result = $merchantPayment->refund->byOutTradeNumber($orderNo, $refundNo, $totalFee, $refundFee);

        app('log')->debug('wechat doRefund end');
        app('log')->debug('wechat doRefund result:' . var_export($result, 1));

        if ($result['return_code'] == 'SUCCESS') {
            if ($result['result_code'] == 'SUCCESS') {
                $orderProcessLog = [
                    'order_id' => $data['order_id'],
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（微信支付渠道）',
                ];
                $return['status'] = 'SUCCESS';
                $return['refund_id'] = $result['refund_id'];
            } else {
                $orderProcessLog = [
                    'order_id' => $data['order_id'],
                    'company_id' => $companyId,
                    'operator_type' => 'system',
                    'remarks' => '订单退款',
                    'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（微信支付渠道），失败原因：' . $result['err_code_des'],
                ];
                $return['status'] = 'FAIL';
                $return['error_code'] = $result['err_code'];
                $return['error_desc'] = $result['err_code_des'];
            }
        } else {
            $return['status'] = 'FAIL';
            $return['error_code'] = '';
            $return['error_desc'] = $result['return_msg'];
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（微信支付渠道），失败原因：' . $result['return_msg'],
            ];
        }
        event(new OrderProcessLogEvent($orderProcessLog));
        return $return;
    }

    /**
     * 获取订单状态信息
     */
    public function getPayOrderInfo($companyId, $trade_id)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (!$paymentSetting['cert_url'] || !$paymentSetting['cert_key_url']) {
                throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
            }
            $appId = isset($paymentSetting['app_id']) && !empty($paymentSetting['app_id']) ? $paymentSetting['app_id'] : '';
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                $payment = app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url'], $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                $payment = app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url']);
            }
            return $payment->order->queryByOutTradeNumber($trade_id);
        } else {
            throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
        }
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (!$paymentSetting['cert_url'] || !$paymentSetting['cert_key_url']) {
                throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
            }
            $appId = isset($paymentSetting['app_id']) && !empty($paymentSetting['app_id']) ? $paymentSetting['app_id'] : '';
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                $payment = app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url'], $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                $payment = app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url']);
            }
            return $payment->refund->queryByOutRefundNumber($data);
        } else {
            throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
        }
    }

    protected function generateSign(array $attributes, $key, $encryptMethod = 'md5')
    {
        ksort($attributes);

        $attributes['key'] = $key;

        return strtoupper(call_user_func_array($encryptMethod, [urldecode(http_build_query($attributes))]));
    }

    public function scheduleQueryMerchantPayment()
    {
        $merchantTradeService = new MerchantTradeService();
        $communityCashWithdrawalService = new CommunityChiefCashWithdrawalService();
        $distributionCashWithdrawalService = new DistributionCashWithdrawalService();
        $popularizeCashWithdrawalService = new PopularizeCashWithdrawalService();
        $list = $merchantTradeService->lists(['payment_action' => 'WECHAT', 'status' => 'PROCESS']);
        foreach ($list['list'] as $row) {
            $merchantPayment = $this->getMerchantPayment($row['company_id'], null);
            $result = $merchantPayment->batch_transfer->queryBalanceOrder($row['payment_no']);
            if (isset($result['transfer_batch'])) {
                $status = 'PROCESS';
                if ($result['transfer_batch']['batch_status'] == 'FINISHED') {
                    if ($result['transfer_batch']['success_num'] > 0) {
                        $status = 'SUCCESS';
                        $tradeStatus = 'success';
                    } else {
                        $status = 'FAIL';
                        $tradeStatus = 'apply';
                    }
                } elseif ($result['transfer_batch']['batch_status'] == 'CLOSED') {
                    $status = 'FAIL';
                    $tradeStatus = 'apply';
                }

                if ($status != 'PROCESS') {
                    $merchantTradeService->updateOneBy(['merchant_trade_id' => $row['merchant_trade_id']], ['status' => $status]);
                    switch ($row['rel_scene_name']) {
                        case 'community_chief_cash_withdrawal':
                            $communityCashWithdrawalService->updateOneBy(['id' => $row['rel_scene_id']], ['status' => $tradeStatus]);
                            break;
                        case 'rebate_cash_withdrawal':
                            $distributionCashWithdrawalService->updateOneBy(['id' => $row['rel_scene_id']], ['status' => $tradeStatus]);
                            break;
                        case 'popularize_rebate_cash_withdrawal':
                            $popularizeCashWithdrawalService->updateOneBy(['id' => $row['rel_scene_id']], ['status' => $tradeStatus]);
                            break;
                    }
                }
            }
        }
    }
}
