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

namespace ThirdPartyBundle\Services;

//shopex oms直连接口
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use SystemLinkBundle\Services\OmsQueueLogService;

class OmsApiService
{
    private $host;
    private $nodeId;
    private $appSecret;
    private $companyId;

    public function __construct($companyId = 0)
    {
        $this->companyId = $companyId;
        $omsSettingService = new OmsSettingService();
        $config = $omsSettingService->getSetting($this->companyId);
        $this->host = $config['api_host'] ?? '';
        $this->nodeId = $config['node_id'] ?? '';
        $this->appSecret = $config['app_secret'] ?? '';
//        $this->host = config('oms.api_host');
//        $this->nodeId = config('oms.node_id');
//        $this->appSecret = config('oms.app_secret', '666666'); // 默认值666666
    }

    //生成签名 (根据参考算法优化)
    private function generateSign(array $params): string
    {
        return strtoupper(
            md5(
                strtoupper(md5($this->assemble($params))) . $this->appSecret
            )
        );
    }

    //参数组装 (与参考算法保持一致)
    private function assemble($params): string
    {
        if (!is_array($params)) {
            return '';
        }

        ksort($params, SORT_STRING);
        $sign = '';

        foreach ($params as $key => $val) {
            if (is_null($val)) {
                continue;
            }

            if (is_bool($val)) {
                $val = $val ? 1 : 0;
            }

            $sign .= $key . (is_array($val) ? $this->assemble($val) : $val);
        }

        return $sign;
    }

    //调用OMS API
    public function callApi($method, $bizParams = [])
    {

        $t1 = microtime(true);
        // 公共参数
        $publicParams = [
            'method' => $method,
            'timestamp' => Carbon::now()->format('Y-m-d H:i:s.u'),
            'node_id' => $this->nodeId,
            'v' => '1.0',
        ];

        // 合并业务参数
        $params = array_merge($publicParams, $bizParams);

        // 生成签名
        $params['sign'] = $this->generateSign($params);

        // 初始化cURL
        $ch = curl_init();

        $apiUrl = $this->host . '/api';
        $postData = http_build_query($params);
        // app('log')->info("callApi_oms_url => " . $apiUrl);
        // app('log')->info("callApi_oms_params => " . $postData);

        // 设置cURL选项
        curl_setopt_array($ch, [
            CURLOPT_URL => $apiUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // 5秒超时
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
            ],
        ]);

        // 执行请求
        $response = curl_exec($ch);
        // app('log')->info("callApi_oms_response => " . $response);

        // 检查错误
        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            app('log')->info("callApi_oms_url => " . $apiUrl);
            app('log')->info("callApi_oms_params => " . $postData);
            throw new \RuntimeException("cURL error: " . $error_msg);
        }

        // 关闭cURL资源
        curl_close($ch);
        $result = json_decode($response, true);
        $t2 = microtime(true);
        $runtime = round($t2-$t1,3);
        $this->saveRequestLog($method,$runtime,$params,$result);
        // 返回JSON解码后的响应
        return $result;
    }

    private function saveRequestLog($api, $runtime, $params, $result)
    {
        $logParams = [
            'result' => $result,
            'runtime'=> $runtime,
            'company_id' => $this->companyId,
            'api_type' => 'request',
            'worker' => 'jushuitan.'.$api,
            'params' => $params,
        ];
        if (isset($result['code']) && strval($result['code']) === '0') {
            $logParams['status'] = 'success';
        } else {
            $logParams['status'] = 'fail';
        }
        $omsQueueLogService = new OmsQueueLogService();
        $logResult = $omsQueueLogService->create($logParams);
        return true;
    }
}
