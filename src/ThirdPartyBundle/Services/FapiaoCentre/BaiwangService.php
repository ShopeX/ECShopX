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

namespace ThirdPartyBundle\Services\FapiaoCentre;

use GuzzleHttp\Client;
use CompanysBundle\Services\SettingService;
use OrdersBundle\Traits\GetOrderServiceTrait;
use OrdersBundle\Services\OrderInvoiceService;
// use OrdersBundle\Services\OrderInvoiceItemService;

class BaiwangService
{
    use GetOrderServiceTrait;

    public $fapiao_config;
    public $settingService;
    public $apiUrl;
    public $tokenUrl;
    public $appKey;
    public $appSecret;
    public $token;
    private $demoFapiaoConfig;

    public function __construct($companyId = 1)
    {
        // BAIWANG_API_URL=https://sandbox-openapi.baiwang.com/router/rest
        // BAIWANG_TOKEN_URL=https://sandbox-openapi.baiwang.com/auth/token
        // BAIWANG_APP_KEY=your_app_key
        // BAIWANG_APP_SECRET=your_app_secret
        // BAIWANG_TOKEN=your_token

        // 测试环境 :
        // #BAIWANG_TOKEN_URL=https://sandbox-openapi.baiwang.com/router/rest
        // BAIWANG_USERNAME=admin_3sylog6ryv8cs
        // BAIWANG_APP_KEY=1002948
        // BAIWANG_PASSWORD=Aa2345678@
        // BAIWANG_APP_SECRET=223998c6-5b76-4724-b5c9-666ff4215b45
        // BAIWANG_SALT=521c0eea19f04367ad20a3be12c9b4bc
        // BAIWANG_TAXNO=338888888888SMB


        $this->settingService = new SettingService();

        // 实际API地址建议写到配置 env 中 默认一个沙箱地址
        // 正式环境：https://openapi.baiwang.com/router/rest
        // 沙箱环境：https://sandbox-openapi.baiwang.com/router/rest
        $this->apiUrl = env('BAIWANG_API_URL', 'https://sandbox-openapi.baiwang.com/router/rest');
        $this->tokenUrl = env('BAIWANG_TOKEN_URL', 'https://sandbox-openapi.baiwang.com/auth/token');
        $this->appKey = env('BAIWANG_APP_KEY', '');
        $this->appSecret = env('BAIWANG_APP_SECRET', '');
        $this->token = env('BAIWANG_TOKEN', '');

        // 获取配置
        // $this->demoFapiaoConfig = $this->getFapiaoConfigFromEnv();
        $this->getFapiaoConfigFromDb($companyId);
        $this->fapiao_config = $this->demoFapiaoConfig;
        $this->fapiao_config['invoiceTerminalCode'] = env('BAIWANG_INVOICE_TERMINAL_CODE', false);
        app('log')->debug("[BaiwangService][__construct] 百旺云配置: " . json_encode($this->demoFapiaoConfig));
    }
    /**
     * 从数据库获取配置
     * @param int $companyId
     * @return array
     *  "appKey": "1002948",
     *  "appSecret": "223998c6-5b76-4724-b5c9-666ff4215b45",
     *  "username": "admin_3sylog6ryv8cs",
     *  "password": "12322",
     *  "orgAuthCode": "521c0eea19f04367ad20a3be12c9b4bc",
     *  "taxNo": "338888888888SMB",
     *  "terminal": "",
     *  "mobile": "",
     *  "drawer": "",
     *  "payee": "",
     *  "checker": "",
     *  "tax_rate": "0.03",
     *  "api_url": "https:\/\/sandbox-openapi.baiwang.com\/router\/rest",
     *  "token_url": "https:\/\/sandbox-openapi.baiwang.com\/auth\/token",
     *  "updated_at": 1752217081
     */
    public function getFapiaoConfigFromDb($companyId){
        $baiwangInvoiceSetting = $this->getInvoiceSetting($companyId);
        $this->demoFapiaoConfig = $baiwangInvoiceSetting['data'];
        app('log')->debug("[BaiwangService][getFapiaoConfigFromDb] 百旺云配置: " . json_encode($this->demoFapiaoConfig));
        //TOKEN 获取
        try {
            $this->demoFapiaoConfig['token'] = $this->getToken([
                    'appKey'    => $this->demoFapiaoConfig['appKey'],
                    'appSecret' => $this->demoFapiaoConfig['appSecret'],
                ]);
        } catch (\Exception $e) {
            app('log')->debug("[BaiwangService][__construct] 获取token失败: " . $e->getMessage());
        }
        app('log')->debug("[BaiwangService][getFapiaoConfigFromDb] 百旺云配置: " . json_encode($this->demoFapiaoConfig));
        return $this->demoFapiaoConfig;
    }
    // public function getFapiaoConfigFromEnv(){
    //     $this->fapiao_config = [
    //         'appKey'      => env('BAIWANG_APP_KEY', ''),
    //         'appSecret'   => env('BAIWANG_APP_SECRET', ''),
    //         'username'    => env('BAIWANG_USERNAME', ''),
    //         'orgAuthCode' => env('BAIWANG_SALT', ''),
    //         'token'       => '',
    //         'taxNo'       => env('BAIWANG_TAXNO', '338888888888SMB'),
    //         'terminal'    => env('BAIWANG_TERMINAL', '202312120001'),
    //         'mobile'      => env('BAIWANG_MOBILE', '15888888888'),
    //         'drawer'      => '张三',
    //         'payee'       => '李四',
    //         'checker'     => '王五',
    //         'tax_rate'    => '0.03',
    //         // 其他可选参数
    //     ];
    // }
    /**
     * 发票开具 
     */
    public function createFapiao($params, $sourceType = 'normal')
    {
        $companyId = $params['company_id'];
        $orderId = $params['order_id'];

        // 获取发票配置信息
        $this->fapiao_config = $this->demoFapiaoConfig;

        
        // 获取订单信息
        $orderService = $this->getOrderService($sourceType);
        $orderData = $orderService->getOrderInfo($companyId, $orderId);
        app('log')->debug('[BaiwangService][createFapiao] 订单信息: ' . json_encode($orderData));
        
        $orderInfo = $orderData['orderInfo'];
        $tradeInfo = $orderData['tradeInfo'];

        
        // 根据订单号 查询开票信息orders_invoice
        $orderInvoiceService = new OrderInvoiceService();
        $filter = [
            'order_id' => $orderId,
            'invoice_status' => 'pending',
        ];
        $ordersInvoice = $orderInvoiceService->getInfo($filter);
        app('log')->debug('[BaiwangService][createFapiao] 开票信息: ' . json_encode($ordersInvoice));
        
        // 检查重试次数是否超过限制
        if (isset($ordersInvoice['try_times']) && $ordersInvoice['try_times'] >= 5) {
            app('log')->info('[BaiwangService][createFapiao] 重试次数检查', ['try_times' => $ordersInvoice['try_times']]);
            throw new \Exception('重试次数超过限制(5次)，无法继续开票');
        }
        
        if (!$ordersInvoice) {
            // return false;
        }



        // 组装业务参数
        $bodyParams = $this->formatBaiwangParams($orderInfo, $ordersInvoice, $params);
        // 组装协议参数
        $protocolParams = $this->buildProtocolParams('baiwang.s.outputinvoice.invoice', $this->fapiao_config);

        // 生成签名
        $sign = $this->makeSign($protocolParams, json_encode($bodyParams, JSON_UNESCAPED_UNICODE));
        $protocolParams['sign'] = $sign;

        // 发起请求
        $client = new \GuzzleHttp\Client();
        $url = $this->apiUrl . '?' . http_build_query($protocolParams);
        app('log')->debug('[BaiwangService][createFapiao] URL: ' . $url);
        app('log')->debug('[BaiwangService][createFapiao] BODY: ' . json_encode($bodyParams, JSON_UNESCAPED_UNICODE));
        $response = $client->post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($bodyParams, JSON_UNESCAPED_UNICODE),
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $result = json_decode($response->getBody(), true);
        $result['bodyParams'] = $bodyParams;
        $result['protocolParams'] = $protocolParams;
        app('log')->debug('[BaiwangService][createFapiao] 响应: ' . json_encode($result));
        return $result;
    }

