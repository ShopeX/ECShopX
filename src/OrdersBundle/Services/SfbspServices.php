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

namespace OrdersBundle\Services;

use GuzzleHttp\Client as Client;
use Dingo\Api\Exception\ResourceException;
use GuzzleHttp\Exception\RequestException;

class SfbspServices
{
    private $url = 'https://sfapi-sbox.sf-express.com/std/service';

    public $companyId;

    // private $accesscode = 'BSPdevelop';
    // private $checkword ='j8DzkIFgmlomPt0aLuwU';

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
    }

    //顺丰物流跟踪设置保存
    public function setSfbspSetting($params)
    {
        return app('redis')->set($this->genReidsId(), json_encode($params));
    }
    //获取顺丰物流跟踪设置
    public function getSfbspSetting()
    {
        // Ver: 1e2364-fe10
        $data = app('redis')->get($this->genReidsId());
        if ($data) {
            $data = json_decode($data, true);
            return $data;
        } else {
            return [];
        }
    }
    /**
     * 获取redis存储的ID
     */
    private function genReidsId()
    {
        return 'SFBSPConfigSetting:' . sha1($this->companyId);
    }

    //顺丰BSP路由查询接口
    public function RouteService($tracking_number, $tracking_type = 1, $method_type = 1, $receiver_mobile = '')
    {
        $setting = $this->getSfbspSetting();

        if (!$setting || !$setting['accesscode'] || !$setting['checkword']) {
            throw new ResourceException('无效的顺丰BSP查询配置');
        }

        $msgData = json_encode([
            'language' => '0',
            'trackingType' => $tracking_type,
            'trackingNumber' => $tracking_number,
            'methodType' => $method_type,
            'checkPhoneNo' => substr($receiver_mobile, -4),
        ]);
        $timestamp = time();

        $postData = [
            'partnerID' => $setting['accesscode'],
            'requestID' => $this->__getRequestID(),
            'serviceCode' => 'EXP_RECE_SEARCH_ROUTES',
            'timestamp' => $timestamp,
            'msgDigest' => $this->sign($msgData, $timestamp, $setting['checkword']),
            'msgData' => $msgData,
        ];

        $result = $this->ApiPost($setting['url'], $postData);
        return $result;
    }

    //计算数字签名
    public static function sign($msgData, $timestamp, $checkword)
    {
        return base64_encode(md5((urlencode($msgData .$timestamp. $checkword)), TRUE));
    }

    //POST
    public function ApiPost($url, $postData)
    {
        $this->__apiLogStart($postData);
        $postData = http_build_query($postData);
        $options = array(
            'http' => array(
                'method' => 'POST',
                'header' => 'Content-type:application/x-www-form-urlencoded;charset=utf-8',
                'content' => $postData,
                'timeout' => 15 * 60 // 超时时间（单位:s）
            )
        );
        $context = stream_context_create($options);
        $result = file_get_contents($url ?: $this->url, false, $context);
        $this->__apiLogEnd($result);
        $result = json_decode($result, true);
        if ($result['apiResultCode'] != 'A1000') {
            throw new ResourceException($result['apiErrorMsg']);
        }
        return json_decode($result['apiResultData'], true);
    }

    //获取requestID
    private function __getRequestID() {
        $chars = md5(uniqid(mt_rand(), true));
        $requestID = substr ( $chars, 0, 8 ) . '-'
            . substr ( $chars, 8, 4 ) . '-'
            . substr ( $chars, 12, 4 ) . '-'
            . substr ( $chars, 16, 4 ) . '-'
            . substr ( $chars, 20, 12 );
        return $requestID ;
    }

    //API日志创建q
    private function __apiLogStart($params)
    {
        app('log')->debug("SF-BSP-requestData===>:".var_export($params, 1)."\n");
        return true;
    }

    private function __apiLogEnd($result)
    {
        app('log')->debug("SF-BSP-requestResult===>:".var_export($result, 1)."\n");
        return true;
    }
}
