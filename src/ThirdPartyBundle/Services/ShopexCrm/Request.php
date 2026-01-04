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

namespace ThirdPartyBundle\Services\ShopexCrm;

use GuzzleHttp\Client as Client;

class Request
{
    public function sendRequest($apiName, $data)
    {
        $crm_url = config('crm.crm_url');
        $crm_app_key = config('crm.crm_app_key');
        $crm_app_secret = config('crm.crm_app_secret');
        if (empty($crm_url) || empty($crm_app_key) || empty($crm_app_secret)) {
            app('log')->info('crm参数配置错误');
            return false;
        }
        $url = $crm_url . $apiName;
        $params['method'] = 'MD5';
        $params['app_key'] = $crm_app_key;
        $params['timestamp'] = time();
        $params['version'] = '1.0';
        $params['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        $params['sign'] = $this->gen_sign($params, $crm_app_secret);
        $result = $this->request($url, $params);
        app('log')->info("-----crm请求参数-----" . var_export($params, 1));
        app('log')->info("-----crm请求-----'$url'-----" . json_encode($params, JSON_UNESCAPED_UNICODE));
        app('log')->info("-----crm返回结果-----'$url'-----" . var_export($result, 1));
        return $result;
    }


    public function gen_sign($params, $secret)
    {
        // FIXME: check performance
        return strtoupper(md5($secret . $this->assemble($params) . $secret));
    }

    public function assemble($params)
    {
        if (!is_array($params)) {
            return null;
        }
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key . (is_array($val) ? $this->assemble($val) : $val);
        }
        return $sign;
    }

    public function request($url, $params)
    {
        $client = new Client();
        $resData = $client->post($url, [
            'json' => $params
        ])->getBody();
        $response = $resData->getContents();
        return $response;
    }

//    function request($url, $params){
//        $curl = curl_init();
//
//        curl_setopt_array($curl, array(
//            CURLOPT_URL => $url,
//            CURLOPT_RETURNTRANSFER => true,
//            CURLOPT_ENCODING => "",
//            CURLOPT_MAXREDIRS => 10,
//            CURLOPT_TIMEOUT => 10,
//            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
//            CURLOPT_CUSTOMREQUEST => "POST",
//            CURLOPT_POSTFIELDS => json_encode($params),
//            CURLOPT_HTTPHEADER => array(
//                "content-type: application/json"
//            ),
//        ));
//        $response = curl_exec($curl);
//        $err = curl_error($curl);
//        curl_close($curl);
//        if ($err) {
//            app('log')->info('-----crm返回错误-----'.var_export($err,1));
//        } else {
//            return $response;
//        }
//    }
}
