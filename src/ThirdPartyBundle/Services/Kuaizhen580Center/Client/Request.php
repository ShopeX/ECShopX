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

namespace ThirdPartyBundle\Services\Kuaizhen580Center\Client;

use ThirdPartyBundle\Services\Kuaizhen580Center\Config\Config;
use ThirdPartyBundle\Services\Kuaizhen580Center\Config\Constant;

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
    public function __construct($companyId, $api)
    {
        $config = new Config($companyId);
        $this->config = $config;
        $this->api = $api;
    }

    /**
     * 请求调用api
     * @return Response
     */
    public function makeRequest()
    {
        $reqParams = $this->buildRequestParams();
        $resp = $this->getHttpRequestWithPost($reqParams);
        return $this->parseResponseData($resp);
    }

    /**
     * 构造请求数据
     */
    public function buildRequestParams()
    {
        $config = $this->getConfig();
        $api = $this->getApi();

        $requestParams = [];
        if ($api->getBusinessParams()) {
            $requestParams = $api->getBusinessParams();
        }
        $requestParams['clientId'] = $config->getClientId();
        $requestParams['timeStamp'] = time() * 1000;

        $requestParams['sign'] = $this->signature($requestParams);
        return $requestParams;
    }

    /**
     * 签名生成signature
     * @param $params
     * @return string
     */
    public function signature($params): string
    {
        $config = $this->getConfig();

        $params = $this->kSort($params);

        $str = '';
        foreach ($params as $key => $value) {
            if (is_array($value)) {
                if ($this->is_assoc($value)) {
                    $value = $this->getSignStringByArray($value);
                } else {
                    $value = $this->getSignStringByList($value);
                }
                $str .= $key . '=' . $value . '&';
            } else if ($value !== '') {
                $str .= $key . '=' . $value . '&';
            }
        }
        $str .= 'key=' . $config->getClientSecret();

        $sign = strtoupper(md5($str));

        return $sign;
    }

    public static function getSignStringByArray($map)
    {
        if ($map === null) {
            return null;
        }

        // 定义拼接对象
        $stringBuffer = '{';
        // 对数组做排序处理
        ksort($map);

        // 遍历数组
        foreach ($map as $key => $value) {
            // 如果值为空，或者空字符串，则不作处理
            if ($value === null || $value === '') {
                continue;
            }

            // 拼接键
            $stringBuffer .= $key . ':';

            // 根据值类型进行处理
            if (is_array($value)) {
                if (self::is_assoc($value)) {
                    // 如果是关联数组，递归调用
                    $stringBuffer .= self::getSignStringByArray($value) . ",";
                } else {
                    // 如果是索引数组，调用数组排序方法
                    $stringBuffer .= self::getSignStringByList($value) . ",";
                }
            } else {
                // 否则直接拼接值
                $stringBuffer .= $value . ',';
            }
        }

        // 移除最后一个逗号
        if (strlen($stringBuffer) > 1) {
            $stringBuffer = substr($stringBuffer, 0, -1);
        }
        $stringBuffer .= '}';

        return $stringBuffer;
    }

    /**
     * 判断数组是否是关联数组
     *
     * @param array $array
     * @return bool
     */
    private static function is_assoc($array) {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * 对列表排序并生成签名字符串
     *
     * @param array $list
     * @return string
     */
    public static function getSignStringByList($list) {
        // 如果列表为空，则直接返回
        if (empty($list)) {
            return '';
        } else if (count($list) == 0) {
            // 如果列表长度为0，则返回空长度字符串
            return '[]';
        }

        $stringBuffer = '[';
        foreach ($list as $value) {
            if (is_array($value)) {
                if (self::is_assoc($value)) {
                    $stringBuffer .= self::getSignStringByArray($value) . ',';
                } else {
                    $stringBuffer .= self::getSignStringByList($value) . ",";
                }
            } else {
                $stringBuffer .= $value . ',';
            }
        }

        // 移除最后一个逗号
        if (strlen($stringBuffer) > 1) {
            $stringBuffer = substr($stringBuffer, 0, -1);
        }
        $stringBuffer .= "]";

        return $stringBuffer;
    }

    public function kSort($params)
    {
        $params = $this->removeEmptyValues($params);
        ksort($params);
        foreach ($params as &$param) {
            if (is_array($param)) {
                $param = $this->kSort($param);
            }
        }
        unset($param);

        return $params;
    }

    public function removeEmptyValues($array)
    {
        $result = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && $value !== []) {
                // 递归调用以处理多维数组
                $value = $this->removeEmptyValues($value);
                // 只有当子数组不为空时，才保留这个键
                if (!empty($value)) {
                    $result[$key] = $value;
                }
            } else {
                // 只有当值不为空时，才保留这个键
                if ($value !== '') {
                    $result[$key] = $value;
                }
            }
        }
        return $result;
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

        $headers = [
            'Content-Type: application/json',
        ];
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->httpTimeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        $resp = curl_exec($curl);
        app('log')->info('580 url：' . $url);
        app('log')->info('580接口请求参数：' . json_encode($params, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        app('log')->info('580接口响应：' . $resp);
//        var_dump(curl_error($curl));die; //如果在执行curl的过程中出现异常，可以打开此开关查看异常内容。
        $info = curl_getinfo($curl);
        curl_close($curl);
        if (isset($info['http_code']) && $info['http_code'] == 200) {
            return $resp;
        }
        return '';
    }

    /**
     * 解析响应数据
     * @param $resp
     * 响应数据格式：{"err": 状态码,"errmsg": 错误信息, "data": 数据}
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
            $result->setStatus($resp['err'] == 0 ? Constant::SUCCESS : Constant::FAIL);
            $result->setMsg($resp['errmsg']);
            $result->setCode($resp['err']);
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