    /**
     * 组装百旺API业务参数（不含协议参数）
     */
    private function formatBaiwangParams($orderInfo, $ordersInvoice, $params = [])
    {
        app('log')->debug('[BaiwangService][formatBaiwangParams] 订单信息: ' . json_encode($orderInfo));
        app('log')->debug('[BaiwangService][formatBaiwangParams] 开票信息: ' . json_encode($ordersInvoice));
        $refundDetail = $params['refundDetail'] ?? [];
        app('log')->debug('[BaiwangService][formatBaiwangParams] 退货信息: ' . json_encode($refundDetail));
        // 查询商品税率
        $orderInvoiceService = new OrderInvoiceService();
        $itemRateMap = $orderInvoiceService->getInvoiceRateBatch($orderInfo['items'], $orderInfo['company_id']);
        
        app('log')->debug('[BaiwangService][formatBaiwangParams] 商品税率查询结果: ' . json_encode($itemRateMap));
        // 商品明细
        $invoiceDetailList = [];
        foreach ($orderInfo['items'] as $item) {
            // 获取商品税率，如果没有查询到则使用默认税率
            $itemRate = $itemRateMap[$item['item_id']] ?? '13';
            // 转换为百分比格式（如：  13） -> 0.13
            $itemRate = number_format($itemRate / 100, 2);
            // $taxRatePercent = number_format($itemRate * 100, 0);
            $refund_fee = $refundDetail['itemRefundFee'][$item['item_id']]['refund_fee'] ?? 0;
            $refund_num = $refundDetail['itemRefundFee'][$item['item_id']]['num'] ?? 0;
            app('log')->debug('[BaiwangService][formatBaiwangParams] itemrefund退货信息:refund_fee: ' . $refund_fee.',refund_num:'.$refund_num);
            $item_price_fee = bcdiv(($item['total_fee']  - $refund_fee), ( 100), 8);
            app('log')->debug('[BaiwangService][formatBaiwangParams] item_price_fee: ' . $item_price_fee. ':item_name:'.$item['item_name']);
            if($item_price_fee <= 0){
                continue;
            }
            $invoiceDetailList[] = [
                'goodsName'         => $item['item_name'],
                'goodsTaxRate'      => $itemRate,
                'goodsQuantity'     => $item['num'] - $refund_num,
                // 'goodsPrice'        => bcdiv($item['item_fee'] - $item['discount_fee'], ($item['num'] * 100), 8),
                'goodsTotalPriceTax' => $item_price_fee,//bcdiv(($item['item_fee'] - $item['discount_fee']) - $refund_fee, ( 100), 8),
                'goodsUnit'         => $item['unit'] ?? '件',
                'invoiceLineNature' => '0', // 正常行
                // 'priceTaxMark'      => '1', // 含税，或按实际业务传递
                // 可补充 goodsCode, goodsSpecification, goodsTotalPrice, goodsTotalTax 等
            ];
        }
        $settingService = new SettingService();
        $settingData = $settingService->getInvoiceSetting($orderInfo['company_id']); 

        // 需求2-3：如果订单里面的freight_fee>0，在商品行中增加运费明细行
        if ($settingData['freight_invoice'] == 2 && isset($orderInfo['freight_fee']) && $orderInfo['freight_fee'] > 0) {
            // 运费税率 从发票设置中来settingservice
            app('log')->debug('[BaiwangService][formatBaiwangParams] 发票设置: ' . json_encode($settingData));
            $freightTaxRate = $settingData['freight_tax_rate'] ?? '13';
            $freightTaxRate = number_format($freightTaxRate / 100, 2);
            
            $invoiceDetailList[] = [
                'goodsName'         => $settingData['freight_name']??'运费',
                'goodsTaxRate'      => $freightTaxRate,
                'goodsQuantity'     => 1,
                'goodsPrice'        => $orderInfo['freight_fee'] / 100, // 转换为元
                'goodsUnit'         => '次',
                'invoiceLineNature' => '0', // 正常行
            ];
        }

        // 需求2-1：根据invoice_type_code决定是专用发票还是普通发票
        // 01:增值税专用发票,02:增值税普通发票
        // invoiceTypeCode String 01-数电票(增值税专用发票),02-数电票(普通发票),
        $invoice_type_code = $ordersInvoice['invoice_type_code'] ?? '02'; // 默认普通发票
        $invoiceTypeCode = ($invoice_type_code == '01') ? '01' : '02'; // 01:数电票(增值税专用发票),02:数电票(普通发票)
        // invoiceType String 1 是 开票类型:1-蓝票,2-红票
        $invoiceType = 1;

        // 需求2-2：根据invoice_type决定开票类型
        // 从发票申请信息中获取开票参数
        // $invoiceParams =  [
        //     'invoice_type_code' => $ordersInvoice['invoice_type_code'] ?? '02', // 默认普通发票
        //     'invoice_type' => $ordersInvoice['invoice_type'] ?? 'enterprise', // 默认企业
        //     'company_title' => $ordersInvoice['company_title'] ?? '',
        //     'company_tax_number' => $ordersInvoice['company_tax_number'] ?? '',
        //     'email' => $ordersInvoice['email'] ?? '',
        //     'mobile' => $ordersInvoice['mobile'] ?? '',
        // ];
        
        // 根据开票类型设置购买方信息
        $buyerName = $ordersInvoice['company_title'];
        $buyerTaxNo = ($ordersInvoice['invoice_type'] == 'enterprise') ? ($ordersInvoice['company_tax_number'] ?? '') : '';
        $buyerAddress = ($ordersInvoice['invoice_type'] == 'enterprise') ? ($ordersInvoice['company_address'] ?? '') : '';
        $buyerTelephone = ($ordersInvoice['invoice_type'] == 'enterprise') ? ($ordersInvoice['company_telephone'] ?? '') : '';
        $buyerBankName = ($ordersInvoice['invoice_type'] == 'enterprise') ? ($ordersInvoice['bank_name'] ?? '') : '';
        $buyerBankNumber = ($ordersInvoice['invoice_type'] == 'enterprise') ? ($ordersInvoice['bank_account'] ?? '') : '';

        $data = [
            'invoiceTypeCode'   => $invoiceTypeCode,
            'orderNo'           => $orderInfo['order_id'].'-'.$ordersInvoice['id'].'-'.$ordersInvoice['try_times'],
            'orderDateTime'     => date('Y-m-d H:i:s', $orderInfo['create_time']),
            'invoiceType'       => $invoiceType, // 根据invoice_type_code设置
            'priceTaxMark'      => '1', // 含税，或按实际业务传递
            'buyerName'         => $buyerName, // 根据开票类型设置
            // 'buyerType'         => $buyerType, // 根据invoice_type设置
            'buyerTaxNo'        => $buyerTaxNo, // 企业税号
            'buyerAddress'      => $buyerAddress, // 企业地址
            'buyerTelephone'    => $buyerTelephone, // 企业电话
            'buyerBankName'     => $buyerBankName, // 开户银行
            'buyerBankNumber'   => $buyerBankNumber, // 开户账号
            'taxNo'             => $this->fapiao_config['taxNo'] ?? '',
            'drawer'            => $this->fapiao_config['drawer'] ?? '',
            'payee'             => $this->fapiao_config['payee'] ?? '',
            'checker'           => $this->fapiao_config['checker'] ?? '',
            'invoiceDetailList' => $invoiceDetailList,
            // 其他文档要求的参数可继续补充
        ];
        $invoiceTerminalCode = $this->fapiao_config['invoiceTerminalCode'] ?? '';
        app('log')->debug('[BaiwangService][formatBaiwangParams] invoiceTerminalCode: ' . $invoiceTerminalCode);
        if($invoiceTerminalCode){
            $data['invoiceTerminalCode'] = $invoiceTerminalCode;
        }
        return $data;
    }

