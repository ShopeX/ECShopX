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

use AftersalesBundle\Services\AftersalesRefundService;
use OrdersBundle\Events\OrderProcessLogEvent;
use OrdersBundle\Jobs\TradeRefundStatistics;
use OrdersBundle\Services\Orders\NormalOrderService;
use OrdersBundle\Services\TradeService;
use PaymentBundle\Interfaces\Payment;
use PaymentBundle\Services\PaymentsService;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use IcbcPayBundle\Services\IcbcPayApiService;


class IcbcPayService extends IcbcPayApiService implements Payment
{

    /**
     * 预存款充值，本项目不需要，所以这里不写了
     */
    public function depositRecharge($authorizerAppId, $wxaAppId, array $data)
    {
        return $data;
    }



    public function doRefund($companyId, $wxaAppId, $data)
    {
        app('log')->info('doRefund_icbcpay_' . $data['order_id'] . ':' . $data['refund_bn']);

        $apiMethod = 'online_merrefund_v1';
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->tradeRepository->getInfo(['trade_id' => $data['trade_id']]);

        if(!$tradeInfo){
            throw new BadRequestHttpException('交易单号不存在');
        }

        if ($data['refund_fee'] <= 0) {
            throw new BadRequestHttpException('0元订单不支持退款');
        }

        $apiParams = [
            'company_id' => $data['company_id'],//
            'outtrx_serial_no' => $data['refund_bn'], // 退货流水号，商户系统生成的退款编号，每次部分退款需生成不同的退款编号。示例值：REFUND123456789
            'order_id' => $tradeInfo['transaction_id'], // 工行订单号，商户订单号或工行订单号必输其一。示例值：ICBC123456789
            'out_trade_no' => $data['trade_id'], // 商户订单号（或POS交易检索号），商户订单号或工行订单号必输其一。示例值：MERCHANTORDER12345
            'ret_total_amt' => $data['refund_fee'], // 退款金额，单位：分。示例值：100
            'trnsc_ccy' => '001', // 交易币种，目前只支持人民币，送001。示例值：001
            'icbc_appid' => '', // 商户在工行API平台的APPID。示例值：10000000000000002889
            'mer_acct' => '', // 商户清算账号，暂不使用。示例值：6212880200000038618
            'order_apd_inf' => '', // 订单附加信息，订单关联
            'mer_prtcl_no' => '', // 协议编号，多协议退款时必输。示例值：965412357
            'acq_addn_data' => '', // 订单详细信息（支持单品）,采用 JSON 格式，全部内容用“{}”包含，内部可包含多个子域。子域取值见备注。示例值：{ “goodsInfo” : [ {“id”: “1234567890”, “name”: “商品 1”, “price”: “500”, “quantity”: “1” },{“id”: “1234567891”, “name”: “商品 2”, “price”: “1000”, “quantity”: “2”, “category”: “类目 1” } ] }
        ];

        $result = $this->callApi($apiMethod, $apiParams);



        if (empty($result)) {
            throw new BadRequestHttpException($this->errMsg);
        }

        if ($result['return_code'] == '0') {

            $orderProcessLog = [
                'order_id' => $data['order_id'],
                'company_id' => $companyId,
                'operator_type' => 'system',
                'remarks' => '订单退款',
                'detail' => '订单号：' . $data['order_id'] . '，订单退款成功（微信支付渠道）',
            ];
            $return['status'] = 'SUCCESS';
            $return['refund_id'] = $result['intrx_serial_no'];

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


    public function getPayOrderInfo($companyId, $trade_id = '', $order_id = '')
    {
        // Ver: 8d1abe8e
        $apiMethod = 'online_orderqry_v1';
        $apiParams = [
            'company_id' => $companyId,
            'out_trade_no' => $trade_id, // 商户订单号，只能是数字、大小写字母，且在同一个商户号下唯一，商户订单号或工行订单号必输其一。示例值：65964126858
            'order_id' => $order_id, // 工行订单号，商户订单号或工行订单号必输其一。示例值：ICBC123456789
            'deal_flag' => '0', // 操作标志，0-查询；1-关单 2-关单（不支持二次支付）,3-云MIS订单关单标志）。示例值：0
            'icbc_appid' => '', // 工行API平台的APPID。示例值：10000000000000002889
            'mer_prtcl_no' => '', // 协议编号。示例值：965412357
        ];

        $payRes = $this->callApi($apiMethod, $apiParams);


        return $payRes;
    }



    public function doPay($authorizerAppId, $wxaAppId, array $data)
    {
        // Ver: 8d1abe8e
        $apiMethod = 'online_consumepurchase_v1';
        $notify_url = env('APP_URL').$this->notify_uri;
        $expire_minutes = 30;
        $normalOrderService = new NormalOrderService();
        $order_cancel_time = $normalOrderService->getOrdersSetting($data['company_id'], 'order_cancel_time');
        if ($order_cancel_time) {
            $expire_minutes = $order_cancel_time;
        }
        $expire_time = $expire_minutes * 60 ;//订单绝对超时时间
        $attach = ['company_id'=>$data['company_id']];
        $apiParams = [
            'company_id'=>$data['company_id'],
            'out_trade_no' => $data['trade_id'], // 商户订单号，只能是数字、大小写字母，且在同一个商户号下唯一。示例值：65964126858
            'pay_mode' => '9', // 支付方式，9-微信；10-支付宝；13-云闪付。示例值：9
            'access_type' => '9', // 收单接入方式，5-APP，7-微信公众号，8-支付宝生活号，9-小程序。示例值：5
            'mer_prtcl_no' => '', // 收单产品协议编号
            'orig_date_time' => date('Y-m-dTH:i:s'), // 交易日期时间，格式为yyyy-MM-dd’T’HH:mm:ss。示例值：2019-07-09T12:11:03
            'decive_info' => '', // 设备号。示例值：013467007045764
            'body' => $data['body'], // 商品描述，商品描述交易字段格式根据不同的应用场景按照以下格式：1）PC网站：传入浏览器打开的网站主页title名-实际商品名称 ；2）公众号：传入公众号名称-实际商品名称；3）H5：传入浏览器打开的移动网页的主页title名-实际商品名称；4）线下门店：门店品牌名-城市分店名-实际商品名称；5）APP：传入应用市场上的APP名字-实际商品名称。示例值：喜士多
            'fee_type' => '001', // 交易币种，目前工行只支持使用人民币（001）支付。示例值：001
            'spbill_create_ip' => $data['client_ip'] ?? 0, // 用户端IP
            'total_fee' => $data['pay_fee'], // 订单金额，单位为分。示例值：100
            'mer_url' => $notify_url, // 异步通知商户URL，端口必须为443或80。示例值：http://www.test.com/notifyurl
            'shop_appid' => $wxaAppId, // 商户在微信开放平台注册的APPID，支付方式为微信时不能为空
            'icbc_appid' => '', // 商户在工行API平台的APPID。示例值：10000000000000002889
            'open_id' => $data['open_id'], // 第三方用户标识，商户在微信公众号内或微信小程序内接入时必送，即access_type为7或9时，上送用户在商户APPID下的唯一标识；商户通过支付宝生活号接入时不送。示例值：oUSDOusdsdISLSDlskdf
//            'union_id' => '', // 第三方用户标识，商户在支付宝生活号接入时必送，即access_type为8时，上送用户的唯一标识；商户通过微信公众号内或微信小程序接入时不送。示例值：oUSDOusdsdISLSDlskdf
//            'mer_acct' => '', // 商户账号，商户入账账号，只能交易时指定。（商户付给银行手续费的账户，可以在开户的时候指定，也可以用交易指定方式；用交易指定方式则使用此商户账号）目前暂不支持。示例值：6212880200000038618
            'expire_time' => $expire_time, // 订单失效时间，单位为秒，建议大于60秒。示例值：120
            'attach' => json_encode($attach), // 附加数据，在查询API和支付通知中原样返回，该字段主要用于商户携带订单的自定义数据
            'notify_type' => 'HS', // 通知类型，表示在交易处理完成后把交易结果通知商户的处理模式。取值“HS”：在交易完成后将通知信息，主动发送给商户，发送地址为mer_url指定地址；取值“AG”：在交易完成后不通知商户。示例值：HS
            'result_type' => '0', // 结果发送类型，通知方式为HS时有效。取值“0”：无论支付成功或者失败，银行都向商户发送交易通知信息；取值“1”，银行只向商户发送交易成功的通知信息。默认是”0”。示例值：0
//            'pay_limit' => 'no_credit', // 支付方式限定，上送”no_credit“表示不支持信用卡支付；上送“no_balance”表示仅支持银行卡支付；不上送或上送空表示无限制。示例值：no_credit
            'order_apd_inf' => '', // 订单附加信息
            'detail' => $data['detail'], // 商品详细描述，对于使用单品优惠的商户，该字段必须按照规范上传。微信与支付宝的规范不同，请根据支付方式对应相应的规范上送，详细内容参考文末说明
//            'return_url' => '', // 支付成功回显页面，支付成功后，跳转至该页面显示。当access_type=5且pay_mode=10才有效
//            'quit_url' => '', // 用户付款中途退出返回商户网站的地址（仅对浏览器内支付时有效）当access_type=5且pay_mode=10才有效
//            'cust_name' => '', // 客户姓名（仅实名认证输入）
//            'cust_cert_type' => '', // 证件类型（仅实名认证输入）。0-身份证；1-护照；2-军官证;3-士兵证；6-户口薄（微信支付仅支持身份证）
//            'cust_cert_no' => '', // 证件号码（仅实名认证输入）
//            'goods_tag' => '', // 订单优惠标记
//            'acq_addn_data' => '', // 订单详细信息（支持单品）,采用 JSON 格式，全部内容用“{}”包含，内部可包含多个子域。子域取值见备注。示例值：{ “goodsInfo” : [ {“id”: “1234567890”, “name”: “商品 1”, “price”: “500”, “quantity”: “1” },{“id”: “1234567891”, “name”: “商品 2”, “price”: “1000”, “quantity”: “2”, “category”: “类目 1” } ] }
        ];
        $this->setTestParams($apiParams);



        $apiRes = $this->callApi($apiMethod, $apiParams);

        if (empty($apiRes)) {
            throw new BadRequestHttpException($this->errMsg);
        }
        $payData = $apiRes['wx_data_package'] ?? [];
        if (!$payData) {
            throw new BadRequestHttpException('支付错误，请稍后再试');
        }

        //保存支付参数，如果场景相同，下次可以重复使用
        $tradeService = new TradeService();
        $tradeService->updateOneBy(['trade_id' => $data['trade_id']], ['payment_params' => json_encode($payData, 256)]);
        return $payData;
    }


    public function getRefundOrderInfo($companyId, $refund_bn)
    {
        $apiMethod = 'online_refundqry_v1';
        $aftersalesRefundService = new AftersalesRefundService();
        $refund = $aftersalesRefundService->getInfo(['refund_bn' => $refund_bn]);
        $tradeService = new TradeService();
        $tradeInfo = $tradeService->tradeRepository->getInfo(['trade_id' => $refund['trade_id']]);
        $apiParams = [
            'company_id' => $companyId,
            'out_trade_no' => $refund['trade_id'], // 商户订单号，只能是数字、大小写字母，且在同一个商户号下唯一，商户订单号或工行订单号必输其一。示例值：65964126858
            'order_id' => $tradeInfo['transaction_id'], // 工行订单号，商户订单号或工行订单号必输其一。示例值：ICBC123456789
            'outtrx_serial_no' => $refund['refund_id'], // 工行API平台的APPID。示例值：10000000000000002889
            'mer_prtcl_no' => '', // 协议编号。示例值：965412357
        ];

        $result = $this->callApi($apiMethod, $apiParams);


        return $result;
    }

    /**
     * 设置支付配置
     */
    public function setPaymentSetting($companyId, $data)
    {
        $paySetting = [];
        $cacheData = app('redis')->get($this->genReidsId($companyId));
        if ($cacheData) {
            $paySetting = json_decode($cacheData, 1);
        }
        foreach ($data as $k => $v) {
            switch ($k) {
                case 'appid':
                case 'mer_id':
                case 'decive_info':
                case 'private_key':
                case 'public_key':
                case 'is_open':
                    $paySetting[$k] = $v;
                    break;
            }
        }
        return app('redis')->set($this->genReidsId($companyId), json_encode($paySetting, 256));
    }

    /**
     * 检查支付回调的参数签名
     */
    public function verifySignature($company_id, $rawData, &$errMsg = '')
    {
        $paymentSetting = $this->getPaymentSetting($company_id);
        if (!$paymentSetting) {
            $errMsg = '支付参数未配置';
            return false;
        }
        $signature = $rawData['sign'];
        $data = $rawData;
        unset($data['sign']);
        ksort($data);
        $sign_str = $this->notify_uri.'?';
        foreach ($data as $k=>$v){
            $sign_str.=$k.'='.$v.'&';
        }
        $sign_str = trim($sign_str,'&');

        $publickey = openssl_pkey_get_public($this->formatPublicKey($paymentSetting['public_key']));// 加载公钥

        $verify = openssl_verify($sign_str, $signature, $publickey, OPENSSL_ALGO_SHA256);
        openssl_free_key($publickey);
        if (!$verify) {
            $errMsg = '验签错误';
            // app('log')->error('LitePosNotify_$req_response => ' . $req_response[1]);
            // app('log')->error('LitePosNotify_$req_signature => ' . $req_signature[1]);
            return false;
        }
        return true;
    }

    /**
     * 商户收到通知后的响应，返回参数
     */
    public function responseSucc($companyId, $data = ['return_code'=>'0', 'return_msg'=>'success',])
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if (!$paymentSetting) {
            $data =  ['return_code'=>'8', 'return_msg'=>'支付参数未配置',];
        }
        $response_biz_content = $data;
        $response_biz_content['msg_id'] = date('YmdHis').randValue(8);

        $res = [
            'response_biz_content'=>$response_biz_content,
            'sign_type'=>'RSA',
        ];

        $sign_body = json_encode($res, JSON_UNESCAPED_UNICODE);

        //对请求体进行RSA加密  获取签名
        $privateKey = openssl_get_privatekey($this->formatPrivateKey($paymentSetting['private_key']));
        openssl_sign($sign_body, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        $sign = base64_encode($signature); // 对签名结果进行base64编码
        $res['sign'] = $sign;
        return json_encode($res,JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        $key = $this->settingKey . ':' . sha1($companyId);
        return  $key;
    }

}
