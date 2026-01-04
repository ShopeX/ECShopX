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

use BsPayBundle\Entities\PaymemtConfirm;
use BsPayBundle\Entities\DivFee;

use BsPayBundle\Services\UserService;

use BsPayBundle\Services\V2\Trade\PaymentJspay;
use BsPayBundle\Services\V2\Trade\PaymentScanpayRefund;
use BsPayBundle\Services\V2\Trade\PaymentScanpayQuery;
use BsPayBundle\Services\V2\Trade\PaymentDelaytransConfirm;

use AftersalesBundle\Services\AftersalesRefundService;
use DistributionBundle\Services\DistributorService;
use OrdersBundle\Entities\NormalOrdersItems;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Interfaces\Payment;
use OrdersBundle\Traits\GetOrderServiceTrait;
use CompanysBundle\Services\CompanysService;
use GoodsBundle\Services\ItemsCommissionService;
use BsPayBundle\Services\DivFeeService;
use BsPayBundle\Services\V2\Trade\PaymentWithdraw;

class BsPayService implements Payment
{
    use GetOrderServiceTrait;

    private $payType = 'bspay';

    public const TRADE_PENDING = 'P';//待处理
    public const TRADE_SUCC = 'S';//分账成功
    public const TRADE_FAIL = 'F';//失败

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
            throw new BadRequestHttpException('汇付斗拱 支付信息未配置，请联系商家');
        }
    }

    /**
     * 退款
     */
    public function getRefund($companyId)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if (empty($paymentSetting)) {
            throw new BadRequestHttpException('请检查 汇付斗拱 支付相关配置是否完成');
        }
        return $paymentSetting;
    }

    /**
     * 预存款充值
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        // 判断支付方式是否配置
        $paymentSetting = $this->getPaymentSetting($data['company_id']);
        if (!$paymentSetting || !$paymentSetting['is_open']) {
            throw new BadRequestHttpException('请检查汇付斗拱支付配置');
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

        $obj_params = array(
            'order_id' => $data['order_id'],
            'trade_id' => $data['trade_id'],
            // 'upper_huifu_id' => $paymentSetting['upper_huifu_id'],
            'upper_huifu_id' => $paymentSetting['sys_id'],
            'pay_channel' => $data['pay_channel'],
            'goods_desc' => $data['detail'],
            'trans_amt' => bcdiv($data['pay_fee'], 100, 2),
            'sub_openid' => $data['open_id'],
            'body' => $data['body'],
            'spbill_create_ip' => get_client_ip(),
            'remark' => $data['trade_source_type'],
            'sub_appid' => config('bspay.wx_sub_appid'),
            'time_expire' => date('YmdHis', $orderInfo['auto_cancel_time'] ?? (time() + 3600)),
            'notify_url' => config('bspay.notify_url')."/pay.".$data['pay_channel'],//异步通知地址
        );
        if ($data['trade_source_type'] != 'membercard') {
            $obj_params['delay_acct_flag'] = 'Y';   
        }
        $paymentJspay = new PaymentJspay($data['company_id']);
        $resData = $paymentJspay->handle($obj_params);
        app('log')->info('depositRecharge resData====>'.var_export($resData, true));

        switch ($data['pay_channel']) {
            case 'wx_lite':
                return $resData['data']['pay_info'];
            case 'alipay_wap':
                return ['payment' => $resData['data']['qr_code']];
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
            throw new BadRequestHttpException('请检查汇付斗拱支付配置');
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
        if ($data['pay_channel'] != 'wx_qr') {
            $pay_channel = $data['pay_channel'];
            if ($data['pay_channel'] == 'wx_pub' && ($data['source'] ?? '') == 'pc') {
                $pay_channel = 'wx_qr';
            }
            $obj_params = array(
                'order_id' => $data['order_id'],
                'trade_id' => $data['trade_id'],
                // 'upper_huifu_id' => $paymentSetting['upper_huifu_id'],
                'upper_huifu_id' => $paymentSetting['sys_id'],
                'pay_channel' => $data['pay_channel'],
                'goods_desc' => $data['detail'],
                'trans_amt' => bcdiv($data['pay_fee'], 100, 2),
                'sub_appid' => $wxaAppId,
                'sub_openid' => $data['open_id'],
                'body' => $data['body'],
                'spbill_create_ip' => get_client_ip(),
                'remark' => $data['trade_source_type'],
                'time_expire' => date('YmdHis', $orderInfo['auto_cancel_time'] ?? (time() + 3600)),
                'notify_url' => config('bspay.notify_url')."/pay.".$pay_channel,//异步通知地址
            );
            if ($data['trade_source_type'] != 'membercard') {
                $obj_params['delay_acct_flag'] = 'Y';   
            }
            $paymentJspay = new PaymentJspay($data['company_id']);
            app('log')->info('bspay::dopay::obj_params====>'.json_encode($obj_params));
            $resData = $paymentJspay->handle($obj_params);
            app('log')->info('bspay::dopay:resData====>'.json_encode($resData));
            switch ($data['pay_channel']) {
                case 'wx_lite':// 微信小程序
                case 'wx_pub':// 微信公众号
                    $result = json_decode($resData['data']['pay_info'], true);
                    break;
                case 'alipay_wap':// 支付宝H5
                    $result = ['payment' => $resData['data']['qr_code']];
                    break;
                case 'alipay_qr':// 支付宝PC扫码
                    // 生成二维码
                    $dns2d = app('DNS2D')->getBarcodePNG($resData['data']['qr_code'], "QRCODE", 120, 120);
                    $result = ['payment' => $resData['data']['qr_code'], 'qrcode_url' => 'data:image/jpg;base64,' . $dns2d];
                    break;
            } 
            $tradeService = new TradeService();
            $updateData = [
                'inital_request' => json_encode($obj_params),
                'transaction_id' => $resData['data']['party_order_id'],
                'bspay_div_status' => 'NOTDIV',
                'payment_params' => json_encode($result),
                'bspay_req_date' => $resData['data']['req_date']
            ];
            if ($pay_channel == 'wx_qr') {
                $updateData['pay_channel'] = 'wx_qr';
            }
            $tradeService->updateOneBy(['trade_id' => $data['trade_id']], $updateData);
        } else {
            $result = [];
            $h5Url = $this->getWxQrPayLink($data['company_id'], $data['order_id'], $data['pay_fee']);
            // 生成二维码
            $dns2d = app('DNS2D')->getBarcodePNG($h5Url, "QRCODE", 120, 120);
            $result = ['payment' => $h5Url, 'qrcode_url' => 'data:image/jpg;base64,' . $dns2d];
            $tradeService = new TradeService();
            $tradeService->updateOneBy(['trade_id' => $data['trade_id']], [ 'bspay_div_status' => 'NOTDIV', 'payment_params' => json_encode($result)]);
        }
        return $result;

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
        // $h5BaseUrl = 'http://172.27.31.118:10088';
        $queryParams = http_build_query([
            'company_id' => $companyId,
            'order_id' => $orderId,
            'total_fee' => $totalFee,
            'source' => 'pc'
        ]);

        return $h5BaseUrl.'/pages/cart/cashier-weapp?'.$queryParams;
    }

    /**
     * 商家退款到指定账号
     */
    public function doRefund($companyId, $wxaAppId, $data)
    {
        $setting = $this->getRefund($companyId);

        // $paymemtConfirmRepository = app('registry')->getManager('default')->getRepository(PaymemtConfirm::class);
        // $paymemtConfirmInfo = $paymemtConfirmRepository->getInfo(['company_id' => $companyId, 'order_id' => $data['order_id'], 'status' => self::TRADE_SUCC]);
        // if ($paymemtConfirmInfo) {
        //     // 已确认支付，暂时不做处理
        //     app('log')->debug('斗拱 退款失败 ======> 该订单已确认支付:'.$data['order_id']);
        //     return false;
        // }
        $ord_amt = isset($data['refund_fee']) ? $data['refund_fee'] : $data['pay_fee'];
        $obj_params = array(
            'req_seq_id' => isset($data['refund_bn']) ? $data['refund_bn'] . '_' . rand(10000, 99999) . time() : $data['trade_id'] . '_' . rand(10000, 99999) . time(),
            'upper_huifu_id' => $setting['sys_id'],
            'ord_amt' => bcdiv($ord_amt, 100, 2),// 退款金额
            'org_req_date' => $data['bspay_req_date'],
            'trade_id' => $data['trade_id'],
            // 'notify_url' => config('bspay.notify_url')."/refund.".$data['pay_channel'],//异步通知地址
        );
        // 查询已退款成功金额总和
        $aftersalesRefundService = new AftersalesRefundService();
        $refunded_fee = $aftersalesRefundService->sum([
            'company_id' => $companyId,
            'order_id' => $data['order_id'],
            'refund_status' => 'SUCCESS',
        ], 'sum(refund_fee)');

        $refund_fee = $ord_amt + $refunded_fee;

        // 部分退款，需要传分账串
        /**if ($refund_fee < $data['pay_fee']) {
            $ord_amt = bcdiv($ord_amt, 100, 2);
            if ($data['distributor_id'] == 0) {
                $divMember = [
                    [
                        'huifu_id' => $setting['sys_id'],//主商户
                        'div_amt' => $ord_amt,
                    ]
                ];
            } else {
                // 获取用户进件信息
                $userService = new UserService();
                $distributorHuifuId = $userService->getHuifuIdByOperatorId($companyId, $data['distributor_id'], 'distributor');
                if (!$distributorHuifuId) {
                    app('log')->debug('斗拱 扫码交易退款失败 ======> 用户进件未成功   店铺id:'.$data['distributor_id']);
                    return;
                }

                $distributorService = new DistributorService();
                $distributorInfo = $distributorService->getInfoById($data['distributor_id']);
                if (!$distributorInfo) {
                    app('log')->debug('斗拱 扫码交易退款失败 ======> 无效的店铺   店铺id:'.$data['distributor_id']);
                    return;
                }
                if (!$distributorInfo['bspay_split_ledger_info']) {
                    app('log')->debug('斗拱 扫码交易退款失败 ======> 未设置分账信息   店铺id:'.$data['distributor_id']);
                    return;
                }
                // $splitLedgerInfo = json_decode($distributorInfo['bspay_split_ledger_info'], true);
                
                // if ($distributorInfo['dealer_id'] == '0') {//未关联经销商两方分账
                //     // $headquartersProportion = config('bspay.headquarters_proportion') / 100;
                //     $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                //     $headquartersFee = number_format(round($ord_amt * $headquartersProportion, 2), 2, '.', '');
                //     $distributorFee = bcsub($ord_amt, $headquartersFee, 2);
                //     $divMember = [
                //         [
                //             'huifu_id' => $setting['sys_id'],//主商户
                //             'div_amt' => bcdiv($headquartersFee, 100, 2),
                //         ],
                //         [
                //             'huifu_id' => (string)$userInfo['huifu_id'],//店铺用户
                //             'div_amt' => bcdiv($distributorFee, 100, 2),
                //         ]
                //     ];
                // }


                $divFlag = 'distributor';
                // 由于云店没有经销商下的店铺了，这段逻辑不使用了，暂时保留
                // if ($distributorInfo['dealer_id'] > 0) {
                //     $divFlag = 'dealer';
                // }
                if ($distributorInfo['merchant_id'] > 0) {
                    $divFlag = 'merchant';
                }
                $splitLedgerInfo = json_decode($distributorInfo['bspay_split_ledger_info'], true);
                if ($divFlag == 'dealer') { // 关联经销商，三方分账；
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $dealerProportion = $splitLedgerInfo['dealer_proportion'] / 100;
                    $headquartersFee = number_format(round($ord_amt * $headquartersProportion, 2), 2, '.', '');
                    $dealerFee = number_format(round($ord_amt * $dealerProportion, 2), 2, '.', '');
                    $distributorFee = $ord_amt - $headquartersFee - $dealerFee;
                    $dealerHuifuId = $userService->getHuifuIdByOperatorId($companyId, $distributorInfo['dealer_id'], 'dealer');
                    if (!$dealerHuifuId) {
                        app('log')->debug('斗拱 扫码交易退款失败 ======> 用户进件未成功 经销商id:'.$orderInfo['dealer_id']);
                        return;
                    }

                    $divMember = [
                        [
                            'huifu_id' => $setting['sys_id'],//主商户
                            'div_amt' => $headquartersFee,
                        ],
                        [
                            'huifu_id' => (string)$distributorHuifuId,//店铺子商户
                            'div_amt' => $distributorFee,
                        ],
                        [
                            'huifu_id' => (string)$dealerHuifuId,//经销商子商户
                            'div_amt' => $dealerFee,
                        ],
                    ];
                } elseif ($divFlag == 'merchant') { // 关联商户，三方分账
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $merchantProportion = $splitLedgerInfo['merchant_proportion'] / 100;
                    $headquartersFee = number_format(round($ord_amt * $headquartersProportion, 2), 2, '.', '');
                    $merchantFee = number_format(round($ord_amt * $merchantProportion, 2), 2, '.', '');
                    $distributorFee = $ord_amt - $headquartersFee - $merchantFee;
                    $merchantHuifuId = $userService->getHuifuIdByOperatorId($orderInfo['company_id'], $distributorInfo['merchant_id'], 'merchant');
                    if (!$merchantHuifuId) {
                        app('log')->debug('斗拱 扫码交易退款失败 ======> 用户进件未成功 商户id:'.$orderInfo['merchant_id']);
                        return;
                    }

                    $divMember = [
                        [
                            'huifu_id' => $setting['sys_id'],//主商户
                            'div_amt' => $headquartersFee,
                        ],
                        [
                            'huifu_id' => (string)$distributorHuifuId,//店铺子商户
                            'div_amt' => $distributorFee,
                        ],
                        [
                            'huifu_id' => (string)$merchantHuifuId,//商户子商户
                            'div_amt' => $merchantFee,
                        ],
                    ];
                } else { //未关联经销商、商户两方分账
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $headquartersFee = number_format(round($ord_amt * $headquartersProportion, 2), 2, '.', '');
                    $distributorFee = bcsub($ord_amt, $headquartersFee, 2);
                    $divMember = [
                        [
                            'huifu_id' => $setting['sys_id'],//主商户
                            'div_amt' => $headquartersFee,
                        ],
                        [
                            'huifu_id' => (string)$distributorHuifuId,//店铺用户
                            'div_amt' => $distributorFee,
                        ]
                    ];
                }
            }
            foreach ($divMember as $key => $value) {//如果分账金额有0元则不传入
                if ($value['div_amt'] === '0.00') {
                    unset($divMember[$key]);
                }
            }
            $obj_params['acct_infos'] = $divMember;
        }**/
        
        app('log')->info('bspay dorefund obj_params====>'.json_encode($obj_params));
        $paymentScanpayRefund = new PaymentScanpayRefund($companyId);
        $resData = $paymentScanpayRefund->handle($obj_params);
        // 交易失败
        // 交易失败
        if ($resData['data']['trans_stat'] == self::TRADE_FAIL) {
            app('log')->debug('斗拱 退款失败 => ' . $resData['data']['resp_desc']);
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款失败（斗拱支付渠道），失败原因：' . $resData['data']['resp_desc'],
            ];
            $return['status'] = 'FAIL';
            $return['error_code'] = $resData['data']['resp_code'] ?? '';
            $return['error_desc'] = $resData['data']['resp_desc'] ?? '';
        } else {
            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（斗拱支付渠道）',
            ];
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $resData['data']['req_seq_id'];
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
            throw new BadRequestHttpException('请检查 汇付斗拱 支付相关配置是否完成');
        }

        $tradeService = new TradeService();
        $tradeInfo = $tradeService->getInfo(['trade_id' => $trade_id, 'company_id' => $companyId]);
        if (!$tradeInfo) {
            throw new BadRequestHttpException('交易ID不存在');
        }

        if (!$tradeInfo['transaction_id']) {
            return [];
        }
        $obj_params = [
            'huifu_id' => $paymentSetting['sys_id'],
            'bspay_req_date' => $tradeInfo['bspay_req_date'],
            'trade_id' => $trade_id,
        ];
        $paymentScanpayQuery = new PaymentScanpayQuery($companyId);
        $resData = $paymentScanpayQuery->handle($obj_params);

        return json_encode($resData['data'], 256);
    }

    /**
     * 获取退款订单状态信息
     */
    public function getRefundOrderInfo($companyId, $data)
    {
        return [];//暂时没有做这个  先返回空
    }

    /**
     * 自动关闭售后，支付确认
     */
    public function scheduleAutoPaymentConfirmation($companyId, $orderId)
    {
        app('log')->debug('斗拱支付确认开始 => '.$orderId);
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
                'status' => self::TRADE_PENDING,//待处理
            ];
            $paymemtConfirmRepository = app('registry')->getManager('default')->getRepository(PaymemtConfirm::class);
            // app('log')->info('斗拱支付确认 paymemtConfirmRepository data====>'.var_export($data, true));
            $result = $paymemtConfirmRepository->create($data);
            // app('log')->info('斗拱支付确认 paymemtConfirmRepository result====>'.var_export($result, true));
            $this->paymentConfirmation($companyId, $orderId, $result);
        }
    }

    /**
     * 支付确认重试
     */
    public function paymentConfirmRetry()
    {
        $paymemtConfirmRepository = app('registry')->getManager('default')->getRepository(PaymemtConfirm::class);
        $lists = $paymemtConfirmRepository->getLists(['status' => self::TRADE_PENDING, 'created|lte' => time() - 60 * 10]);
        if ($lists) {
            foreach ($lists as $val) {
                app('log')->debug('斗拱支付确认::paymentConfirmRetry::order_id:'.$val['order_id']);
                $this->paymentConfirmation($val['company_id'], $val['order_id'], $val);
            }
        }
    }

    /**
     * 延迟分账的订单，请求支付确认接口，分账到商户和用户
     */
    public function paymentConfirmation($companyId, $orderId, $confirmationData)
    {
        app('log')->debug('斗拱支付确认::paymentConfirmation::order_id:'.$orderId);
        $normalOrderService = new NormalOrderService();
        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
        ];
        $orderInfo = $normalOrderService->getInfo($filter);
        if (!$orderInfo) {
            app('log')->debug('斗拱支付确认::paymentConfirmation::失败，订单不存在 => '.$orderId);
            return true;
        }
        if ($orderInfo['pay_type'] != 'bspay') {
            app('log')->debug('斗拱支付确认::paymentConfirmation::失败，支付方式只支持斗拱 => '.$orderId);
            return true;
        }
        $tradeService = new TradeService();
        $tradeFilter = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'trade_state' => 'SUCCESS',
            'pay_type' => 'bspay',
        ];
        $tradeInfo = $tradeService->getInfo($tradeFilter);
        if (!$tradeInfo) {
            app('log')->debug('斗拱支付确认::paymentConfirmation::失败 => 该订单的交易单不存在:'.$orderId);
            return true;
        }
        if ($tradeInfo['pay_type'] != 'bspay') {
            app('log')->debug('斗拱支付确认::paymentConfirmation::失败 => 支付方式只支持斗拱:'.$tradeInfo['trade_id']);
            return true;
        }
        if (!$tradeInfo['transaction_id']) {
            app('log')->debug('斗拱支付确认::paymentConfirmation::失败 => 该交易单没有transaction_id:'.$tradeInfo['trade_id']);
            return true;
        }

        $aftersalesRefundService = new AftersalesRefundService();
        $refundList = $aftersalesRefundService->getList(['company_id' => $companyId, 'order_id' => $orderId, 'refund_status' => ['SUCCESS', 'AUDIT_SUCCESS', 'CHANGE']]);
        foreach ($refundList['list'] as $value) {
            $tradeInfo['total_fee'] -= $value['refund_fee'];
        }
        if ($tradeInfo['total_fee'] == 0) {
            return true;
        }

        $setting = $this->getPaymentSetting($companyId);
        
        // 获取参与分账的用户（店铺，平台，供应商，商户）
        $divResult = $this->getDivMember($orderInfo, $tradeInfo, $setting);
        if (!$divResult or !$divResult['div_members']) {
            return true;
        }
        $originalDivMember = $divMember = $divResult['div_members'];
        // app('log')->info('斗拱 支付确认 originalDivMember====>'.var_export($originalDivMember, true));
        foreach ($divMember as $key => $value) {//如果分账金额有0元则不传入
            if ($value['div_amt'] === '0.00') {
                unset($divMember[$key]);
            }
        }
        // 请求交易确认接口        
        $obj_params = array(
            'req_seq_id' => date("YmdHis") . rand(100000, 999999),
            // 'huifu_id' => $setting['upper_huifu_id'],
            'huifu_id' => $setting['sys_id'],
            'org_req_seq_id' => $tradeInfo['trade_id'],// 原交易请求流水号
            'org_req_date' => $tradeInfo['bspay_req_date'],//原交易请求日期
            'acct_infos' => array_values($divMember),//分账对象
        );
        $paymentDelaytransConfirm = new PaymentDelaytransConfirm($companyId);
        // app('log')->debug('斗拱 重试支付确认paymentConfirmation 准备请求接口 ======> 订单号:'.$orderId);
        $resData = $paymentDelaytransConfirm->handle($obj_params);
        // app('log')->debug('斗拱 重试支付确认paymentConfirmation 接口请求结束 ======> 订单号:'.$orderId);
        $paymemtConfirmRepository = app('registry')->getManager('default')->getRepository(PaymemtConfirm::class);
        $data = [
            'company_id' => $companyId,
            'order_id' => $orderId,
            'distributor_id' => $orderInfo['distributor_id'],
            'payment_id' => $tradeInfo['transaction_id'],
            'order_no' => $obj_params['req_seq_id'],//支付确认请求流水号
            'confirm_amt' => $tradeInfo['total_fee'],
            'div_members' => json_encode($obj_params['acct_infos']),
            'status' => $resData['data']['trans_stat'],
            'request_params' => json_encode($obj_params),
            'response_params' => json_encode($resData['data']),
        ];
        if ($resData['data']['trans_stat'] == self::TRADE_FAIL) {
            app('log')->debug('斗拱支付确认::paymentConfirmation::失败 => '.$orderId.', 错误信息:'.$resData['data']['resp_desc']);            
            // $paymemtConfirmRepository->updateBy(['company_id' => $companyId, 'order_id' => $orderId], $data);
            $paymemtConfirmRepository->updateOneBy(['id' => $confirmationData['id']], $data);
            return;
        }
        $data['payment_confirmation_id'] = $resData['data']['hf_seq_id'];//全局流水号
        // $paymemtConfirmRepository->updateOneBy(['company_id' => $companyId, 'order_id' => $orderId], $data);
        $paymemtConfirmRepository->updateOneBy(['id' => $confirmationData['id']], $data);
        $tradeData = [
            'bspay_div_members' => json_encode($divMember),
            'bspay_fee_mode' => $divResult['headquarters_fee_mode'],
            'bspay_fee' => $divResult['fee_amt'] * 100,
            'bspay_div_status' => 'DIVED',
        ];
        $tradeService = new TradeService();
        $tradeService->updateOneBy(['trade_id' => $tradeInfo['trade_id']], $tradeData);

        /** @var DivFeeService $divFeeService */
        $divFeeService = new DivFeeService();
        foreach ($originalDivMember as $key => $value) {
            $divData = [
                'trade_id' => $tradeInfo['trade_id'],
                'order_id' => $orderInfo['order_id'],
                'company_id' => $orderInfo['company_id'],
                'distributor_id' => $value['distributor_id'] ?? null,
                'supplier_id' => $value['supplier_id'] ?? 0,
                'operator_type' => $value['operator_type'],
                'pay_fee' => $tradeInfo['total_fee'],
                'div_fee' => bcmul($value['div_amt'], 100),
                'huifu_id' => $value['huifu_id'],
                'supplier_id' => $value['supplier_id'] ?? 0,
                'merchant_id' => $value['merchant_id'] ?? null,
            ];
            // 直接创建分账记录
            $divFeeService->divFeeRepository->create($divData);
        }

        return $resData['data'];
    }

    public function getDivMember($orderInfo, $tradeInfo, $setting)
    {
        $divMember = [];
        $feeRate = $this->getFeeRate($orderInfo['company_id'], $tradeInfo['pay_channel']);
        if (!$feeRate) {
            app('log')->debug('斗拱支付确认::getDivMember::失败 => 没有设置费率');
            return false;
        }
        $totalFee = bcdiv($tradeInfo['total_fee'], 100, 2);
        //手续费
        $feeAmt = number_format(round($totalFee * $feeRate / 100, 2), 2, '.', '');
        // 平台商户的手续费扣费方式 1:外扣 2:内扣
        $headquarters_fee_mode = config('bspay.headquarters_fee_mode');
        if ($headquarters_fee_mode == '2') {            
            $divFee = $totalFee - $feeAmt;//内扣，可分账金额 = 订单金额-交易手续费
        } else {
            $divFee = $totalFee;//外扣，可分账金额 = 订单金额
        }

        //如果包含供应商商品，商品的成本价要结算给经销商
        $normalOrderService = new NormalOrderService();
        $spplierItemsData = $normalOrderService->normalOrdersItemsRepository->getList([
            'company_id' => $orderInfo['company_id'],
            'order_id' => $orderInfo['order_id'],
            'supplier_id|gte' => 1,
        ]);
        if ($spplierItemsData['list']) {
            $costFees = [];//每个供应商的分账金额
            foreach ($spplierItemsData['list'] as $v) {
                if (isset($costFees[$v['supplier_id']])) {
                    $costFees[$v['supplier_id']] += $v['cost_fee'];
                } else {
                    $costFees[$v['supplier_id']] = $v['cost_fee'];
                }
            }

            //扣除供应商订单里已经退款的金额
            $aftersalesRefundService = new AftersalesRefundService();
            foreach ($costFees as $supplierId => $cost_fee) {                
                $refundAmount = $aftersalesRefundService->aftersalesRefundRepository->sum(['supplier_id' => $supplierId, 'company_id' => $orderInfo['company_id'], 'order_id' => $orderInfo['order_id'], 'refund_status' => ['SUCCESS', 'AUDIT_SUCCESS', 'CHANGE']], 'refund_fee');
                $costFees[$supplierId] -= $refundAmount;
                if ($costFees[$supplierId] > 0) {
                    // 获取用户进件信息
                    // $bsPayUserService = new UserService();
                    // $bsPayUserId = $bsPayUserService->getHuifuIdByOperatorId($orderInfo['company_id'], $supplierId, 'supplier');
                    $bsPayUserId = $this->getHuifuId($orderInfo['company_id'], $supplierId, 'supplier');
                    if (!$bsPayUserId) {
                        app('log')->debug('斗拱支付确认::getDivMember::失败 => 供应商未进件，id:'.$orderInfo['distributor_id']);
                        return false;
                    }
                    $divMember[] = [
                        'operator_type' => 'supplier',
                        'supplier_id' => $supplierId,
                        'huifu_id' => (string)$bsPayUserId,
                        'div_amt' => bcdiv($costFees[$supplierId], '100', 2),
                        'supplier_id' => $supplierId,
                    ];
                }
            }

            //分账金额减去供应商的成本金额
            $divFee -= bcdiv(array_sum($costFees), '100', 2);
        }

        if (!$divFee && !$divMember) {
            app('log')->debug('斗拱支付确认::getDivMember::失败 => 可分账金额为0, ' . $orderInfo['order_id']);
            return false;
        }
        
        if ($divFee && $orderInfo['distributor_id'] == 0) {
            $divMember[] = [
                'operator_type' => 'admin',//主商户
                'huifu_id' => $setting['sys_id'],
                'div_amt' => number_format($divFee, 2, '.', ''),
            ];
        } elseif ($divFee) {
            $distributorService = new DistributorService();
            $distributorInfo = $distributorService->getInfoById($orderInfo['distributor_id']);
            if (!$distributorInfo) {
                app('log')->debug('斗拱支付确认::getDivMember::失败 => 无效的店铺   店铺id:'.$orderInfo['distributor_id']);
                return;
            }
            if (!$distributorInfo['bspay_split_ledger_info']) {
                app('log')->debug('斗拱支付确认::getDivMember::失败 => 未设置分账信息   店铺id:'.$orderInfo['distributor_id']);
                return;
            }
            // 获取用户进件信息
            $distributorHuifuId = $this->getHuifuId($orderInfo['company_id'], $orderInfo['distributor_id'], 'distributor');
            if (!$distributorHuifuId) {
                app('log')->debug('斗拱支付确认::getDivMember::失败 => 用户进件未成功   店铺id:'.$orderInfo['distributor_id']);
                return;
            }
            $divFlag = 'distributor';
            if ($distributorInfo['merchant_id'] > 0) {
                $divFlag = 'merchant';
            }
            $splitLedgerInfo = json_decode($distributorInfo['bspay_split_ledger_info'], true);
            if ($divFlag == 'merchant') { // 关联商户，三方分账
                // 如果平台分账比例为0，去查看商品佣金配置,用来计算平台的分账金额
                if ($splitLedgerInfo['headquarters_proportion'] == 0) {
                    $headquartersFee = $this->getItemHeadquartersFee($orderInfo['company_id'], $orderInfo['order_id'], $totalFee, $feeAmt, $headquarters_fee_mode);
                } else {
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $headquartersFee = number_format(round($divFee * $headquartersProportion, 2), 2, '.', '');
                }
                $merchantProportion = $splitLedgerInfo['merchant_proportion'] / 100;
                $merchantFee = number_format(round($divFee * $merchantProportion, 2), 2, '.', '');
                $distributorFee = number_format($divFee - $headquartersFee - $merchantFee, 2, '.', '');
                $merchantHuifuId = $this->getHuifuId($orderInfo['company_id'], $distributorInfo['merchant_id'], 'merchant');
                if (!$merchantHuifuId) {
                    app('log')->debug('斗拱支付确认::getDivMember::失败 => 用户进件未成功 商户id:'.$distributorInfo['merchant_id']);
                    return;
                }

                $divMember[] = [
                    'operator_type' => 'admin',//主商户
                    'huifu_id' => $setting['sys_id'],
                    'div_amt' => $headquartersFee,
                ];
                $divMember[] = [
                    'operator_type' => 'distributor',//店铺, 子商户
                    'huifu_id' => (string)$distributorHuifuId,
                    'div_amt' => $distributorFee,
                    'distributor_id' => $orderInfo['distributor_id'],
                ];
                $divMember[] = [
                    'operator_type' => 'merchant',//商户, 子商户
                    'huifu_id' => (string)$merchantHuifuId,
                    'div_amt' => $merchantFee,
                    'merchant_id' => $distributorInfo['merchant_id'],
                ];
            } else { //未关联商户两方分账
                // 如果平台分账比例为0，去查看商品佣金配置,用来计算平台的分账金额
                if ($splitLedgerInfo['headquarters_proportion'] == 0) {
                    $headquartersFee = $this->getItemHeadquartersFee($orderInfo['company_id'], $orderInfo['order_id'], $totalFee, $feeAmt, $headquarters_fee_mode);
                } else {
                    $headquartersProportion = $splitLedgerInfo['headquarters_proportion'] / 100;
                    $headquartersFee = number_format(round($divFee * $headquartersProportion, 2), 2, '.', '');
                }
                $distributorFee = number_format($divFee - $headquartersFee, 2, '.', '');
                $divMember[] = [
                    'operator_type' => 'admin',//主商户
                    'huifu_id' => $setting['sys_id'],//主商户
                    'div_amt' => $headquartersFee,
                ];
                $divMember[] = [
                    'operator_type' => 'distributor',//店铺, 子商户
                    'huifu_id' => (string)$distributorHuifuId,//店铺用户
                    'div_amt' => $distributorFee,
                    'distributor_id' => $orderInfo['distributor_id'],
                ];
            }
        }
        $result = [
            'headquarters_fee_mode' => $headquarters_fee_mode,
            'fee_amt' => $feeAmt,
            'div_members' => $divMember,
        ];
        app('log')->info('斗拱支付确认::getDivMember::result====>'.json_encode($result));
        return $result;
    }

    /**
     * 获取订单的商品平台佣金
     */
    public function getItemHeadquartersFee($companyId, $orderId, $totalFee, $feeAmt, $headquarters_fee_mode)
    {
        $headquartersFee = 0;
        $normalOrdersItemsRepository = app('registry')->getManager('default')->getRepository(NormalOrdersItems::class);

        $filter = [
            'company_id' => $companyId,
            'order_id' => $orderId
        ];

        $rsList = $normalOrdersItemsRepository->getList($filter);
        if ($rsList['total_count'] == 0) {
            return 0;
        }
        $itemsCommissionService = new ItemsCommissionService();

        $itemIds = array_column($rsList['list'], 'item_id');
        $commissionList = $itemsCommissionService->getAllCommissionByItem($companyId, $itemIds);
        if (empty($commissionList)) {
            return 0;
        }
        $orderItems = array_column($rsList['list'], null, 'item_id');
        foreach ($itemIds as $item_id) {
            if (!isset($commissionList[$item_id])) {
                continue;
            }
            $divFee = $itemTotalFee = bcdiv($orderItems[$item_id]['total_fee'], 100, 2);
            // app('log')->info('itemTotalFee===>'.$itemTotalFee);
            // app('log')->info('totalFee===>'.$totalFee);
            // app('log')->info('feeAmt===>'.$feeAmt);
            if ($headquarters_fee_mode == '2') {
                // 内扣，可分账金额 = 订单单金额-交易手续费
                $itemFeeAmt = number_format(round($itemTotalFee / $totalFee * $feeAmt, 2), 2, '.', '');//子单手续费
                // app('log')->info('itemFeeAmt===>'.$itemFeeAmt);
                $divFee = $itemTotalFee - $itemFeeAmt;
                // app('log')->info('divFee===>'.$divFee);
            }
            $commissionInfo = $commissionList[$item_id];
            $commission = !empty($commissionInfo['sku_commission']) ? $commissionInfo['sku_commission'] : $commissionInfo['commission'];
            if ($commissionInfo['commission_type'] == '1') {// 按比例
                $headquartersFee += number_format(round($divFee * $commission / 100, 2), 2, '.', '');
                // app('log')->info('commission===>'.$commission);
                // app('log')->info('headquartersFee===>'.$headquartersFee);
            } else {
                // 固定金额 佣金 * 商品数量
                $headquartersFee += number_format($commission / 100 * $orderItems[$item_id]['num'], 2, '.', '');
            }
        }
        $headquartersFee = number_format(round($headquartersFee, 2), 2, '.', '');
        return $headquartersFee;
    }
    public function getFeeRate($companyId, $payChannel)
    {
        $setting = $this->getPaymentSetting($companyId);
        if (!$setting) {
            return null;
        }

        // if ($payChannel == 'wx_qr') {
        //     $payChannel = 'wx_pub';
        // }
        switch ($payChannel) {
            case 'wx_lite':
            case 'wx_pub':
            case 'wx_qr':
                return $setting[$payChannel.'_'.$setting['wxpay_fee_type']] ?? null;
            // case 'alipay':
            case 'alipay_wap':
                return $setting['alipay_call'] ?? null;
            case 'alipay_qr':
                return $setting[$payChannel.'_'.$setting['alipay_fee_type']] ?? null;
        }
    }

    /**
     * 汇付取现接口
     *
     * @param array $params
     * @return array
     * @throws ResourceException
     */
    public function doWithdraw($params)
    {
        $companyId = $params['company_id'];

        // 获取汇付配置
        $config = $this->getPaymentSetting($companyId);
        if (empty($config)) {
            throw new ResourceException('汇付配置不存在，请先配置汇付支付');
        }

        if (empty($config['is_open']) || !$config['is_open']) {
            throw new ResourceException('汇付支付未开启');
        }

        // 验证必填参数
        if (empty($params['token_no'])) {
            throw new ResourceException('取现卡序列号不能为空，请先绑定银行卡');
        }
        // 构建请求参数（按照PaymentJspay的模式）
        $obj_params = array(
            'req_seq_id' => $this->generateWithdrawReqSeqId(),
            'cash_amt' => bcdiv($params['amount'], 100, 2), // 取现金额，元为单位
            'huifu_id' => $params['huifu_id'],
            'into_acct_date_type' => $params['withdraw_type'] ?? 'T1', // 默认T1：次工作日到账
            'token_no' => $params['token_no'], // 取现卡序列号（必填）
            'enchashment_channel' => '00', // 00：汇付（默认）
            'remark' => '手动提现申请',
            'notify_url' => config('bspay.notify_url').'/withdraw.bspay', // 异步通知地址
        );

        $paymentWithdraw = new PaymentWithdraw($companyId);
        app('log')->info('bspay::doWithdraw::obj_params====>'.json_encode($obj_params));
        $resData = $paymentWithdraw->handle($obj_params);
        app('log')->info('bspay::doWithdraw:resData====>'.json_encode($resData));

        // 首先检查API调用是否成功
        if (isset($resData['msg'])) {
            // API调用失败，返回错误信息
            $errorMsg = $resData['msg'] ?? '汇付API调用失败';
            app('log')->error('bspay::doWithdraw::汇付取现API调用失败::huifu_id:'.$params['huifu_id'].',amount:'.$params['amount'].',error:'.$errorMsg);
            throw new ResourceException($errorMsg);
        }

        // 处理响应结果
        $result = $resData['data'] ?? [];
        
        // 检查业务返回码和交易状态
        $respCode = $result['resp_code'] ?? '';
        $transStat = $result['trans_stat'] ?? '';
        $respDesc = $result['resp_desc'] ?? '汇付取现失败';
        
        if ($respCode === '00000000' && in_array($transStat, ['S', 'P'])) {
            // 业务成功且交易状态为成功
            return [
                'success' => true,
                'hf_seq_id' => $result['hf_seq_id'] ?? '', // 汇付全局流水号
                'req_seq_id' => $result['req_seq_id'] ?? '', // 请求流水号
                'trans_stat' => $transStat,
                'data' => $result
            ];
        } else {
            // 根据交易状态提供具体错误信息
            if ($respCode === '00000000' && $transStat === 'F') {
                $errorMsg = '取现处理失败：' . $respDesc;
            } else {
                $errorMsg = $respDesc;
            }
            
            app('log')->error('bspay::doWithdraw::汇付取现接口调用失败::huifu_id:'.$params['huifu_id'].',amount:'.$params['amount'].',resp_code:'.$respCode.',trans_stat:'.$transStat.',error:'.$errorMsg);
            throw new ResourceException($errorMsg);
        }
    }

    /**
     * 生成取现请求流水号
     *
     * @return string
     */
    private function generateWithdrawReqSeqId()
    {
        return 'W' . date('YmdHis') . mt_rand(1000, 9999);
    }

    /**
     * 获取汇付用户ID
     *
     * @param int $companyId
     * @param int $operatorId
     * @param string $operatorType
     * @return string|null
     */
    public function getHuifuId($companyId, $operatorId, $operatorType)
    {
        // 平台端，从配置中获取
        if ($operatorType == 'admin' || $operatorType == 'staff') {
            $setting = $this->getPaymentSetting($companyId);
            return $setting['sys_id'] ?? null;
        }
        // 其他端，从进件用户表中获取
        $bsPayUserService = new UserService();
        return $bsPayUserService->getHuifuIdByOperatorId($companyId, $operatorId, $operatorType);
    }
}
