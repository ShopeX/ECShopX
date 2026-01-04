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

namespace IcbcPayBundle\Services;


use GuzzleHttp\Client as Client;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class IcbcPayApiService
{

    public $api_url= 'https://gw.open.icbc.com.cn';
    public $notify_uri = '/api/third/icbcpay/notify';
    public $api_list = [
        'online_consumepurchase_v1' => '/api/cardbusiness/aggregatepay/b2c/online/consumepurchase/V1',//下单
        'online_orderqry_v1' => '/api/cardbusiness/aggregatepay/b2c/online/orderqry/V1',//订单付款状态查询
        'online_merrefund_v1' => '/api/cardbusiness/aggregatepay/b2c/online/merrefund/V1',//申请退款
        'online_refundqry_v1' => '/api/cardbusiness/aggregatepay/b2c/online/refundqry/V1',//订单退款记录查询
    ];
    public $settingKey = 'icbcPaymentSetting';
    public $api_error = [];
    public $errMsg = '';
    public $test_api_setting = [

    ];

    public $icbcPayLogService;

    public function __construct()
    {
        // 53686f704578
        $this->icbcPayLogService = new IcbcApiLogsService();
    }

    /**
     * 或者支付方式配置
     */
    public function getPaymentSetting($companyId)
    {
        // 53686f704578
        $paySetting = [];
        $cacheData = app('redis')->get($this->genReidsId($companyId));

        if ($cacheData) {
            $paySetting = json_decode($cacheData, true);
        }

        $paySetting['is_open'] = $paySetting['is_open'] ?? 1;
        $paySetting['is_open'] = $paySetting['is_open'] ? true : false;
        return $paySetting;
    }

    public function callApi($apiMethod, $apiParams)
    {
        $companyId = $apiParams['company_id'] ?? 1;
        $order_id = $apiParams['out_trade_no'] ?? '';
        if ( empty($apiMethod)) {
            $this->errMsg = '请求方法不能为空！';
            return [];
        }
        if (!isset($this->api_list[$apiMethod])) {
            $this->errMsg = '调用了不存在的方法！';
            return [];
        }
        if ($this->test_api_setting) {
            $this->api_url = $this->test_api_setting['api_url'];
            $paymentSetting = $this->test_api_setting;
            $app_id = $this->test_api_setting['app_id'];
            $apiParams['mer_id'] = $this->test_api_setting['mer_id'];
        }else{
            $paymentSetting = $this->getPaymentSetting($companyId);
            $app_id = $paymentSetting['app_id'];
            $apiParams['mer_id'] = $paymentSetting['mer_id'];
        }

        if (!$paymentSetting) {
            $this->errMsg = '请先完成工商银行支付配置！';
            return [];
        }

        if(in_array($apiMethod,['online_consumepurchase_v1','online_orderqry_v1','online_merrefund_v1'])){
            $apiParams['icbc_appid'] = $app_id;
        }
        $api_name = $this->api_list[$apiMethod];
        $params = [
            'app_id' => $app_id, // APP的编号，应用在API开放平台注册时生成。示例值：10000000000004095781
            'msg_id' => date('YmdHis').randValue(8), // 消息通讯唯一编号，每次调用独立生成，APP级唯一。示例值：urcnl24ciutr9
            'format' => 'json', // 请求参数格式，仅支持json。示例值：json
            'charset' => 'UTF-8', // 字符集，缺省为UTF-8。示例值：UTF-8
            'encrypt_type' => '', // 本接口此字段无需上送。
            'sign_type' => 'RSA', // 签名类型，本接口为RSA2-RSAWithSha256认证方式。示例值：RSA2
            //'sign' => '', // 报文签名。示例值：ERITJKEIJKJHKKKHJEREEEEEE
            'timestamp' => date('Y-m-d H:i:s'), // 交易发生时间戳，yyyy-MM-dd HH:mm:ss格式。示例值：2016-10-29 20:44:38
            'ca' => '', // 本接口此字段无需上送。
            'biz_content' => json_encode($apiParams), // 请求参数的集合。
        ];

        ksort($params);
        $sign_str = $this->notify_uri.'?';
        foreach ($params as $k=>$v){
            $sign_str.=$k.'='.$v.'&';
        }
        $sign_str = trim($sign_str,'&');
        //对请求体进行RSA加密  获取签名
        $privateKey = openssl_get_privatekey($this->formatPrivateKey($paymentSetting['private_key']));
        openssl_sign($sign_str, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        $params['sign'] = base64_encode($signature);// 对签名结果进行base64编码
        $url = $this->api_url . $api_name;
        // 记录接口日志
        $log_id = $this->icbcPayLogService->apiStart($companyId, $api_name, $url, $params, $order_id);
        if (!empty($this->test_api_json)) {
            $res = $this->test_api_json;
        } else {
            app('log')->debug('icbcpayapi===>:params'.json_encode($params));

            $res = $this->apiRequest($url, $params);

            app('log')->debug('icbcpayapi===>:res'.$res);
        }
        $retJson = json_decode($res ,true);
        $result = $retJson['response_biz_content'] ?? [];
        // 接口请求结束
        if (isset($result['return_code']) && $result['return_code'] == 0) {// 成功
            $this->icbcPayLogService->apiEnd($log_id, $res);
            #todo 针对返回参数可以选择验证签名
            return $result ?? [];

        } else {// 成功
            $this->api_error = $result;// 失败记录
            $this->errMsg = $result['third_party_return_msg'] ?? '未知错误';
            $this->icbcPayLogService->apiEnd($log_id, $res);
            return [];


        }

    }

    public function getSign($companyId,$params)
    {
        $paymentSetting = $this->getPaymentSetting($companyId);
        if (!$paymentSetting) {
            throw new BadRequestHttpException('请先完成工商银行支付配置');
        }
        //对请求体进行RSA加密  获取签名
        $privateKey = openssl_get_privatekey($this->formatPrivateKey($paymentSetting['private_key']));
        openssl_sign($params, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        openssl_free_key($privateKey);
        $sign = base64_encode($signature); // 对签名结果进行base64编码
        return $sign;
    }


    function apiRequest($url, $post_data = array(),$type = 'json')
    {
        if ( '' == $url )
        {
            return false;
        }
        try {
            if($type == 'json'){
                $client = new Client();
                $option = [
//                'debug'=>true,
                    'timeout' => 5,
                    'headers' => [
                        'Content-Type'=>'application/x-www-form-urlencoded',
                    ],
                    'form_params' => $post_data
                ];
                app('log')->debug('icbcpayapi===>:request-option'.$option);
                //dd(($data));
                $retJson = $client->request('POST', $url, $option);
                $result = $retJson->getBody()->getContents();

            }elseif ($type == 'query'){
                $client = new Client();
                $option = [
                    'timeout' => 5,
                    'headers' => [
                        'Content-Type'=>'application/x-www-form-urlencoded',
                    ],
                    'query' => $post_data
                ];
                $retJson = $client->request('GET', $url, $option);
                $result = $retJson->getBody()->getContents();
            }
            app('log')->debug('icbcpayapi===>:request-result'.$result);

        } catch (\Exception $e) {
            app('log')->debug('icbcpayapiapi===>:request-error'.$e->getMessage());

            $result = $e->getMessage();
        }

        return $result;
    }

    public function formatPrivateKey($priKey)
    {
        if (strstr($priKey, 'BEGIN PRIVATE KEY')) {
            return $priKey;
        }
        $fKey = "-----BEGIN RSA PRIVATE KEY-----\n";
        $len = strlen($priKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($priKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END RSA PRIVATE KEY-----";
        return $fKey;
    }

    public function formatPublicKey($pubKey)
    {
        if (strstr($pubKey, 'BEGIN PUBLIC KEY')) {
            return $pubKey;
        }
        $fKey = "-----BEGIN PUBLIC KEY-----\n";
        $len = strlen($pubKey);
        for ($i = 0; $i < $len;) {
            $fKey = $fKey . substr($pubKey, $i, 64) . "\n";
            $i += 64;
        }
        $fKey .= "-----END PUBLIC KEY-----";
        return $fKey;
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        $key = $this->settingKey . ':' . sha1($companyId);
        return $key;
    }

}