    /**
     * 调试签名生成过程
     * @param array $params 协议参数
     * @param string $body 业务参数JSON字符串
     * @return array 调试信息
     */
    public function debugSign($params, $body = '')
    {
        $protocolKeys = ['appKey', 'format', 'method', 'timestamp', 'token', 'type', 'version', 'requestId'];
        $signParams = [];
        foreach ($protocolKeys as $k) {
            if (isset($params[$k])) {
                $signParams[$k] = $params[$k];
            }
        }
        ksort($signParams);

        $str = '';
        foreach ($signParams as $k => $v) {
            if ($v === '' || $v === null) continue;
            $str .= $k . $v;
        }

        $secret = $this->demoFapiaoConfig['appSecret'] ?? $this->appSecret;
        $signStr = $secret . $str . $body . $secret;
        $sign = strtoupper(md5($signStr));

        return [
            'protocolParams' => $signParams,
            'protocolString' => $str,
            'body' => $body,
            'secret' => $secret,
            'signString' => $signStr,
            'sign' => $sign,
            'config' => [
                'appKey' => $this->demoFapiaoConfig['appKey'] ?? '',
                'appSecret' => $this->demoFapiaoConfig['appSecret'] ?? '',
                'token' => $this->demoFapiaoConfig['token'] ?? '',
            ]
        ];
    }

    /**
     * 生成签名
     * @param array $params 参数数组
     * @param string $body 业务参数原始字符串
     * @return string 签名
     */
    private function makeSign($params, $body = '')
    {
        // 只取协议参数
        $protocolKeys = ['appKey', 'format', 'method', 'timestamp', 'token', 'type', 'version', 'requestId'];
        $signParams = [];
        foreach ($protocolKeys as $k) {
            if (isset($params[$k])) {
                $signParams[$k] = $params[$k];
            }
        }
        ksort($signParams);

        $str = '';
        foreach ($signParams as $k => $v) {
            if ($v === '' || $v === null) continue;
            $str .= $k . $v;
        }

        // 使用配置中的appSecret，而不是实例变量
        $secret = $this->demoFapiaoConfig['appSecret'] ?? $this->appSecret;
        if (empty($secret)) {
            app('log')->error('[BaiwangService][makeSign] appSecret为空，无法生成签名');
            throw new \Exception('appSecret配置为空，无法生成签名');
        }
        
        $signStr = $secret . $str . $body . $secret;
        app('log')->debug('[BaiwangService][makeSign] signStr: ' . $signStr);
        app('log')->debug('[BaiwangService][makeSign] secret: ' . $secret);
        app('log')->debug('[BaiwangService][makeSign] protocolStr: ' . $str);
        app('log')->debug('[BaiwangService][makeSign] body: ' . $body);
        return strtoupper(md5($signStr));
    }

    /**
     * 获取百旺云token，带redis缓存（access_token 6小时，refresh_token 30天）
     */
    public function getToken($config = [])
    {
        $appKey = $config['appKey'] ?? $this->demoFapiaoConfig['appKey'] ?? $this->appKey;
        $appSecret = $config['appSecret'] ?? $this->demoFapiaoConfig['appSecret'] ?? $this->appSecret;
        $tokenUrl = $this->tokenUrl ??  'https://sandbox-openapi.baiwang.com/router/rest';//'https://sandbox-openapi.baiwang.com/auth/token';
        app('log')->debug("[BaiwangService][getToken] tokenUrl: " . $tokenUrl);
        $redis = app('redis');
        $redisKey = 'baiwang:access_token:' . md5($appKey . $appSecret);
        $redisRefreshKey = 'baiwang:refresh_token:' . md5($appKey . $appSecret);
    
        // 1. 先查redis access_token
        $cache = $redis->get($redisKey);
        if ($cache) {
            app('log')->debug("[BaiwangService][getToken] redis命中: " . $redisKey . ", cache: " . $cache);
            $cacheArr = json_decode($cache, true);
            // log time diff    
            app('log')->debug("[BaiwangService][getToken] time diff: " . (time() - $cacheArr['expires_at']).'s'.', expires_at: '.$cacheArr['expires_at'].', now: '.time());
            if (isset($cacheArr['access_token']) && isset($cacheArr['expires_at']) && $cacheArr['expires_at'] > time()) {
                return $cacheArr['access_token'];
            }
        } else {
            app('log')->debug("[BaiwangService][getToken] redis未命中: " . $redisKey);
        }
    
        // 2. access_token 过期，尝试用 refresh_token 刷新
        $refreshToken = $redis->get($redisRefreshKey);
        if ($refreshToken) {
            try {
                app('log')->debug("[BaiwangService][getToken] access_token过期，尝试用refresh_token刷新: " . $refreshToken);
                return $this->refreshToken(['refresh_token' => $refreshToken, 'appKey' => $appKey, 'appSecret' => $appSecret]);
            } catch (\Exception $e) {
                app('log')->debug("[BaiwangService][getToken] refresh_token刷新失败: " . $e->getMessage());
                // 继续走全新获取
            }
        }
    
        // 3. refresh_token 也过期，走全新获取
        app('log')->debug("[BaiwangService][getToken] refresh_token也不可用，走全新获取token");

        // 组装 URL 查询参数
        $query = [
            'timestamp'     => (string)round(microtime(true) * 1000),
            'method'        => 'baiwang.oauth.token',
            'grant_type'    => 'password',
            'version'       => '6.0',
            'client_id'     => $appKey,
            'client_secret' => $appSecret,
        ];
        // 组装 body
        $body = [
            'password'      => $this->encryptPassword(),
            'username'      => $this->demoFapiaoConfig['username'] ?? env('BAIWANG_USERNAME', ''),
            'client_secret' => $appSecret,
            'orgAuthCode'   => $this->demoFapiaoConfig['orgAuthCode'] ?? env('BAIWANG_SALT', ''),
            // 'orgAuthCode'   => env('BAIWANG_ORG_AUTH_CODE', ''),
        ];
        app('log')->debug("[BaiwangService][getToken] 请求百旺云参数: query=" . json_encode($query) . ", body=" . json_encode($body));
        $client = new \GuzzleHttp\Client();

        // log tokenUrl
        app('log')->debug("[BaiwangService][getToken] 请求百旺云tokenUrl: " . $tokenUrl . '?' . http_build_query($query));
        $response = $client->post($tokenUrl . '?' . http_build_query($query), [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => json_encode($body),
        ]);
        $result = json_decode($response->getBody(), true);
        app('log')->debug("[BaiwangService][getToken] 百旺云响应: " . json_encode($result));
        if (!empty($result['success']) && !empty($result['response']['access_token'])) {
            $accessToken = $result['response']['access_token'];
            $refreshToken = $result['response']['refresh_token'] ?? '';
            // access_token 6小时
            $expiresAt = time() + 21600;
            $cacheArr = [
                'access_token' => $accessToken,
                'expires_at' => $expiresAt,
            ];
            $redis->setex($redisKey, 21600, json_encode($cacheArr));
            app('log')->debug("[BaiwangService][getToken] access_token已写入redis: " . $redisKey);
            // refresh_token 30天
            if ($refreshToken) {
                $redis->setex($redisRefreshKey, 2592000, $refreshToken);
                app('log')->debug("[BaiwangService][getToken] refresh_token已写入redis: " . $redisRefreshKey);
            }
            return $accessToken;
        } else {
            app('log')->debug("[BaiwangService][getToken] 获取access_token失败: " . json_encode($result));
            throw new \Exception('获取百旺access_token失败: ' . json_encode($result));
        }
    }

