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

namespace BsPayBundle\Sdk\Core;
use BsPayBundle\Sdk\Config\MerConfig;

class BsPayRequestV2 {
    # 请求头
    private $httpHeaders;
    # 网络应答码
    private $httpStateCode;
    # 请求数据
    private $reqDatas;
    # 应答数据
    private $rspDatas;

    # 请求报错
    private $error = null;

    public function curlRequest(MerConfig $merConfig, $url, $postFields = null, $file = null, $headers=null, $is_json=false) {
        // $postFields['needSign'] 是否需要添加签名， $postFields['needVerfySign'] 是否需要验证签名
        $needSign = isset($postFields['needSign']) ? $postFields['needSign'] : true;
        $needVerfySign = isset($postFields['needVerfySign']) ? $postFields['needVerfySign'] : true;
        unset($postFields['needSign'],$postFields['needVerfySign']);
        BsPay::writeLog("curl方法参数:". json_encode(func_get_args(), JSON_UNESCAPED_UNICODE));
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if (is_array($postFields) && 0 < count($postFields)) {
            curl_setopt($ch, CURLOPT_POST, true);

            $body = $this->createBody($merConfig, $postFields, $file);
            if (!$needSign) {
                unset($body['sign']);
            }
            if ($is_json) {
                $json_data = json_encode($body);
                BsPay::writeLog("post-json请求参数:" . json_encode($body, JSON_UNESCAPED_UNICODE));
                array_push($headers, "Content-Length:" . strlen($json_data));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
            } else {
                BsPay::writeLog("post-form请求参数:" . json_encode($body, JSON_UNESCAPED_UNICODE));
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }

            $this->reqDatas = $body;
        }

        # 拼装请求头
        $this->httpHeaders = $this->createHeaders($headers);
        BsPay::writeLog("curl请求头:". json_encode($this->httpHeaders, JSON_UNESCAPED_UNICODE));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->httpHeaders);

        # 执行网络请求
        $resultString = curl_exec($ch);

        # 处理系统的报错
        if (curl_errno($ch)) {
            $this->error = curl_error($ch);
            BsPay::writeLog($this->error, "ERROR");

            # 这里报错直接就关闭本次会话return了
            curl_close($ch);
            return $this;
        }

        # http应答码
        $this->httpStateCode = curl_getinfo($ch,CURLINFO_HTTP_CODE);

        # 关闭本次会话
        curl_close($ch);

        $this->rspDatas = json_decode($resultString, true);
        BsPay::writeLog("curl返回参数:". $this->httpStateCode. " ". $resultString);
        $resp_sign = isset($this->rspDatas['sign']) ? $this->rspDatas['sign'] : '';

        # 不需要对应答数据验签的逻辑分支，斗拱存在一些不用验签的接口，例如：页面版的快捷支付、手机网页支等
        if (!$resp_sign || $this->httpStateCode == 401) {
            if ($needVerfySign) {
                $this->error = array(
                    'code' => 'RESP_SIGN_VERIFY_UNDO',
                    'msg' => '无法对应答数据进行验签',
                );
            }

            if (!$this->rspDatas) {
                # 无法解析出json格式的情况，就直接就把应答数据返回出去，针对返回数据为html页面的接口，例如：页面版的快捷支付等
                $this->rspDatas = $resultString;
            }
            return $this;
        }

        $resp_data = isset($this->rspDatas['data']) ? $this->rspDatas['data'] : '';

        // TODO response 应答错误 hardcode 待优化 --- Charles.huang 2021/09/09

        # 对返回数据验签失败的逻辑分支
        if (!BsPayTools::verifySign_sort($resp_sign, $resp_data, $merConfig->rsa_huifu_public_key)) {
            $this->error = array(
                'code' => 'RESP_SIGN_VERIFY_FAILED',
                'msg' => '接口结果返回签名验证失败',
            );
            return $this;
        }

        # 应答码异常情况
        if ($this->httpStateCode < 200 || $this->httpStateCode >= 400) {
            $this->error = array(
                'code' => 'HTTP_REQUEST_FAILED',
                'msg' =>  "请求失败: HTTP_STATE_CODE-".$this->httpStateCode,
            );
            return $this;
        }

        return $this;
    }

    private function createHeaders($header_data = array()){
        $headers = $header_data;
        if (empty($header_data)){
            $headers = array('Content-type: application/x-www-form-urlencoded');
        }
        
        array_push($headers, 'sdk_version:' . SDK_VERSION);
        array_push($headers, 'charset:UTF-8');
        return $headers;
    }

    private function createBody(MerConfig $merChantConfig, $post_data, $file = null){
        $body = array();
        $body['sys_id'] = $merChantConfig->sys_id;
        $body['product_id'] = $merChantConfig->product_id;
        ksort($post_data);  // 根据key排序
        $body['data'] = $post_data;
        #  执行签名
        $sign = BsPayTools::sha_with_rsa_sign(
            json_encode($post_data, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE),    // 数据里有中文和斜杠都不转码
            $merChantConfig->rsa_merch_private_key);
        $body['sign'] = $sign;
		if(!empty($file)){
			$body['file'] = $file;
			$body['data'] = json_encode($post_data, JSON_UNESCAPED_UNICODE);
		}
        return $body;
    }

    /**
     * 本次请求的http头信息
     */
    public function getHttpHeaders()
    {
        return $this->httpHeaders;
    }

    /**
     * 本次请求应答的 http状态码
     */
    public function getHttpStateCode()
    {
        return $this->httpStateCode;
    }

    /**
     * 本次请求的数据
     */
    public function getReqDatas()
    {
        return $this->reqDatas;
    }

    /**
     * 本次请求的应答数据
     */
    public function getRspDatas()
    {
        return $this->rspDatas;
    }

    /**
     * 获取请求执行失败信息
     */
    public function getErrorInfo()
    {
        return $this->error;
    }

    /**
     * 请求是否成功执行
     */
    public function isError()
    {
        return $this->error != null;
    }
}