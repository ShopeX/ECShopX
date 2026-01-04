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

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace WechatBundle\Services\Payment\BatchTransfer;

use EasyWeChat\Payment\Kernel\BaseClient;

class Client extends BaseClient
{
    /**
     * Send MerchantPay to balance.
     *
     * @param array $params
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function toBalance(array $params)
    {
        return $this->request('v3/transfer/batches', $params, 'post');
    }

    /**
     * Query MerchantPay to balance.
     *
     * @param string $batchId
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function queryBalanceOrder(string $batchId)
    {
        return $this->request('v3/transfer/batches/batch-id/'.$batchId.'?need_query_detail=false', [], 'get');
    }


    /**
     * Make a API request.
     *
     * @param string $endpoint
     * @param array  $params
     * @param string $method
     * @param array  $options
     * @param bool   $returnResponse
     *
     * @return \Psr\Http\Message\ResponseInterface|\EasyWeChat\Kernel\Support\Collection|array|object|string
     *
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidConfigException
     * @throws \EasyWeChat\Kernel\Exceptions\InvalidArgumentException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request(string $endpoint, array $params = [], $method = 'post', array $options = [], $returnResponse = false)
    {

        $token = $this->getToken($endpoint, $params, $method);

        $options = array_merge([
            'json' => $params,
            'headers' => [
                'Authorization' => 'WECHATPAY2-SHA256-RSA2048 '.$token,
                'Accept' => 'application/json',
            ],
            'http_errors' => false,
        ], $options);

        $this->pushMiddleware($this->logMiddleware(), 'log');

        $response = $this->performRequest($endpoint, $method, $options);

        return $returnResponse ? $response : $this->castResponseToType($response, $this->app->config->get('response_type'));
    }

    private function getToken(string $endpoint, array $params = [], $method = 'post')
    {   
        $timestamp   = time();//请求时间戳
        $nonce       = $timestamp.rand('10000','99999');//请求随机串
        $body        = $params ? \GuzzleHttp\json_encode($params, JSON_UNESCAPED_UNICODE) : '';//请求报文主体
        $stream_opts = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ];
 
        $apiclient_cert_path = $this->app['config']->get('cert_path');
        $apiclient_key_path  = $this->app['config']->get('key_path');
        
        $apiclient_cert_arr = openssl_x509_parse(file_get_contents($apiclient_cert_path, false, stream_context_create($stream_opts)));
        $serial_no          = $apiclient_cert_arr['serialNumberHex'];//证书序列号
        $mch_private_key    = file_get_contents($apiclient_key_path, false, stream_context_create($stream_opts));//密钥
        $merchant_id = $this->app['config']['mch_id'];//商户id
        $raw_str = strtoupper($method)."\n".(strpos($endpoint, '/') === 0 ? $endpoint : '/'.$endpoint)."\n".$timestamp."\n".$nonce."\n".$body."\n";
        openssl_sign($raw_str, $raw_sign, $mch_private_key, 'sha256WithRSAEncryption');
        $sign = base64_encode($raw_sign);//签名
        $schema = 'WECHATPAY2-SHA256-RSA2048';
        $token = sprintf('mchid="%s",nonce_str="%s",timestamp="%d",serial_no="%s",signature="%s"', $merchant_id, $nonce, $timestamp, $serial_no, $sign);//微信返回token
        return $token;
    }
}
