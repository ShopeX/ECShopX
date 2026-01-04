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
use Dingo\Api\Exception\ResourceException;

// use AdaPayBundle\Entities\AdapayCorpMember;
use AdaPayBundle\Entities\AdapayDivFee;
use AdaPayBundle\Entities\AdapayPaymemtConfirm;
use AdaPayBundle\Entities\AdapayPaymentReverse;
use AdaPayBundle\Jobs\DrawCashJob;
use AdaPayBundle\Services\AdapayLogService;
use AdaPayBundle\Services\MemberService;
use AdaPayBundle\Services\OpenAccountService;
use AdaPayBundle\Services\Request\Request;
// use AdaPayBundle\Services\SettleAccountService;

use AftersalesBundle\Services\AftersalesRefundService;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\TradeService;
// use PromotionsBundle\Services\PurchaseLimit\PurchaseLimitService;
use PaymentBundle\Interfaces\Payment;
use OrdersBundle\Traits\GetOrderServiceTrait;
use CompanysBundle\Services\CompanysService;

class AdaPaymentService implements Payment
{
    use GetOrderServiceTrait;

    private $payType = 'adaPay';

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

        $logParams = [
            'company_id' => $companyId,
        ];
        $merchantId = app('auth')->user()->get('operator_id');
        (new AdapayLogService())->logRecord($logParams, $merchantId, 'set_payment_setting', 'merchant');

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
        if (!$paymentSetting) {
            throw new BadRequestHttpException('adapay 支付信息未配置，请联系商家');
        }
    }

    /**
     * 退款
     */
    public function getRefund($wxaAppId, $companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if ($paymentSetting) {
            if (!$paymentSetting['cert_url'] || !$paymentSetting['cert_key_url']) {
                throw new BadRequestHttpException('请检查 adapay 支付相关配置是否完成');
            }
            $appId = isset($paymentSetting['app_id']) && !empty($paymentSetting['app_id']) ? $paymentSetting['app_id'] : $wxaAppId;
            if (isset($paymentSetting['is_servicer']) && $paymentSetting['is_servicer'] == 'true') {
                return app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url'], $paymentSetting['servicer_app_id'], $paymentSetting['servicer_merchant_id']);
            } else {
                return app('easywechat.manager')->paymentH5($appId, $paymentSetting['merchant_id'], $paymentSetting['key'], $paymentSetting['cert_url'], $paymentSetting['cert_key_url']);
            }
        } else {
            throw new BadRequestHttpException('请检查 adapay 支付相关配置是否完成');
        }
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (!$paymentSetting || !$paymentSetting['is_open']) {
            throw new BadRequestHttpException('请检查adapay支付配置');
        }

        if ($data['pay_channel'] == 'wx_qr') {
            return ['payment' => $this->getWxQrPayLink($data['company_id'], $data['deposit_trade_id'], $data['pay_fee'])];
        }

        $obj_params = array(
            'company_id' => $data['company_id'],
            'order_no' => $data['deposit_trade_id'],
            'app_id' => $paymentSetting['app_id'],
            'pay_channel' => $data['pay_channel'],
            'pay_amt' => bcdiv($data['pay_fee'], 100, 2),
            'goods_title' => $data['shop_name'].'充值',
            'goods_desc' => $data['detail'],
            'description' => 'depositRecharge',
            'time_expire' => date('YmdHis', $orderInfo['auto_cancel_time'] ?? (time() + 3600)),
            'notify_url' => config('adapay.notify_url'),//异步通知地址
            'api_method' => 'Payment.create',
        );

        if ($data['pay_channel'] == 'wx_lite' && config('adapay.wx_lite_jumppay')) {
            $obj_params['adapay_func_code'] = 'wxpay.createOrder';
        } else {
            switch ($data['pay_channel']) {
                case 'wx_lite':
                case 'wx_pub':
                    $obj_params['expend'] = [
                        'open_id' => $data['open_id'],
                    ];
                    break;
            }
        }

        $request = new Request();
        $resData = $request->call($obj_params);
        if ($resData['data']['status'] == 'failed') {
            throw new BadRequestHttpException($resData['data']['error_msg']);
        }

        switch ($data['pay_channel']) {
            case 'wx_lite':
            case 'wx_pub':
                return json_decode($resData['data']['expend']['pay_info'], true);
            case 'alipay':
            case 'alipay_wap':
                return ['payment' => $resData['data']['expend']['pay_info']];
            case 'alipay_qr':
                return ['payment' => $resData['data']['expend']['qrcode_url']];
        }
    }

    /**
     * 获取小程序支付需要的参数
     * 小程序交易支付调用
     */
    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (!$paymentSetting || !$paymentSetting['is_open']) {
            throw new BadRequestHttpException('请检查adapay支付配置');
        }

        $orderService = $this->getOrderService('normal');
        $filter = [
            'company_id' => $data['company_id'],
            'order_id' => $data['order_id'],
        ];
        $orderInfo = $orderService->getInfo($filter);
        if (!$orderInfo) {
            throw new ResourceException('订单不存在');
        }

        if ($data['pay_channel'] == 'wx_qr') {
            return ['payment' => $this->getWxQrPayLink($data['company_id'], $data['order_id'], $data['pay_fee'])];
        }

        $obj_params = array(
            'company_id' => $data['company_id'],
            'order_no' => $data['trade_id'],
            'app_id' => $paymentSetting['app_id'],
            'pay_channel' => $data['pay_channel'],
            'pay_amt' => bcdiv($data['pay_fee'], 100, 2),
            'goods_title' => $data['detail'],
            'goods_desc' => $data['detail'],
            'description' => $data['trade_source_type'],
            'time_expire' => date('YmdHis', $orderInfo['auto_cancel_time'] ?? (time() + 3600)),
            'notify_url' => config('adapay.notify_url'),//异步通知地址
            'api_method' => 'Payment.create',
        );
        if ($data['trade_source_type'] != 'membercard') {
            $obj_params['pay_mode'] = 'delay';   
        }

        if ($data['pay_channel'] == 'wx_lite' && config('adapay.wx_lite_jumppay')) {
            $obj_params['adapay_func_code'] = 'wxpay.createOrder';
        } else {
            switch ($data['pay_channel']) {
                case 'wx_lite':
                case 'wx_pub':
                    $obj_params['expend'] = [
                        'open_id' => $data['open_id'],
                    ];
                    break;
            }
        }

        $request = new Request();
        $resData = $request->call($obj_params);
        if (isset($resData['data']['status']) && $resData['data']['status'] == 'failed') {
            throw new BadRequestHttpException($resData['data']['error_msg']);
        }

        switch ($data['pay_channel']) {
            case 'wx_lite':
            case 'wx_pub':
                if ($data['pay_channel'] == 'wx_lite' && config('adapay.wx_lite_jumppay')) {
                    $result = $resData['data'];
                } else {
                    $result = json_decode($resData['data']['expend']['pay_info'], true);
                }
		break;
            case 'alipay':
            case 'alipay_wap':
                $result = ['payment' => $resData['data']['expend']['pay_info']];
                break;
            case 'alipay_qr':
                $result = ['payment' => $resData['data']['expend']['qrcode_url']];
                break;
        }

        $tradeService = new TradeService();
        if ($data['pay_channel'] == 'wx_lite' && config('adapay.wx_lite_jumppay')) {
            $tradeService->updateOneBy(['trade_id' => $data['trade_id']], ['inital_request' => json_encode($obj_params), 'transaction_id' => $resData['data']['expend']['pre_order_id'], 'adapay_div_status' => 'NOTDIV', 'payment_params' => json_encode($result)]);
        } else {
            $tradeService->updateOneBy(['trade_id' => $data['trade_id']], ['inital_request' => json_encode($obj_params), 'transaction_id' => $resData['data']['id'], 'adapay_div_status' => 'NOTDIV', 'payment_params' => json_encode($result)]);
        }

        return $result;

    }



    /**
     * 商家退款到指定账号
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (!$paymentSetting) {
            throw new BadRequestHttpException('请检查adapay支付配置');
        }

//        parent::init($data['company_id']);


        $adapayPaymemtConfirmRepository = app('registry')->getManager('default')->getRepository(AdapayPaymemtConfirm::class);
        $paymemtConfirmInfo = $adapayPaymemtConfirmRepository->getInfo(['company_id' => $companyId, 'order_id' => $data['order_id'], 'status' => 'succeeded']);
        if (!$paymemtConfirmInfo) {
            // 未确认支付对象，支付撤销退款
            return $this->adaPayPaymentReverse($companyId, $data);
//            $return['status'] = 'FAIL';
//            $return['error_code'] = '';
//            $return['error_desc'] = '订单未确认支付';
//
//            $orderProcessLog = [
//                'order_id' => $data['order_id'],
//                'company_id' => $companyId,
//                'operator_type' => 'system',
//                'remarks' => '订单退款',
//                'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（adapay支付渠道），失败原因：' . $return['error_desc'],
//            ];
//            event(new OrderProcessLogEvent($orderProcessLog));
//
//            return $return;
        }
//        $version = config('samsung.version');
        $obj_params = array(
            'company_id' => $companyId,
            # 原交易支付对象ID
            'payment_id' => $paymemtConfirmInfo['payment_confirmation_id'],
            # 退款订单号
            'refund_order_no' => isset($data['refund_bn']) ? $data['refund_bn'] . '_' . rand(10000, 99999) . time() : $data['trade_id'] . '_' . rand(10000, 99999) . time(),
            # 退款金额
            'refund_amt' => (isset($data['refund_fee']) ? bcdiv($data['refund_fee'], 100, 2) : null),
            'api_method' => 'Refund.create'
        );
//        $obj = new AdaPayRefund();
//        $obj->create($obj_params);
        $request = new Request();
        $resData = $request->call($obj_params);

        if ($resData['data']['status'] == 'failed') {
//            $errorMsg = $this->getErrorMsg($obj);
            app('log')->debug('adapay退款失败 => ' . $resData['data']['error_msg']);
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（adapay支付渠道），失败原因：' . $resData['data']['error_msg'],
            ];
            $return['status'] = 'FAIL';
            $return['error_code'] = $resData['data']['error_code'] ?? '';
            $return['error_desc'] = $resData['data']['error_msg'] ?? '';
        } else {
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（adapay支付渠道）',
            ];
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $resData['data']['id'];
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
        if (!$paymentSetting) {
            throw new BadRequestHttpException('请检查 adapay 支付相关配置是否完成');
        }

        $tradeService = new TradeService();
        $rs = $tradeService->getInfo(['trade_id' => $trade_id, 'company_id' => $companyId]);
        if (!$rs) {
            throw new BadRequestHttpException('交易ID不存在');
        }

        $transactionId = $rs['transaction_id'];
        if (!$transactionId) {
            return [];
        }
        $obj_params = [
            'company_id' => $rs['company_id'],
            'payment_id' => $transactionId,
            'api_method' => 'Payment.query',
        ];
        $request = new Request();
        $resData = $request->call($obj_params);


        return json_encode($resData['data'], 256);
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data)
    {
        return [];//暂时没有做这个  先返回空
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
            return $payment->queryRefundByRefundNo($data);
        } else {
            throw new BadRequestHttpException('请检查微信支付相关配置是否完成');
        }
    }

    public function scheduleAutoPaymentConfirmation($companyId, $orderId)
    {
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];

        $rsList = $normalOrdersItemsRepository->getList($filter);
        $isAllClosed = true;
        foreach ($rsList['list'] as $value) {
            if ($value['aftersales_status'] != 'CLOSED') {
                $isAllClosed = false;
                break;
            }
        }

        if ($isAllClosed) {
            $data = [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'status' => 'pending',
            ];
            $adapayPaymemtConfirmRepository = app('registry')->getManager('default')->getRepository(AdapayPaymemtConfirm::class);
            $adapayPaymemtConfirmRepository->create($data);

            $this->adaPayPaymentConfirmation($companyId, $orderId);
        }
    }

    public function adaPayPaymentConfirmRetry()
    {
        $adapayPaymemtConfirmRepository = app('registry')->getManager('default')->getRepository(AdapayPaymemtConfirm::class);
        $lists = $adapayPaymemtConfirmRepository->getLists(['status' => 'pending', 'create_time|lte' => time() - 60 * 10]);
        if ($lists) {
            foreach ($lists as $val) {
                $this->adaPayPaymentConfirmation($val['company_id'], $val['order_id']);
            }
        }
    }

    //adapay支付确认
    public function adaPayPaymentConfirmation($companyId, $orderId)
    {
        $normalOrderService = new NormalOrderService();
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $orderInfo = $normalOrderService->getInfo($filter);

        if (!$orderInfo) {
            app('log')->debug('adapay 支付确认失败 ======> 无效的订单号:'.$orderId);
            return;
        }
        if ($orderInfo['pay_type'] != 'adapay') {
            app('log')->debug('adapay 支付确认失败 ======> 支付方式只支持adapay  订单号:'.$orderId);
            return;
        }
//        if (!$orderInfo['distributor_id']) {
//            app('log')->debug('adapay 支付确认失败 ======> 该订单没有有效店铺:'.$orderId);
//            return;
//        }
        $tradeService = new TradeService();
        $tradeFilter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'trade_state' => 'SUCCESS',
            'pay_type' => 'adapay',
        ];
        $tradeInfo = $tradeService->getInfo($tradeFilter);

        if (!$tradeInfo) {
            app('log')->debug('adapay 支付确认失败 ======> 该订单的交易单不存在:'.$orderId);
            return;
        }

        if ($tradeInfo['pay_type'] != 'adapay') {
            app('log')->debug('adapay 支付确认失败 ======> 支付方式只支持adapay:'.$tradeInfo['trade_id']);
            return;
        }

        if (!$tradeInfo['transaction_id']) {
            app('log')->debug('adapay 支付确认失败 ======> 该交易单没有transaction_id:'.$tradeInfo['trade_id']);
            return;
        }
//        parent::init($companyId);

//        $version = config('samsung.version');

        $aftersalesRefundService = new AftersalesRefundService();
        $refundList = $aftersalesRefundService->getList(['company_id' => $companyId, 'order_id' => $orderId, 'refund_status' => ['SUCCESS', 'AUDIT_SUCCESS', 'CHANGE']]);
        foreach ($refundList['list'] as $value) {
            $tradeInfo['total_fee'] -= $value['refund_fee'];
        }
        if ($tradeInfo['total_fee'] == 0) {
            return;
        }
        $totalFee = bcdiv($tradeInfo['total_fee'], 100, 2);
        $obj_params = array(
            'company_id' => $companyId,
            'api_method' => 'PaymentConfirm.create',
            'payment_id' => $tradeInfo['transaction_id'],
            'order_no' => $tradeInfo['trade_id'] . '_' . rand(10000, 99999),
            'confirm_amt' => $totalFee,
//            'description'=> '附件说明',
//            'div_members'=> '' //分账参数列表 默认是数组List
        );
//        $activity_distributor = (new PurchaseLimitService())->entityStoreRepository->getInfo([
//            'activity_id' => $orderInfo['act_id'],
//            'distributor_id' => $orderInfo['distributor_id'],
//        ]);
//        if (!$activity_distributor) {
//            throw new ResourceException('该订单店铺没有参与活动');
//        }
        $feeRate = $this->getFeeRate($companyId, $tradeInfo['pay_channel']);
        if (!$feeRate) {
            app('log')->debug('adapay 支付确认失败 ======> 没有设置费率');
            return;
        }
        $feeAmt = number_format(round($totalFee * $feeRate / 100, 2), 2, '.', '');//手续费

        if ($orderInfo['distributor_id'] == 0) {
            $openAccountService = new OpenAccountService();
            $residentInfo = $openAccountService->adapayMerchantResidentRepository->getInfo(['company_id' => $companyId]);
            $obj_params['fee_mode'] = $residentInfo['adapay_fee_mode'];
            $divMember = [
                [
                    'member_id' => '0',//主商户
                    'amount' => $totalFee,
                    'fee_flag' => 'Y',
                ]
            ];
        } else {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoById($orderInfo['distributor_id']);
            if (!$distributorInfo) {
                app('log')->debug('adapay 支付确认失败 ======> 无效的店铺   店铺id:'.$orderInfo['distributor_id']);
                return;
            }
            if (!$distributorInfo['split_ledger_info']) {
                app('log')->debug('adapay 支付确认失败 ======> 未设置分账信息   店铺id:'.$orderInfo['distributor_id']);
                return;
            }

            $adapayMemberService = new MemberService();
            $distributorMemberId = $adapayMemberService->getMemberIdByOperatorId($orderInfo['distributor_id'], 'distributor');

            $splitLedgerInfo = json_decode($distributorInfo['split_ledger_info'], true);
            $obj_params['fee_mode'] = $splitLedgerInfo['adapay_fee_mode'];

            if ($splitLedgerInfo['adapay_fee_mode'] == 'I') {
                $divFee = $totalFee - $feeAmt;

                if ($distributorInfo['dealer_id'] == '0') {//未关联经销商两方分账
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $headquartersFee = number_format(round($divFee * $headquartersProportion, 2), 2, '.', '');
                    $distributorFee = bcadd($divFee - $headquartersFee, $feeAmt, 2);

                    $divMember = [
                        [
                            'member_id' => '0',//主商户
                            'amount' => $headquartersFee,
                            'fee_flag' => 'N',
                        ],
                        [
                            'member_id' => (string)$distributorMemberId,//店铺子商户
                            'amount' => $distributorFee,
                            'fee_flag' => 'Y',
                        ]
                    ];
                } else {//关联经销商三方分账
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $dealerProportion = $splitLedgerInfo['dealer_proportion'] / 100;
                    $headquartersFee = number_format(round($divFee * $headquartersProportion, 2), 2, '.', '');
                    $dealerFee = number_format(round($divFee * $dealerProportion, 2), 2, '.', '');
                    $distributorFee = bcadd($divFee - $headquartersFee - $dealerFee, $feeAmt, 2);
                    $dealerId = $adapayMemberService->getMemberIdByOperatorId($distributorInfo['dealer_id'], 'dealer');

                    $divMember = [
                        [
                            'member_id' => '0',//主商户
                            'amount' => $headquartersFee,
                            'fee_flag' => 'N',
                        ],
                        [
                            'member_id' => (string)$distributorMemberId,//店铺子商户
                            'amount' => $distributorFee,
                            'fee_flag' => 'Y',
                        ],
                        [
                            'member_id' => (string)$dealerId,//经销商子商户
                            'amount' => $dealerFee,
                            'fee_flag' => 'N',
                        ],
                    ];
                }
            } else {
                if ($distributorInfo['dealer_id'] == '0') {//未关联经销商两方分账
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $headquartersFee = number_format(round($totalFee * $headquartersProportion, 2), 2, '.', '');
                    $distributorFee = bcsub($totalFee, $headquartersFee, 2);

                    $divMember = [
                        [
                            'member_id' => '0',//主商户
                            'amount' => $headquartersFee,
                            'fee_flag' => 'N',
                        ],
                        [
                            'member_id' => (string)$distributorMemberId,//店铺子商户
                            'amount' => $distributorFee,
                            'fee_flag' => 'N',
                        ]
                    ];
                } else {//关联经销商三方分账
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $dealerProportion = $splitLedgerInfo['dealer_proportion'] / 100;
                    $headquartersFee = number_format(round($totalFee * $headquartersProportion, 2), 2, '.', '');
                    $dealerFee = number_format(round($totalFee * $dealerProportion, 2), 2, '.', '');
                    $distributorFee = bcsub($totalFee - $headquartersFee, $dealerFee, 2);
                    $dealerId = $adapayMemberService->getMemberIdByOperatorId($distributorInfo['dealer_id'], 'dealer');

                    $divMember = [
                        [
                            'member_id' => '0',//主商户
                            'amount' => $headquartersFee,
                            'fee_flag' => 'N',
                        ],
                        [
                            'member_id' => (string)$distributorMemberId,//店铺子商户
                            'amount' => $distributorFee,
                            'fee_flag' => 'N',
                        ],
                        [
                            'member_id' => (string)$dealerId,//经销商子商户
                            'amount' => $dealerFee,
                            'fee_flag' => 'N',
                        ],
                    ];
                }
            }
        }
        $originalDivMember = $divMember;
        foreach ($divMember as $key => $value) {//如果分账金额有0元则不传入
            if ($value['amount'] === '0.00') {
                unset($divMember[$key]);
            }
        }

        $obj_params['div_members'] = array_values($divMember);


        $request = new Request();
        $resData = $request->call($obj_params);
        $adapayPaymemtConfirmRepository = app('registry')->getManager('default')->getRepository(AdapayPaymemtConfirm::class);
        if ($resData['data']['status'] == 'failed') {
            app('log')->debug('adapay 支付确认失败 ======> 订单号:'.$orderId.'=====> 错误信息:'.$resData['data']['error_msg']);
            $data = [
                'company_id' => $companyId,
                'order_id' => $orderId,
                'distributor_id' => $orderInfo['distributor_id'],
                'payment_id' => $obj_params['payment_id'],
//                'payment_confirmation_id' => $resData['data']['id'],
                'order_no' => $obj_params['order_no'],
                'confirm_amt' => $obj_params['confirm_amt'],
                'div_members' => json_encode($obj_params['div_members']),
                'status' => $resData['data']['status'],
                'request_params' => json_encode($obj_params),
                'response_params' => json_encode($resData['data']),
            ];
            $adapayPaymemtConfirmRepository->updateBy(['company_id' => $companyId, 'order_id' => $orderId], $data);

            return;
        }
        $data = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'distributor_id' => $orderInfo['distributor_id'],
            'payment_id' => $obj_params['payment_id'],
            'payment_confirmation_id' => $resData['data']['id'],
            'order_no' => $obj_params['order_no'],
            'confirm_amt' => $obj_params['confirm_amt'],
            'div_members' => json_encode($obj_params['div_members']),
            'status' => $resData['data']['status'],
            'request_params' => json_encode($obj_params),
            'response_params' => json_encode($resData['data']),
        ];
        $adapayPaymemtConfirmRepository->updateBy(['company_id' => $companyId, 'order_id' => $orderId], $data);

        $tradeData = [
            'div_members' => json_encode($divMember),
            'adapay_fee_mode' => $obj_params['fee_mode'],
            'adapay_fee' => $feeAmt * 100,
            'adapay_div_status' => 'DIVED',
        ];
        $tradeService = new TradeService();
        $tradeService->updateOneBy(['trade_id' => $tradeInfo['trade_id']], $tradeData);

        $adapayDivFeeRepository = app('registry')->getManager('default')->getRepository(AdapayDivFee::class);
        if (isset($originalDivMember[0])) {//判断是否给主商户分账
            $headquartersData = [
                'trade_id' => $tradeInfo['trade_id'],
                'order_id' => $orderId,
                'company_id' => $companyId,
                'distributor_id' => $orderInfo['distributor_id'],
                'pay_fee' => $tradeInfo['total_fee'],
                'div_fee' => $originalDivMember[0]['fee_flag'] == 'Y' ? bcmul($originalDivMember[0]['amount'], 100) - $tradeData['adapay_fee'] : bcmul($originalDivMember[0]['amount'], 100),//主商户分账金额
                'adapay_member_id' => $originalDivMember[0]['member_id'],
                'operator_type' => 'admin',
            ];
            $adapayDivFeeRepository->create($headquartersData);
        }
        if (isset($originalDivMember[1])) {//判断是否给店铺子商户分账
            $distributorData = [
                'trade_id' => $tradeInfo['trade_id'],
                'order_id' => $orderId,
                'company_id' => $companyId,
                'distributor_id' => $orderInfo['distributor_id'],
                'pay_fee' => $tradeInfo['total_fee'],
                'div_fee' => $originalDivMember[1]['fee_flag'] == 'Y' ? bcmul($originalDivMember[1]['amount'], 100) - $tradeData['adapay_fee'] : bcmul($originalDivMember[1]['amount'], 100),//店铺子商户分账金额
                'adapay_member_id' => $originalDivMember[1]['member_id'],
                'operator_type' => 'distributor',
            ];
            $adapayDivFeeRepository->create($distributorData);
        }

        if (isset($originalDivMember[2])) {//判断是否给经销商子商户分账
            $dealerData = [
                'trade_id' => $tradeInfo['trade_id'],
                'order_id' => $orderId,
                'company_id' => $companyId,
                'distributor_id' => $orderInfo['distributor_id'],
                'pay_fee' => $tradeInfo['total_fee'],
                'div_fee' => bcmul($originalDivMember[2]['amount'], 100),//经销商子商户分账金额
                'adapay_member_id' => $originalDivMember[2]['member_id'],
                'operator_type' => 'dealer',
            ];
            $adapayDivFeeRepository->create($dealerData);

            $tradeService->updateOneBy(['trade_id' => $tradeInfo['trade_id']], ['dealer_id' => $distributorInfo['dealer_id'] ?? 0]);
        }

        return $resData['data'];
    }

    public function getFeeRate($companyId, $payChannel)
    {
        $setting = $this->getPaymentSetting($companyId);
        if (!$setting) {
            return null;
        }

        if ($payChannel == 'wx_qr') {
            $payChannel = 'wx_pub';
        }
        switch ($payChannel) {
            case 'wx_lite':
            case 'wx_pub':
                return $setting[$payChannel.'_'.$setting['wxpay_fee_type']] ?? null;
            case 'alipay':
            case 'alipay_wap':
                return $setting['alipay_call'] ?? null;
            case 'alipay_qr':
                return $setting[$payChannel.'_'.$setting['alipay_fee_type']] ?? null;
        }
    }
    //adapay支付撤销
    public function adaPayPaymentReverse($companyId, $data)
    {
        $normalOrderService = new NormalOrderService();
        $filter = [
            'company_id' => $companyId,
            'order_id' => $data['order_id'],
        ];
        $orderInfo = $normalOrderService->getInfo($filter);

        if (!$orderInfo) {
            $return['status'] = 'FAIL';
            $return['error_desc'] = '订单不存在:' . $data['order_id'];
            return $return;
        }
//        if (!$orderInfo['distributor_id']) {
//            $return['status'] = 'FAIL';
//            $return['error_desc'] = '订单店铺不存在:' . $data['order_id'];
//            return $return;
//        }
        $filter['trade_state'] = 'SUCCESS';
        $orderBy = ['time_expire' => 'DESC'];

        $tradeService = new TradeService();
        $tradeList = $tradeService->lists($filter, $orderBy);

        if (!$tradeList['list']) {
            return false;
        }
        $tradeInfo = $tradeList['list'][0];
        if ($tradeInfo['pay_type'] != 'adapay') {
            $return['status'] = 'FAIL';
            $return['error_desc'] = '订单支付方式不是adapay:' . $data['order_id'];
            return $return;
        }

        if (!$tradeInfo['transaction_id']) {
            $return['status'] = 'FAIL';
            $return['error_desc'] = '交易单不存在:' . $data['order_id'];
        }

//        $version = config('samsung.version');
//        parent::init($companyId);
//        $obj = new AdaPayPaymentReverse();
        $openAccountService = new OpenAccountService();
        $appId = $openAccountService->getAppIdByCompanyId($companyId);

        $reverse_amt = isset($data['refund_fee']) ? $data['refund_fee'] : $tradeInfo['total_fee'];
        $obj_params = array(
            'company_id' => $companyId,
            'payment_id' => $tradeInfo['transaction_id'],
            'app_id' => $appId,
            'order_no' => $tradeInfo['trade_id'] . '_' . rand(10000, 99999) . time(),
            'reverse_amt' => bcdiv($reverse_amt, 100, 2),
            'notify_url' => config('adapay.notify_url'),
            'api_method' => 'PaymentReverse.create',
        );
//        $obj->create($obj_params);

//        if ($obj->isError()) {
//            $errorMsg = $this->getErrorMsg($obj);
//            throw new ResourceException($errorMsg);
//        }

        $request = new Request();
        $resData = $request->call($obj_params);

        if ($resData['data']['status'] == 'failed') {
            app('log')->debug('adapay支付撤销失败 => ' . $resData['data']['error_msg']);
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单支付撤销（adapay支付渠道），失败原因：' . $resData['data']['error_msg'],
            ];
            $return['status'] = 'FAIL';
            $return['error_code'] = $resData['data']['error_code'] ?? '';
            $return['error_desc'] = $resData['data']['error_msg'] ?? '';
        } else {
            $data = [
                'company_id' => $companyId,
                'order_id' => $data['order_id'],
                'payment_id' => $obj_params['payment_id'],
                'payment_reverse_id' => $resData['data']['id'],
                'app_id' => $obj_params['app_id'],
                'order_no' => $obj_params['order_no'],
                'reverse_amt' => $obj_params['reverse_amt'],
                'status' => $resData['data']['status'],
                'request_params' => json_encode($obj_params),
            ];
            $AdapayPaymentReverseRepository = app('registry')->getManager('default')->getRepository(AdapayPaymentReverse::class);
            $AdapayPaymentReverseRepository->create($data);

            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单支付撤销成功（adapay支付渠道）',
            ];
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $resData['data']['id'];
        }
        event(new OrderProcessLogEvent($orderProcessLog));

        return $return;
    }

    private function getWxQrPayLink($companyId, $orderId, $totalFee) {
        if (!config('common.system_is_saas')) {
            $h5BaseUrl = config('common.h5_base_url');
        } else {
            $companysService = new CompanysService();
            $domainInfo = $companysService->getDomainInfo(['company_id' => $companyId]);
            if (isset($domainInfo['h5_domain']) && $domainInfo['h5_domain']) {
                $h5BaseUrl = 'https://'.$domainInfo['h5_domain'];
            } else {
                $h5BaseUrl = 'https://'.$domainInfo['h5_default_domain'];
            }
        }

        $queryParams = http_build_query([
            'company_id' => $companyId,
            'order_id' => $orderId,
            'total_fee' => $totalFee
        ]);

        return $h5BaseUrl.'/pages/cart/cashier-weapp?'.$queryParams;
    }
}
