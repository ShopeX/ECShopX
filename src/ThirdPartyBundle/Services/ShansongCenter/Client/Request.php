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

namespace ThirdPartyBundle\Services\ShansongCenter\Client;

use ThirdPartyBundle\Services\ShansongCenter\Config\Config;
use ThirdPartyBundle\Services\ShansongCenter\Config\Constant;

class Request
{
    /**
     * http request timeout;
     */
    private $httpTimeout = 30;

    /**
     * 配置项
     */
    private $config;

    /**
     * 接口类
     */
    private $api;

    /**
     * 构造函数
     */
    public function __construct($company_id, $api)
    {
        $config = new Config($company_id);
        $this->config = $config;
        $this->api = $api;
    }

    /**
     * 请求调用api
     * @return bool
     */
    public function makeRequest()
    {
        $reqParams = $this->bulidRequestParams();
        app('log')->info('ShansongRequest start reqParams:'.var_export($reqParams, 1));
        $resp = $this->getHttpRequestWithPost($reqParams);
        app('log')->info('ShansongRequest resp api:'.$this->api->getUrl().',resp:'.var_export($resp, 1));
        return $this->parseResponseData($resp);
    }

    /**
     * 构造请求数据
     * data:业务参数，json字符串
     */
    public function bulidRequestParams()
    {
        $config = $this->getConfig();
        $api = $this->getApi();

        $requestParams = array();
        $requestParams['clientId'] = $config->getAppKey();
        $requestParams['shopId'] = $config->getShopId();
        $requestParams['timestamp'] = time();
        if ($api->getBusinessParams()) {
            $requestParams['data'] = $api->getBusinessParams();
        }
        $requestParams['sign'] = $this->_sign($requestParams);
        return $requestParams;
    }

    /**
     * 签名生成signature
     */
    public function _sign($params)
    {
        $config = $this->getConfig();

        $str = $config->getAppSecret();
        $str .= 'clientId'.$config->getAppKey();
        if (isset($params['data']) && $params['data']) {
            $str .= 'data'.$params['data'];
        }
        $str .= 'shopId'.$config->getShopId();
        $str .= 'timestamp'.$params['timestamp'];

        $sign = strtoupper(md5($str));

        return $sign;
    }


    /**
     * 发送请求,POST
     * @param $url 指定URL完整路径地址
     * @param $data 请求的数据
     */
    public function getHttpRequestWithPost($params)
    {
        $config = $this->getConfig();
        $api = $this->getApi();
        $url = $config->getHost() . $api->getUrl();
        app('log')->info('ShansongRequest start url:'.$url);

        // json
        $headers = array(
            'Content-Type: application/x-www-form-urlencoded',
        );
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->httpTimeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($curl);
        // var_dump( curl_error($curl) );//如果在执行curl的过程中出现异常，可以打开此开关查看异常内容。
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (isset($info['http_code']) && $info['http_code'] == 200) {
            return $resp;
        }
        return '';
    }

    /**
     * 解析响应数据
     * @param $resp 返回的数据
     * 响应数据格式：{"status": 状态码,"msg": 错误信息, "data": 数据}
     */
    public function parseResponseData($resp)
    {
        $result = new Response();
        if (empty($resp)) {
            $result->setStatus(Constant::FAIL);
            $result->setMsg(Constant::FAIL_MSG);
            $result->setCode(Constant::FAIL_CODE);
        } else {
            $resp = json_decode($resp, true);
            $result->setStatus($resp['status'] == 200 ? Constant::SUCCESS : Constant::FAIL);
            $result->setMsg($resp['msg']);
            $result->setCode($resp['status']);
            $result->setResult($resp['data'] ?? null);
        }
        return $result;
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getApi()
    {
        return $this->api;
    }
}
