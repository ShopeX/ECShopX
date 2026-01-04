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

declare(strict_types=1);
/**
 * This file is part of Shopex .
 *
 * @link     https://www.shopex.cn
 * @document https://club.shopex.cn
 * @contact  dev@shopex.cn
 */
namespace AdaPayBundle\Services\Adapay;

use AdaPayBundle\Services\AdaPay;

class AdaPayCommon extends AdaPay
{
    public function packageRequestUrl($requestParams = [])
    {
        $adapayFuncCode = $requestParams['adapay_func_code'];
        if (empty($adapayFuncCode)) {
            try {
                throw new \Exception('adapay_func_code不能为空');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        $adapayApiVersion = isset($requestParams['adapay_api_version']) ? $requestParams['adapay_api_version'] : 'v1';

        self::getGateWayUrl(self::$gateWayType);
        return self::$gateWayUrl . '/' . $adapayApiVersion . '/' . str_replace('.', '/', $adapayFuncCode);
    }

    /**
     * 通用请求接口 - POST - 多商户模式.
     * @param array $params 请求参数
     * @param string $merchantKey 如果传了则为多商户，否则为单商户
     */
    public function requestAdapay($params = [], $merchantKey = '')
    {
        if (! empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        $request_params = $params;
        $req_url = $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
    }

    public function requestAdapayFile($params = [], $merchantKey = '')
    {
        if (! empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        $request_params = $params;
        $req_url = $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, false, true);
    }

    /**
     * 通用请求接口 - POST - 多商户模式.
     * @param array $params
     * @param $merchantKey
     */
    public function requestAdapayUits($params = [], $merchantKey = '')
    {
        self::$gateWayType = 'page';

        if (! empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        $request_params = $params;
        $req_url = $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        //echo $req_url;

        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json = true);
    }

    /**
     * 通用查询接口 - GET.
     * @param array $params
     * @param string $merchantKey 传了则为多商户模式
     */
    public function queryAdapay($params = [], $merchantKey = '')
    {
        if (! empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }

        ksort($params);
        $request_params = $params;
        $req_url = $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . '?' . http_build_query($request_params), '', $header, false);
    }

    public function queryAdapayUits($params = [], $merchantKey = '')
    {
        self::$gateWayType = 'page';

        if (! empty($merchantKey)) {
            self::$rsaPrivateKey = $merchantKey;
            $this->ada_tools->rsaPrivateKey = $merchantKey;
        }
        ksort($params);
        $request_params = $params;
        $req_url = $this->packageRequestUrl($request_params);
        $request_params = $this->format_request_params($request_params);

        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . '?' . http_build_query($request_params), '', $header, false);
    }

    public function array_remove($arr, $key)
    {
        if (! array_key_exists($key, $arr)) {
            return $arr;
        }

        $keys = array_keys($arr);
        $index = array_search($key, $keys);

        if ($index !== false) {
            array_splice($arr, $index, 1);
        }

        return $arr;
    }

    public function format_request_params($request_params)
    {
        $request_params = $this->array_remove($request_params, 'adapay_func_code');
        $request_params = $this->array_remove($request_params, 'adapay_api_version');
        return $this->do_empty_data($request_params);
    }
}