    /**
     * 加密密码
     */
    private function encryptPassword()
    {
        $plainPassword = $this->demoFapiaoConfig['password'] ?? env('BAIWANG_PASSWORD', '');
        $salt = $this->demoFapiaoConfig['orgAuthCode'] ?? env('BAIWANG_SALT', '');
        if(!$plainPassword){
            // throw new \Exception('百旺云密码未配置，请在.env文件中配置BAIWANG_PASSWORD');
            app('log')->debug("[BaiwangService][plainPassword]:百旺云密码未配置，请在.env文件中配置BAIWANG_PASSWORD " . ($plainPassword.':'.$salt));
            return false;
        }
        
        $md5 = md5($plainPassword . $salt); // 32位小写
        return sha1($md5); // SHA-1加密
    }
    /**
     * 刷新access_token（用refresh_token）
     * @param array $config 可选参数，支持传refresh_token、appKey、appSecret、tokenUrl等
     * @return string 新的access_token
     */
    public function refreshToken($config = [])
    {
        $appKey = $config['appKey'] ?? $this->appKey;
        $appSecret = $config['appSecret'] ?? $this->appSecret;
        $tokenUrl = $config['tokenUrl'] ?? $this->tokenUrl;
        $redis = app('redis');
        $redisRefreshKey = 'baiwang:refresh_token:' . md5($appKey . $appSecret);
        $refreshToken = $config['refresh_token'] ?? $redis->get($redisRefreshKey);
        if (!$refreshToken) {
            app('log')->debug("[BaiwangService][refreshToken] 无可用refresh_token: " . $redisRefreshKey);
            throw new \Exception('无可用refresh_token，请先获取token');
        }
        $params = [
            'method' => 'baiwang.oauth.token',
            'grant_type' => 'refresh_token',
            'refresh_token' => $refreshToken,
            'client_id' => $appKey,
            'timestamp' => round(microtime(true) * 1000),
            'version' => '6.0',
        ];
        app('log')->debug("[BaiwangService][refreshToken] 刷新token参数: " . json_encode($params));
        $client = new \GuzzleHttp\Client();
        $response = $client->post($tokenUrl, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($params)
        ]);
        $result = json_decode($response->getBody(), true);
        app('log')->debug("[BaiwangService][refreshToken] 百旺云响应: " . json_encode($result));
        if (!empty($result['success']) && !empty($result['response']['access_token'])) {
            $accessToken = $result['response']['access_token'];
            $refreshTokenNew = $result['response']['refresh_token'] ?? '';
            // access_token 6小时
            $expiresAt = time() + 21600;
            $cacheArr = [
                'access_token' => $accessToken,
                'expires_at' => $expiresAt,
            ];
            $redisKey = 'baiwang:access_token:' . md5($appKey . $appSecret);
            $redis->setex($redisKey, 21600, json_encode($cacheArr));
            app('log')->debug("[BaiwangService][refreshToken] access_token已写入redis: " . $redisKey);
            // refresh_token 30天
            if ($refreshTokenNew) {
                $redis->setex($redisRefreshKey, 2592000, $refreshTokenNew);
                app('log')->debug("[BaiwangService][refreshToken] refresh_token已写入redis: " . $redisRefreshKey);
            }
            return $accessToken;
        } else {
            app('log')->debug("[BaiwangService][refreshToken] 刷新access_token失败: " . json_encode($result));
            throw new \Exception('刷新百旺access_token失败: ' . json_encode($result));
        }
    }

    /**
     * 发送HTTP请求到百望云API
     * @param array $params 请求参数
     * @return array 响应结果
     * @throws \Exception
     */
    private function request($params)
    {
        try {
            // 只保留协议参数到 query
            $protocolKeys = ['method', 'appKey', 'version', 'format', 'timestamp', 'token', 'type', 'requestId'];
            $query = [];
            $bodyParams = [];
            foreach ($params as $k => $v) {
                if (in_array($k, $protocolKeys)) {
                    $query[$k] = $v;
                } else {
                    $bodyParams[$k] = $v;
                }
            }
            // orderDateTime 转字符串（如有）
            if (isset($bodyParams['orderDateTime']) && is_numeric($bodyParams['orderDateTime'])) {
                $bodyParams['orderDateTime'] = date('Y-m-d H:i:s', $bodyParams['orderDateTime']);
            }
            $body = json_encode($bodyParams, JSON_UNESCAPED_UNICODE);

            // 生成签名
            $paramsForSign = array_merge($query, []); // 只用协议参数
            $sign = $this->makeSign($paramsForSign, $body);
            $query['sign'] = $sign;

            // 日志
            app('log')->debug('[BaiwangService][request] URL: ' . $this->apiUrl . '?' . http_build_query($query));
            app('log')->debug('[BaiwangService][request] BODY: ' . $body);

            // 发起请求
            $client = new \GuzzleHttp\Client();
            $response = $client->post($this->apiUrl . '?' . http_build_query($query), [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $body,
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);

            $result = json_decode($response->getBody(), true);
            app('log')->debug('[BaiwangService][request] 响应结果: ' . json_encode($result));
            return $result;
        } catch (\GuzzleHttp\Exception\ConnectException $e) {
            app('log')->error("[BaiwangService][request] 连接异常: " . $e->getMessage());
            throw new \Exception('连接百望云API失败: ' . $e->getMessage());
        } catch (\GuzzleHttp\Exception\RequestException $e) {
            app('log')->error("[BaiwangService][request] 请求异常: " . $e->getMessage());
            throw new \Exception('请求百望云API失败: ' . $e->getMessage());
        } catch (\Exception $e) {
            app('log')->error("[BaiwangService][request] 未知异常: " . $e->getMessage());
            throw new \Exception('调用百望云API时发生错误: ' . $e->getMessage());
        }
    }

    /**
     * 组装百望云协议参数
     * @param string $method
     * @param array $config 可选，指定 appKey/token 来源
     * @return array
     */
    protected function buildProtocolParams($method, $config = [])
    {
        $cfg = $config ?: ($this->demoFapiaoConfig ?? []);
        return [
            'method'     => $method,
            'appKey'     => $cfg['appKey'] ?? '',
            'token'      => $cfg['token'] ?? '',
            'timestamp'  => (string)intval(microtime(true) * 1000),
            'version'    => '6.0',
            'format'     => 'json',
            'type'       => 'sync',
            'requestId'  => uniqid('bw_', true),
        ];
    }

    /**
     * 查询发票信息（支持流水号、开票单号）
     * @param array $params [
     *   'taxNo' => '', // 必填
     *   'serialNos' => [], // 可选，流水号列表
     *   'orderNos' => [],  // 可选，开票单号列表
     *   'detailMark' => '1' // 可选，1-需要明细，0-不需要
     * ]
     * @return array
     */
    public function queryInvoice($invoice )
    {
        // 确保配置已加载
        if (empty($this->demoFapiaoConfig)) {
            app('log')->error('[BaiwangService][queryInvoice] 配置未加载');
            throw new \Exception('百旺配置未加载，请先初始化服务');
        }
        
        // 传递发票对象的方式
        $tryTimes = $invoice['try_times'] ?? 0;
        $params = array(
            'taxNo' => $this->demoFapiaoConfig['taxNo'] ?? '',
            'serialNos' => $invoice['invoice_apply_bn'] ?? '',
            'orderNos' => [$invoice['order_id'] . '-' . $invoice['id'] . '-' . $tryTimes],
            'detailMark' => '1'
        );
        if( isset($invoice['type']) && $invoice['type'] == 'red'){
            $params = array(
                'taxNo' => $this->demoFapiaoConfig['taxNo'] ?? '',
                // 'serialNos' => $invoice['red_serial_no'] ?? '',
                'orderNos' => [$invoice['order_id'] . '-' . $invoice['id']. "-" . $tryTimes . "-" . $invoice['id']] ,
                'detailMark' => '1'
            );
        }
        
        app('log')->debug('[BaiwangService][queryInvoice] 查询发票参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE));
        $taxNo = $params['taxNo'] ?? $this->demoFapiaoConfig['taxNo'] ?? '';
        $serialNos = $params['serialNos'] ?? '';
        $orderNos = $params['orderNos'] ?? [];
        $detailMark = $params['detailMark'] ?? '1';

        // 确保token存在
        if (empty($this->demoFapiaoConfig['token'])) {
            app('log')->debug('[BaiwangService][queryInvoice] token为空，尝试重新获取');
            try {
                $this->demoFapiaoConfig['token'] = $this->getToken([
                    'appKey'    => $this->demoFapiaoConfig['appKey'],
                    'appSecret' => $this->demoFapiaoConfig['appSecret'],
                ]);
            } catch (\Exception $e) {
                app('log')->error('[BaiwangService][queryInvoice] 获取token失败: ' . $e->getMessage());
                throw new \Exception('获取百旺token失败: ' . $e->getMessage());
            }
        }

        // 1. 组装协议参数
        $queryParams = $this->buildProtocolParams('baiwang.s.outputinvoice.query', $this->demoFapiaoConfig);

        // 2. 组装body参数
        $bodyParams = [
            'taxNo' => $taxNo,
        ];
        
        // 处理serialNos，确保是数组格式
        if (!empty($serialNos)) {
            if (is_string($serialNos)) {
                $bodyParams['serialNos'] = [$serialNos];
            } else if (is_array($serialNos)) {
                $bodyParams['serialNos'] = $serialNos;
            }
        }
        
        // 处理orderNos，确保是数组格式
        if (!empty($orderNos)) {
            if (is_string($orderNos)) {
                $bodyParams['orderNos'] = [$orderNos];
            } else if (is_array($orderNos)) {
                $bodyParams['orderNos'] = $orderNos;
            }
        }
        
        if ($detailMark !== null) {
            $bodyParams['detailMark'] = $detailMark;
        }
        $body = json_encode($bodyParams, JSON_UNESCAPED_UNICODE);

        // 3. 生成签名
        $sign = $this->makeSign($queryParams, $body);
        $queryParams['sign'] = $sign;

        // 4. 发起请求
        $client = new \GuzzleHttp\Client();
        $url = $this->apiUrl . '?' . http_build_query($queryParams);
        app('log')->debug('[BaiwangService][queryInvoice] URL: ' . $url);
        app('log')->debug('[BaiwangService][queryInvoice] BODY: ' . $body);
        app('log')->debug('[BaiwangService][queryInvoice] 完整请求参数: ' . json_encode($queryParams));
        
        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $body,
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);
            $result = json_decode($response->getBody(), true);
            app('log')->debug('[BaiwangService][queryInvoice] 响应: ' . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            app('log')->error('[BaiwangService][queryInvoice] 请求失败: ' . $e->getMessage());
            throw new \Exception('查询发票失败: ' . $e->getMessage());
        }
    }

    /**
     * 查询发票列表
     * @param array $params 查询参数
     * @return array
     */
    public function queryInvoiceList($params = [])
    {
        app('log')->debug('[BaiwangService][queryInvoiceList] 查询发票列表参数: ' . json_encode($params));
        $requestParams = [
            'method' => 'baiwang.s.outputinvoice.list',
            'appKey' => $this->appKey,
            'token' => $this->token,
            'pageNo' => $params['pageNo'] ?? 1,
            'pageSize' => $params['pageSize'] ?? 20,
            'startDate' => $params['startDate'] ?? date('Y-m-d', strtotime('-7 days')),
            'endDate' => $params['endDate'] ?? date('Y-m-d'),
        ];
        app('log')->debug('[BaiwangService][queryInvoiceList] 请求参数: ' . json_encode($requestParams));
        return $this->request($requestParams);
    }

    /**
     * 作废发票
     * @param string $invoiceCode 发票代码
     * @param string $invoiceNo 发票号码
     * @param string $reason 作废原因
     * @return array
     */
    public function cancelInvoice($invoiceCode, $invoiceNo, $reason = '')
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.cancel',
            'appKey' => $this->appKey,
            'token' => $this->token,
            'invoiceCode' => $invoiceCode,
            'invoiceNo' => $invoiceNo,
            'reason' => $reason,
        ];

        return $this->request($params);
    }

    /**
     * 快捷冲红（红字发票）
     * @param array $params [
     *   'taxNo' => '', // 必填，销方税号
     *   'orderNo' => '', // 必填，开票单号
     *   'invoiceTerminalCode' => '' // 可选，开票终端号
     * 
     * ]
     * 
     *   ./artisan  test:baiwang red --params='{"company_id":1,"order_id":"4930601000310028","invoiceCode":"4930601000310028","serialNos":"25070118551097000363","orderNo":"4930601000310028-001","originalSerialNo":"25070118551097000363","originalOrderNo":"4930601000310028","originalInvoiceNo":"00002557","originalInvoiceCode":"999977292601"}'
     * 
     * storage/logs/lumen-2025-07-01.log:1261:[2025-07-01 21:08:42] production.DEBUG: [BaiwangService][makeSign] signStr: 223998c6-5b76-4724-b5c9-666ff4215b45appKey1002948formatjsonmethodbaiwang.s.outputinvoice.fastRedrequestIdbw_6863ddda706bc8.39096014timestamp1751375322460token27423b48-3b49-4f24-8994-140fe8ebe3e5typesyncversion6.0{"taxNo":"338888888888SMB","orderNo":"4930601000310028-001","originalOrderNo":"4930601000310028","originalSerialNo":"25070118551097000363","originalInvoiceCode":"999977292601","originalInvoiceNo":"00002557"}223998c6-5b76-4724-b5c9-666ff4215b45
     * @return array
     */
    public function redInvoice($params = [])
    {
        app('log')->debug('[BaiwangService][redInvoice] START:冲红参数: ' . json_encode($params));
        $taxNo = $params['taxNo'] ?? $this->demoFapiaoConfig['taxNo'] ?? '';
        
        // 先查询发票信息获取 try_times
        $orderInvoiceService = new \OrdersBundle\Services\OrderInvoiceService();
        $invoiceFilter = [
            'order_id' => $params['order_id'],
            'id' => $params['id']
        ];
        $invoice = $orderInvoiceService->getInfo($invoiceFilter);
        
        if (!$invoice) {
            throw new \Exception('发票信息不存在，无法进行冲红操作');
        }
        
        $tryTimes = $invoice['try_times'] ?? 0;
        $originalOrderNo = $params['order_id'] . '-' . $params['id'] . '-' . $tryTimes;
        $invoiceTerminalCode = $params['invoiceTerminalCode'] ?? '';

        // 1. 组装协议参数 baiwang.s.outputinvoice.fastRed 
        $queryParams = $this->buildProtocolParams('baiwang.s.outputinvoice.fastRed', $this->demoFapiaoConfig);

        // 2. 组装body参数
        $bodyParams = [
            'taxNo' => $taxNo,
            'orderNo' => $originalOrderNo . "-" . $params['id'] ,
            'originalOrderNo' => $originalOrderNo
        ];
        if (!empty($params['invoice_apply_bn'])) {
            $bodyParams['originalSerialNo'] = $params['invoice_apply_bn'] ;
        }
        // 任选一项必填
        if (!empty($params['originalOrderNo'])) {
            $bodyParams['originalOrderNo'] = $params['originalOrderNo'];
        }
        if (!empty($params['originalSerialNo'])) {
            $bodyParams['originalSerialNo'] = $params['originalSerialNo'] ;
        }

        // 税控票时必填
        if (!empty($params['originalInvoiceCode'])) {
            $bodyParams['originalInvoiceCode'] = $params['originalInvoiceCode'];
        }
        if (!empty($params['originalInvoiceNo'])) {
            $bodyParams['originalInvoiceNo'] = $params['originalInvoiceNo'];
        }
        
        if (!empty($invoiceTerminalCode)) {
            $bodyParams['invoiceTerminalCode'] = $invoiceTerminalCode;
        }
        app('log')->debug('[BaiwangService][redInvoice] query_content: ' . json_encode($params['query_content']));
        if(is_array($params['query_content'])){
            $digitInvoiceNo = $params['query_content']['digitInvoiceNo'] ?? '';
        }else{
            //json_decode
            $query_content = json_decode($params['query_content'], true);
            $digitInvoiceNo = $query_content['digitInvoiceNo'] ?? '';
        }

        if(!empty($digitInvoiceNo)){
            $bodyParams['originalDigitInvoiceNo'] = $digitInvoiceNo;
        }

        app('log')->debug('[BaiwangService][redInvoice] 请求参数: ' . json_encode($bodyParams));
        $body = json_encode($bodyParams, JSON_UNESCAPED_UNICODE);
        app('log')->debug('[BaiwangService][redInvoice] 请求参数: ' . $body);
        // 3. 生成签名
        $sign = $this->makeSign($queryParams, $body);
        $queryParams['sign'] = $sign;

        // 4. 发起请求
        $client = new \GuzzleHttp\Client();
        $url = $this->apiUrl . '?' . http_build_query($queryParams);
        app('log')->debug('[BaiwangService][redInvoice] URL: ' . $url);
        app('log')->debug('[BaiwangService][redInvoice] BODY: ' . $body);
        $response = $client->post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body'    => $body,
            'timeout' => 30,
            'connect_timeout' => 10,
        ]);
        $result = json_decode($response->getBody(), true);
        app('log')->debug('[BaiwangService][redInvoice] 响应: ' . json_encode($result));
        return $result;
    }

    /**
     * 查询发票余量
     * @return array
     */
    public function queryInvoiceQuota()
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.quota',
            'appKey' => $this->appKey,
            'token' => $this->token,
        ];

        return $this->request($params);
    }

    /**
     * 发送发票邮件
     * @param string $invoiceCode 发票代码
     * @param string $invoiceNo 发票号码
     * @param string $email 接收邮箱
     * @return array
     */
    public function sendInvoiceEmail($invoiceCode, $invoiceNo, $email)
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.email',
            'appKey' => $this->appKey,
            'token' => $this->token,
            'invoiceCode' => $invoiceCode,
            'invoiceNo' => $invoiceNo,
            'email' => $email,
        ];

        return $this->request($params);
    }

    /**
     * 发送发票短信
     * @param string $invoiceCode 发票代码
     * @param string $invoiceNo 发票号码
     * @param string $mobile 接收手机号
     * @return array
     */
    public function sendInvoiceSms($invoiceCode, $invoiceNo, $mobile)
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.sms',
            'appKey' => $this->appKey,
            'token' => $this->token,
            'invoiceCode' => $invoiceCode,
            'invoiceNo' => $invoiceNo,
            'mobile' => $mobile,
        ];

        return $this->request($params);
    }

    /**
     * 下载发票PDF
     * @param string $invoiceCode 发票代码
     * @param string $invoiceNo 发票号码
     * @return array
     */
    public function downloadInvoicePdf($invoiceCode, $invoiceNo)
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.download',
            'appKey' => $this->appKey,
            'token' => $this->token,
            'invoiceCode' => $invoiceCode,
            'invoiceNo' => $invoiceNo,
            'fileType' => 'PDF',
        ];

        return $this->request($params);
    }

    /**
     * 查询开票资质
     * @return array
     */
    public function queryQualification()
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.qualification',
            'appKey' => $this->appKey,
            'token' => $this->token,
        ];

        return $this->request($params);
    }

    /**
     * 查询开票设备状态
     * @return array
     */
    public function queryDeviceStatus()
    {
        $params = [
            'method' => 'baiwang.s.outputinvoice.device',
            'appKey' => $this->appKey,
            'token' => $this->token,
        ];

        return $this->request($params);
    }
    // [2025-07-24 15:03:11] production.DEBUG: [BaiwangService][redInvoice] 请求参数: {"taxNo":"338888888888SMB","orderNo":"4950694000100002-40-40","originalOrderNo":"4950694000100002-40","originalSerialNo":"25072212570209001994","originalDigitInvoiceNo":"20002946031400006182"}
    // [2025-07-24 15:03:11] production.DEBUG: [BaiwangService][redInvoice] URL: https://sandbox-openapi.baiwang.com/router/rest?method=baiwang.s.outputinvoice.fastRed&appKey=1002948&token=4384bfa8-2c17-456f-b5ef-6e04aeeefbb9&timestamp=1753340591468&version=6.0&format=json&type=sync&requestId=bw_6881daaf725ea6.11892602&sign=05AE88EF44B46044D23947CA0B4BE09D
    // [2025-07-24 15:03:11] production.DEBUG: [BaiwangService][redInvoice] BODY: {"taxNo":"338888888888SMB","orderNo":"4950694000100002-40-40","originalOrderNo":"4950694000100002-40","originalSerialNo":"25072212570209001994","originalDigitInvoiceNo":"20002946031400006182"}
    // [2025-07-24 15:03:12] production.DEBUG: [BaiwangService][redInvoice] 响应: {"method":"baiwang.s.outputinvoice.fastRed","requestId":"bw_6881daaf725ea6.11892602","response":{"redConfirmSerialNo":"1397957295973613568"},"success":true}
    // [2025-07-24 15:03:12] production.INFO: [redInvoice] 冲红结果:{"method":"baiwang.s.outputinvoice.fastRed","requestId":"bw_6881daaf725ea6.11892602","response":{"redConfirmSerialNo":"1397957295973613568"},"success":true}
    // [2025-07-24 15:03:12] production.INFO: [InvoiceQueryJob][handle] 冲红结果 {"method":"baiwang.s.outputinvoice.fastRed","requestId":"bw_6881daaf725ea6.11892602","response":{"redConfirmSerialNo":"1397957295973613568"},"success":true}
    /**
     * 红字确认单查询（分页查询）
     * @param array $params [
     *   'taxNo' => '', // 必填，销方税号
     *   'operatorType' => '', // 必填，操作类型：1-从局端下载，2-库里查询
     *   'buySelSelector' => '', // 必填，操作方身份：0-销方，1-购方
     *   'digitAccount' => '', // 可选，数电账号（仅下载操作时有效）
     *   'entryIdentity' => '', // 可选，录入方身份（仅查询操作时有效）：0-销方，1-购方
     *   'redConfirmSerialNo' => '', // 可选，红字确认单流水号
     *   'redConfirmNo' => '', // 可选，红字确认单编号
     *   'redConfirmUuid' => '', // 可选，红字确认单uuid
     *   'beginDate' => '', // 可选，录入时间起（格式：yyyy-MM-dd）
     *   'endDate' => '', // 可选，录入时间止（格式：yyyy-MM-dd）
     *   'originalInvoiceCode' => '', // 可选，原蓝票发票代码（仅查询操作时有效）
     *   'originalInvoiceNo' => '', // 可选，原蓝票发票号码（仅查询操作时有效）
     *   'originalDigitInvoiceNo' => '', // 可选，原蓝票数电号码（仅查询操作时有效）
     *   'redConfirmStatus' => '', // 可选，红字确认单状态（仅查询操作时有效）
     *   'callBackUrl' => '', // 可选，红票/红字确认单回传地址（仅下载操作时有效）
     *   'pageNo' => '' // 可选，当前页码（默认第1页）
     * ]
     * @return array
     */
    public function queryRedConfirm($params = [])
    {
        // 确保配置已加载
        if (empty($this->fapiao_config)) {
            app('log')->error('[BaiwangService][queryRedConfirm] 配置未加载');
            throw new \Exception('百旺配置未加载，请先初始化服务');
        }

        $params['entryIdentity'] = '1';
        // 参数验证
        $taxNo = $params['taxNo'] ?? $this->fapiao_config['taxNo'] ?? '';
        $operatorType = $params['operatorType'] ?? '1';
        $buySelSelector = $params['buySelSelector'] ?? '1';

        if (empty($taxNo)) {
            throw new \Exception('销方税号不能为空');
        }
        if (empty($operatorType)) {
            throw new \Exception('操作类型不能为空');
        }
        if (empty($buySelSelector)) {
            throw new \Exception('操作方身份不能为空');
        }

        // 验证操作类型
        if (!in_array($operatorType, ['1', '2'])) {
            throw new \Exception('操作类型只能是：1-从局端下载，2-库里查询');
        }

        // 验证操作方身份
        if (!in_array($buySelSelector, ['0', '1'])) {
            throw new \Exception('操作方身份只能是：0-销方，1-购方');
        }

        // 根据操作类型验证必填参数
        if ($operatorType == '1') {
            // 下载操作需要数电账号
            // if (empty($params['digitAccount'])) {
            //     throw new \Exception('下载操作时数电账号不能为空');
            // }
            // if (empty($params['callBackUrl'])) {
            //     throw new \Exception('下载操作时回传地址不能为空');
            // }
        } else {
            // // // 查询操作需要录入方身份
            // // if (empty($params['entryIdentity'])) {
            // //     throw new \Exception('查询操作时录入方身份不能为空');
            // // }
            // // 验证录入方身份
            // if (!in_array($params['entryIdentity'], ['0', '1'])) {
            //     throw new \Exception('录入方身份只能是：0-销方，1-购方');
            // }
        }

        app('log')->debug('[BaiwangService][queryRedConfirm] 红字确认查询参数: ' . json_encode($params, JSON_UNESCAPED_UNICODE));

        // 确保token存在
        if (empty($this->fapiao_config['token'])) {
            app('log')->debug('[BaiwangService][queryRedConfirm] token为空，尝试重新获取');
            try {
                $this->fapiao_config['token'] = $this->getToken([
                    'appKey'    => $this->fapiao_config['appKey'],
                    'appSecret' => $this->fapiao_config['appSecret'],
                ]);
            } catch (\Exception $e) {
                app('log')->error('[BaiwangService][queryRedConfirm] 获取token失败: ' . $e->getMessage());
                throw new \Exception('获取百旺token失败: ' . $e->getMessage());
            }
        }

        // 1. 组装协议参数
        $queryParams = $this->buildProtocolParams('baiwang.s.redconfirm.query', $this->fapiao_config);
        app('log')->debug(__FUNCTION__.':'.__LINE__.'[BaiwangService][queryRedConfirm] queryParams: ' . json_encode($queryParams));

        // 2. 组装body参数
        $bodyParams = [
            'taxNo' => $taxNo,
            'operatorType' => $operatorType,
            'buySelSelector' => $buySelSelector,
        ];
        // $invoiceTerminalCode = $this->fapiao_config['invoiceTerminalCode'] ?? '';
        $bodyParams['digitAccount'] = $this->fapiao_config['invoiceTerminalCode'] ?? '';

        app('log')->debug(__FUNCTION__.':'.__LINE__.'[BaiwangService][queryRedConfirm] bodyParams: ' . json_encode($bodyParams));
        // 添加可选参数
        $optionalParams = [
            'digitAccount', 'entryIdentity', 'redConfirmSerialNo', 'redConfirmNo', 
            'redConfirmUuid', 'beginDate', 'endDate', 'originalInvoiceCode', 
            'originalInvoiceNo', 'originalDigitInvoiceNo', 'redConfirmStatus', 
            'callBackUrl', 'pageNo','invoiceTerminalCode',
        ];

        foreach ($optionalParams as $param) {
            if (!empty($params[$param])) {
                $bodyParams[$param] = $params[$param];
            }
        }

        // 验证日期格式
        if (!empty($bodyParams['beginDate']) && !$this->validateDateFormat($bodyParams['beginDate'])) {
            throw new \Exception('开始日期格式错误，应为 yyyy-MM-dd 格式');
        }
        if (!empty($bodyParams['endDate']) && !$this->validateDateFormat($bodyParams['endDate'])) {
            throw new \Exception('结束日期格式错误，应为 yyyy-MM-dd 格式');
        }

        $body = json_encode($bodyParams, JSON_UNESCAPED_UNICODE);

        // 3. 生成签名
        $sign = $this->makeSign($queryParams, $body);
        $queryParams['sign'] = $sign;

        // 4. 发起请求
        $client = new \GuzzleHttp\Client();
        $url = $this->apiUrl . '?' . http_build_query($queryParams);
        app('log')->debug('[BaiwangService][queryRedConfirm] URL: ' . $url);
        app('log')->debug('[BaiwangService][queryRedConfirm] BODY: ' . $body);
        app('log')->debug('[BaiwangService][queryRedConfirm] 完整请求参数: ' . json_encode($queryParams));

        try {
            $response = $client->post($url, [
                'headers' => ['Content-Type' => 'application/json'],
                'body'    => $body,
                'timeout' => 30,
                'connect_timeout' => 10,
            ]);
            $result = json_decode($response->getBody(), true);
            app('log')->debug('[BaiwangService][queryRedConfirm] 响应: ' . json_encode($result));
            return $result;
        } catch (\Exception $e) {
            app('log')->error('[BaiwangService][queryRedConfirm] 请求失败: ' . $e->getMessage());
            throw new \Exception('红字确认查询失败: ' . $e->getMessage());
        }
    }

    /**
     * 验证日期格式是否为 yyyy-MM-dd
     * @param string $date
     * @return bool
     */
    private function validateDateFormat($date)
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && 
               strtotime($date) !== false;
    }

    public static function getDemoFapiaoConfig()
    {
        return self::$demoFapiaoConfig;
    }

    /**
     * setInvoiceSetting - 保存百旺发票配置到 Redis
     * @param int $companyId 企业ID
     * @param array $data 配置数据
     * @return array
     */
    public function setInvoiceSetting($companyId, $data)
    {
        // 1. 检查配置数据
        $checkResult = $this->checkInvoiceSetting($data);
        if (!$checkResult['success']) {
            return $checkResult;
        }

        // 2. 构建保存的配置数据
        $saveData = [
            'appKey'      => $data['appKey'] ?? '',
            'appSecret'   => $data['appSecret'] ?? '',
            'username'    => $data['username'] ?? '',
            'password'    => $data['password'] ?? '',
            'orgAuthCode' => $data['orgAuthCode'] ?? '',
            'taxNo'       => $data['taxNo'] ?? '',
            'terminal'    => $data['terminal'] ?? '',
            'mobile'      => $data['mobile'] ?? '',
            'drawer'      => $data['drawer'] ?? '',
            'payee'       => $data['payee'] ?? '',
            'checker'     => $data['checker'] ?? '',
            'tax_rate'    => $data['tax_rate'] ?? '0.03',
            'api_url'     => $data['api_url'] ?? env('BAIWANG_API_URL', 'https://sandbox-openapi.baiwang.com/router/rest'),
            'token_url'   => $data['token_url'] ?? env('BAIWANG_TOKEN_URL', 'https://sandbox-openapi.baiwang.com/auth/token'),
            'updated_at'  => time(),
        ];

        // 3. 保存到 Redis
        $key = 'BaiwangInvoiceSetting:' . $companyId;
        $redis = app('redis')->connection('companys');
        $saveResult = $redis->set($key, json_encode($saveData, JSON_UNESCAPED_UNICODE));

        if (!$saveResult) {
            return [
                'success' => false,
                'message' => '保存失败，Redis 连接异常'
            ];
        }

        // 4. 清除相关缓存
        $this->clearBaiwangCache($companyId);

        return [
            'success' => true,
            'message' => '百旺发票配置保存成功',
            'data' => $saveData
        ];
    }

    /**
     * 检查发票配置数据
     * @param array $data 配置数据
     * @return array
     */
    private function checkInvoiceSetting($data)
    {
        $errors = [];

        // 必填字段检查
        $requiredFields = [
            'appKey' => 'AppKey',
            'appSecret' => 'AppSecret', 
            'username' => '用户名',
            'orgAuthCode' => '用户盐值',
            'taxNo' => '机构税号'
        ];

        foreach ($requiredFields as $field => $label) {
            if (empty($data[$field])) {
                $errors[] = $label . '不能为空';
            }
        }

        // 格式检查
        if (!empty($data['appKey']) && !is_string($data['appKey'])) {
            $errors[] = 'AppKey 必须是字符串';
        }

        if (!empty($data['appSecret']) && !is_string($data['appSecret'])) {
            $errors[] = 'AppSecret 必须是字符串';
        }

        if (!empty($data['taxNo']) && !preg_match('/^[0-9A-Z]{15,20}$/', $data['taxNo'])) {
            $errors[] = '机构税号格式不正确，应为15-20位数字和大写字母';
        }

        if (!empty($data['mobile']) && !preg_match('/^1[3-9]\d{9}$/', $data['mobile'])) {
            $errors[] = '手机号格式不正确';
        }

        if (!empty($data['tax_rate'])) {
            $taxRate = floatval($data['tax_rate']);
            if ($taxRate < 0 || $taxRate > 1) {
                $errors[] = '税率必须在0-1之间';
            }
        }

        // URL 格式检查
        if (!empty($data['api_url']) && !filter_var($data['api_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'API地址格式不正确';
        }

        if (!empty($data['token_url']) && !filter_var($data['token_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Token地址格式不正确';
        }

        if (!empty($errors)) {
            return [
                'success' => false,
                'message' => '配置检查失败：' . implode('，', $errors)
            ];
        }

        return [
            'success' => true,
            'message' => '配置检查通过'
        ];
    }

    /**
     * 清除百旺相关缓存
     * @param int $companyId 企业ID
     */
    private function clearBaiwangCache($companyId)
    {
        $redis = app('redis');
        
        // 清除 token 缓存
        $appKey = env('BAIWANG_APP_KEY', '');
        $appSecret = env('BAIWANG_APP_SECRET', '');
        $redisKey = 'baiwang:access_token:' . md5($appKey . $appSecret);
        $redisRefreshKey = 'baiwang:refresh_token:' . md5($appKey . $appSecret);
        
        $redis->del($redisKey);
        $redis->del($redisRefreshKey);
        
        app('log')->debug("[BaiwangService][clearBaiwangCache] 清除缓存: {$redisKey}, {$redisRefreshKey}");
    }

    /**
     * getInvoiceSetting - 获取百旺发票配置
     * @param int $companyId 企业ID
     * @return array
     */
    public function getInvoiceSetting($companyId)
    {
        $key = 'BaiwangInvoiceSetting:' . $companyId;
        $redis = app('redis')->connection('companys');
        $data = $redis->get($key);
        
        if ($data) {
            $config = json_decode($data, true);
            return [
                'success' => true,
                'data' => $config
            ];
        }
        return [
            'success' => true,
            'message' => '配置不存在',
            'data' => []
        ];

    }
}  