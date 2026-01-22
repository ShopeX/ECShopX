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

namespace PaymentBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\MemberService;
use AdaPayBundle\Services\OpenAccountService;
use Dingo\Api\Exception\ResourceException;
use PaymentBundle\Services\Payments\AdaPaymentService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PaymentBundle\Services\Payments\AlipayService;
use PaymentBundle\Services\Payments\HfPayService;
use PaymentBundle\Services\Payments\IcbcPayService;
use PaymentBundle\Services\Payments\OfflinePayService;
use PaymentBundle\Services\Payments\PaypalService;
use PaymentBundle\Services\Payments\WechatPayService;
use PaymentBundle\Services\PaymentsService;
use PaymentBundle\Services\Payments\ChinaumsPayService;
use PaymentBundle\Services\Payments\BsPayService;
use PointBundle\Services\PointMemberRuleService;

class Payment extends Controller
{
    /**
     * @SWG\Post(
     *     path="/trade/payment/setting",
     *     summary="支付配置信息保存",
     *     tags={"订单"},
     *     description="支付配置信息保存",
     *     operationId="setPaymentSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型", required=true, type="string"),
     *     @SWG\Parameter( name="config", in="query", description="配置信息json数据", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="status", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function setPaymentSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->input('distributor_id', 0);

        if ($request->input('pay_type') == 'wxpay') {
            $paymentsService = new WechatPayService($distributorId);
            $config['app_id'] = $request->input('app_id');
            $config['merchant_id'] = $request->input('merchant_id');
            $config['key'] = $request->input('key');
            $config['is_servicer'] = $request->input('is_servicer');
            $config['servicer_merchant_id'] = $request->input('servicer_merchant_id');
            $config['servicer_app_id'] = $request->input('servicer_app_id');
            $config['is_open'] = $request->input('is_open');

            //证书文件
            $config['cert'] = $request->file('cert');
            $config['cert_key'] = $request->file('cert_key');
            // 微信APP支付的应用ID
            $config['app_app_id'] = $request->input('app_app_id');
        } elseif ($request->input('pay_type') == 'alipay') {
            $paymentsService = new AlipayService($distributorId);
            $config['app_id'] = $request->input('app_id');
            $config['private_key'] = $request->input('private_key');
            $config['ali_public_key'] = $request->input('ali_public_key');
            $config['is_open'] = 'true' == $request->input('is_open') ? true : false;
        } elseif ($request->input('pay_type') == 'paypal') {
            $paymentsService = new PaypalService($distributorId);
            $config['client_id'] = $request->input('client_id');
            $config['client_secret'] = $request->input('client_secret');
            $config['sandbox'] = $request->input('sandbox') === 'true' ? true : false;
            $config['webhook_id'] = $request->input('webhook_id');
            $config['is_open'] = $request->input('is_open') === 'true' ? true : false;
        } elseif ($request->input('pay_type') == 'point_pay') {
            $point_pay_first = intval($request->input('point_pay_first', 0));
            $pointRule = (new PointMemberRuleService())->savePointRule($companyId, ['point_pay_first' => $point_pay_first]);
            return $this->response->array($pointRule);
        } elseif ($request->input('pay_type') == 'hfpay') {
            $paymentsService = new HfPayService();
            $config['mer_cust_id'] = $request->input('mer_cust_id');
            $config['acct_id'] = $request->input('acct_id');
            $config['pfx_password'] = $request->input('pfx_password');
            $config['pfx_file'] = $request->file('pfx_file');
            $config['ca_pfx_file'] = $request->file('ca_pfx_file');
            $config['oca31_pfx_file'] = $request->file('oca31_pfx_file');
            $config['is_open'] = $request->input('is_open');
        } elseif ($request->input('pay_type') == 'adapay') {
            $paymentsService = new AdaPaymentService();
            $config['app_id'] = $request->input('app_id');
            $config['test_api_key'] = $request->input('test_api_key');
            $config['live_api_key'] = $request->input('live_api_key');
            $config['rsa_private_key'] = $request->input('rsa_private_key');
            $config['pay_channel'] = $request->input('pay_channel');
            $config['wxpay_fee_type'] = $request->input('wxpay_fee_type');
            $config['wx_pub_online'] = $request->input('wx_pub_online');
            $config['wx_pub_offline'] = $request->input('wx_pub_offline');
            $config['wx_lite_online'] = $request->input('wx_lite_online');
            $config['wx_lite_offline'] = $request->input('wx_lite_offline');
            $config['wx_scan'] = $request->input('wx_scan');
            $config['alipay_fee_type'] = $request->input('alipay_fee_type');
            $config['alipay_qr_online'] = $request->input('alipay_qr_online');
            $config['alipay_qr_offline'] = $request->input('alipay_qr_offline');
            $config['alipay_scan'] = $request->input('alipay_scan');
            $config['alipay_lite_online'] = $request->input('alipay_lite_online');
            $config['alipay_lite_offline'] = $request->input('alipay_lite_offline');
            $config['alipay_call'] = $request->input('alipay_call');
            $config['ali_pub_off_b2b'] = $request->input('ali_pub_off_b2b');
            $config['ali_pub_online_b2b'] = $request->input('ali_pub_online_b2b');
            $config['is_open'] = $request->input('is_open') == 'true' ? true : false;
        } elseif ($request->input('pay_type') == 'offline_pay') {
            $paymentsService = new OfflinePayService();
            $pay_name = $request->input('pay_name', '线下支付');
            $config['pay_name'] = $pay_name != '' ? $pay_name : '线下支付';// 支付方式名称
            $config['pay_tips'] = $request->input('pay_tips', '');// 收款说明
            $config['pay_desc'] = $request->input('pay_desc', '');// 付款说明
            $config['auto_cancel_time'] = intval($request->input('auto_cancel_time', 0));//小时
            if ($config['auto_cancel_time'] < 1) {
                throw new ResourceException('订单自动取消时间不能小于1小时');
            }
            $config['is_open'] = $request->input('is_open', 'false') == 'true' ? 'true' : 'false';
        } elseif ($request->input('pay_type') == 'chinaumspay') {
            $operatorType = app('auth')->user()->get('operator_type');
            if ($operatorType == 'dealer') {
                $operatorId = app('auth')->user()->get('operator_id');
                $paymentsService = new ChinaumsPayService('dealer_'.$operatorId);
            } else {
                $distributorId = $request->input('distributor_id', 0);
                if ($distributorId > 0) {
                    $paymentsService = new ChinaumsPayService('distributor_'.$distributorId);
                } else {
                    $paymentsService = new ChinaumsPayService();
                }
            }

            $config['mid'] = $request->input('mid');// 商户号
            $config['tid'] = $request->input('tid');// 终端号
            $config['enterpriseid'] = $request->input('enterpriseid');// 企业用户号
            if ($operatorType != 'dealer' && $distributorId == 0) {
                $config['rate'] = $request->input('rate', '0');// 收单手续费  0.23%  设置0.23的数值
                //证书文件
                $config['rsa_private'] = $request->file('rsa_private');
                $config['password'] = $request->input('password');
                $config['rsa_public'] = $request->file('rsa_public');

                //平台分账信息
                $config['bank_name'] = $request->input('bank_name');// 开户行名称
                $config['bank_code'] = $request->input('bank_code');// 开户行行号
                $config['bank_account'] = $request->input('bank_account');// 开户行账号

                $config['is_open'] = $request->input('is_open') == 'true' ? true : false;
            }
        } elseif ($request->input('pay_type') == 'bspay') {
            // 汇付斗拱支付
            $paymentsService = new BsPayService();
            $config['sys_id'] = $request->input('sys_id');
            $config['product_id'] = $request->input('product_id');
            $config['rsa_merch_private_key'] = $request->input('rsa_merch_private_key');// 商户私钥
            $config['rsa_huifu_public_key'] = $request->input('rsa_huifu_public_key');// 汇付公钥
            $config['admin_token_no'] = $request->input('admin_token_no');// 管理员提现卡序列号
            // pay_channel 支付渠道 wx_lite 微信小程序支付 wx_pub 微信公众号支付 wx_qr 微信扫码支付 alipay_qr 支付宝扫码支付 alipay_wap 支付宝唤起支付
            $config['pay_channel'] = $request->input('pay_channel', []);
            $config['wxpay_fee_type'] = $request->input('wxpay_fee_type');//微信支付费率类型
            $config['wx_lite_online'] = $request->input('wx_lite_online');// 微信小程序支付（线上）
            $config['wx_pub_online'] = $request->input('wx_pub_online');//微信公众号支付（线上）
            $config['wx_qr_online'] = $request->input('wx_qr_online');// 微信扫码费率线上
            $config['alipay_fee_type'] = $request->input('alipay_fee_type');//支付宝支付费率类型
            $config['alipay_call'] = $request->input('alipay_call');//h5支付宝唤起支付
            $config['alipay_qr_online'] = $request->input('alipay_qr_online');// 支付宝扫码费率线上
            $config['is_open'] = $request->input('is_open') == 'true' ? true : false;
        }elseif ($request->input('pay_type') == 'icbcpay') {
            $paymentsService = new IcbcPayService();
            $config['appid'] = $request->input('appid');
            $config['mer_id'] = $request->input('mer_id');
            $config['decive_info'] = $request->input('decive_info');
            $config['public_key'] = $request->input('public_key');
            $config['private_key'] = $request->input('private_key');
            $config['is_open'] = $request->input('is_open', 'false') == 'true' ? 1 : 0;
        }

        $service = new PaymentsService($paymentsService);
        $service->setPaymentSetting($companyId, $config);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/trade/payment/setting",
     *     summary="获取支付配置信息",
     *     tags={"订单"},
     *     description="获取支付配置信息",
     *     operationId="setPaymentSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="pay_type", in="query", description="支付类型", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                   @SWG\Property(property="app_id", type="stirng"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getPaymentSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = $request->input('distributor_id', 0);
        if ($request->input('pay_type') == 'wxpay') {
            $paymentsService = new WechatPayService($distributorId, false);
        } elseif ($request->input('pay_type') == 'point_pay') {
            $pointRule = (new PointMemberRuleService())->getPointRule($companyId);
            return $this->response->array($pointRule);
        } elseif ($request->input('pay_type') == 'alipay') {
            $paymentsService = new AlipayService($distributorId, false);
        } elseif ($request->input('pay_type') == 'hfpay') {
            $paymentsService = new HfPayService();
        } elseif ($request->input('pay_type') == 'adapay') {
            $paymentsService = new AdaPaymentService();
        } elseif ($request->input('pay_type') == 'offline_pay') {
            $paymentsService = new OfflinePayService();
        }  elseif ($request->input('pay_type') == 'icbcpay') {
            $paymentsService = new IcbcPayService();
        }  elseif ($request->input('pay_type') == 'paypal') {
            $paymentsService = new PaypalService($distributorId);
        }  elseif ($request->input('pay_type') == 'chinaumspay') {
            $operatorType = app('auth')->user()->get('operator_type');
            if ($operatorType == 'dealer') {
                $operatorId = app('auth')->user()->get('operator_id');
                $paymentsService = new ChinaumsPayService('dealer_'.$operatorId);
            } else {
                $distributorId = $request->input('distributor_id', 0);
                if ($distributorId > 0) {
                    $paymentsService = new ChinaumsPayService('distributor_'.$distributorId);
                } else {
                    $paymentsService = new ChinaumsPayService();
                }
            }
        } elseif ($request->input('pay_type') == 'bspay') {
            $paymentsService = new BsPayService();
        }

        $service = new PaymentsService($paymentsService);
        $data = $service->getPaymentSetting($companyId);

        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/trade/payment/open-status",
     *     summary="获取支付配置开关状态",
     *     tags={"订单"},
     *     description="获取支付配置开关状态整合信息，统一返回is_open为bool类型",
     *     operationId="getPaymentOpenStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="wxpay", type="object",
     *                     @SWG\Property(property="is_open", type="boolean", example=true),
     *                 ),
     *                 @SWG\Property(property="alipay", type="object",
     *                     @SWG\Property(property="is_open", type="boolean", example=false),
     *                 ),
     *                 @SWG\Property(property="chinaumspay", type="object",
     *                     @SWG\Property(property="is_open", type="boolean", example=true),
     *                 ),
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getPaymentOpenStatus(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $operatorId = app('auth')->user()->get('operator_id');
        
        // 调用Service获取支付开关状态
        $paymentsService = new PaymentsService();
        $result = $paymentsService->getPaymentOpenStatusList($companyId, $operatorType, $operatorId);
        
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trade/payment/list",
     *     summary="获取支付配置信息列表",
     *     tags={"订单"},
     *     description="获取支付配置信息列表",
     *     operationId="list",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                    @SWG\Property(property="pay_type_code", type="string", description="支付渠道编码"),
     *                    @SWG\Property(property="pay_type_name", type="string", description="支付渠道名称"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getPaymentSettingList(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $distributorId = $request->input('distributor_id', 0);

        $result = [];

        //adapay
        $service = new AdaPaymentService();
        $adapay = $service->getPaymentSetting($company_id);
        $openAccountService = new OpenAccountService();
        $step = $openAccountService->openAccountStepService($company_id);
        if (!empty($adapay) && $adapay['is_open'] && $step['step'] == 4) {
            $memberService = new MemberService();
            if ($distributorId == 0) {
                $result[] = [
                    'pay_type_code' => 'adapay',
                    'pay_channel' => 'wx_lite',
                    'pay_type_name' => '微信支付'
                ];
            } else {
                $memberInfo = $memberService->getInfo(['company_id' => $company_id, 'operator_id' => $distributorId, 'operator_type' => 'distributor', 'audit_state' => 'E']);
                if ($memberInfo) {
                    $result[] = [
                        'pay_type_code' => 'adapay',
                        'pay_channel' => 'wx_lite',
                        'pay_type_name' => '微信支付'
                    ];
                }
            }
        }

        //如果支持adapay支付默认走adapay  不走wxpay
        if (!$result) {
            //微信设置
            $service = new WechatPayService($distributorId);
            $wechat = $service->getPaymentSetting($company_id);
            if (!empty($wechat) && $wechat['is_open'] == 'true') {
                $result[] = [
                    'pay_type_code' => 'wxpay',
                    'pay_type_name' => '微信支付'
                ];
            }
        }


        //汇付天下设置
        $service = new HfPayService();
        $hfpay = $service->getPaymentSetting($company_id);
        if (!empty($hfpay) && $hfpay['is_open'] === 'true') {
            $result[] = [
                'pay_type_code' => 'hfpay',
                'pay_type_name' => '微信支付'
            ];
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trade/payment/hfpayversionstatus",
     *     summary="获取汇付版本状态",
     *     tags={"订单"},
     *     description="获取汇付版本状态",
     *     operationId="hfpayversionstatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="hfpay_version_status", type="string", description="是否汇付版本 true 汇付版本 false 非汇付版本"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PaymentErrorRespones") ) )
     * )
     */
    public function getHfpayVersionStatus()
    {
        $hfpay_is_open = config('common.hfpay_is_open');
        $data = [
            'hfpay_version_status' => $hfpay_is_open
        ];

        return $this->response->array($data);
    }

    public function genRsaKey()
    {
        $config = [
            'digest_alg' => 'sha512',
            'private_key_bits' => 1024,           //字节数  512 1024 2048  4096 等 ,不能加引号，此处长度与加密的字符串长度有关系
            'private_key_type' => OPENSSL_KEYTYPE_RSA,   //加密类型
        ];
        $res = openssl_pkey_new($config);

        //提取私钥
        openssl_pkey_export($res, $private_key);

        //生成公钥
        $public_key = openssl_pkey_get_details($res);

        $public_key = $public_key['key'];

        $private_key = preg_replace('/-----BEGIN PRIVATE KEY-----/', '', $private_key);
        $private_key = preg_replace('/-----END PRIVATE KEY-----/', '', $private_key);
        $private_key = preg_replace('/\n/', '', $private_key);

        $public_key = preg_replace('/-----BEGIN PUBLIC KEY-----/', '', $public_key);
        $public_key = preg_replace('/-----END PUBLIC KEY-----/', '', $public_key);
        $public_key = preg_replace('/\n/', '', $public_key);

        return $this->response->array([
            'rsa_private_key' => $private_key,
            'rsa_public_key' => $public_key,
        ]);
    }
}
