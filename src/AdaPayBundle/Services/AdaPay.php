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
namespace AdaPayBundle\Services;

class AdaPay
{
    public static $api_key = '';

    public static $rsaPrivateKeyFilePath = '';

    public static $rsaPrivateKey = '';

    # 不允许修改
    public static $rsaPublicKey = 'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCwN6xgd6Ad8v2hIIsQVnbt8a3JituR8o4Tc3B5WlcFR55bz4OMqrG/356Ur3cPbc2Fe8ArNd/0gZbC9q56Eb16JTkVNA/fye4SXznWxdyBPR7+guuJZHc/VW2fKH2lfZ2P3Tt0QkKZZoawYOGSMdIvO+WqK44updyax0ikK6JlNQIDAQAB';

    public static $header = ['Content-Type' => 'application/json'];

    public static $headerText = ['Content-Type' => 'text/html'];

    public static $headerEmpty = ['Content-Type' => 'multipart/form-data'];

    public static $gateWayUrl = 'https://%s.adapay.tech';

    public static $gateWayType = 'api';

    public static $mqttAddress = 'post-cn-0pp18zowf0m.mqtt.aliyuncs.com:1883';

    public static $mqttInstanceId = 'post-cn-0pp18zowf0m';

    public static $mqttGroupId = 'GID_CRHS_ASYN';

    public static $mqttAccessKey = 'LTAIOP5RkeiuXieW';

    public static $isDebug;

    public static $logDir = '';

    public $postCharset = 'utf-8';

    public $signType = 'RSA2';

    public $ada_request = '';

    public $ada_tools = '';

    public $statusCode = 200;

    public $result = [];

    public function __construct()
    {
        $this->ada_request = new AdaPayRequests();
        $this->ada_tools = new AdaPayTools();
        self::getGateWayUrl(self::$gateWayType);
        $this->__init_params();
    }

    protected function __init_params()
    {
        $this->ada_tools->rsaPrivateKey = self::$rsaPrivateKey;
        $this->ada_tools->rsaPublicKey = self::$rsaPublicKey;
    }

    public static function init($config_info, $prod_mode = 'live', $is_object = false)
    {
        if (empty($config_info)) {
            try {
                throw new \Exception('缺少SDK配置信息');
            } catch (\Exception $e) {
                echo $e->getMessage();
            }
        }

        if ($is_object) {
            $config_obj = $config_info;
        } else {
            if (! file_exists($config_info)) {
                try {
                    throw new \Exception('SDK配置文件不存在');
                } catch (\Exception $e) {
                    echo $e->getMessage();
                }
            }
            $cfg_file_str = file_get_contents($config_info);
            $config_obj = json_decode($cfg_file_str, true);
        }

        $sdk_version = config('adapay.sdk_version');
        self::$header['sdk_version'] = $sdk_version;
        self::$headerText['sdk_version'] = $sdk_version;
        self::$headerEmpty['sdk_version'] = $sdk_version;
        //        array_push(self::$header, "sdk_version:" . $sdk_version);
        //        array_push(self::$headerText, "sdk_version:" . $sdk_version);
        //        array_push(self::$headerEmpty, "sdk_version:" . $sdk_version);
        self::$isDebug = config('adapay.debug');
        self::$logDir = config('adapay.log_dir');
        $project_env = config('adapay.env');
        self::init_mqtt($project_env);

        if ($prod_mode == 'live') {
            self::$api_key = isset($config_obj['api_key_live']) ? $config_obj['api_key_live'] : '';
        }
        if ($prod_mode == 'test') {
            self::$api_key = isset($config_obj['api_key_test']) ? $config_obj['api_key_test'] : '';
        }

        if (isset($config_obj['rsa_public_key']) && $config_obj['rsa_public_key']) {
            self::$rsaPublicKey = $config_obj['rsa_public_key'];
        }

        if (isset($config_obj['rsa_private_key']) && $config_obj['rsa_private_key']) {
            self::$rsaPrivateKey = $config_obj['rsa_private_key'];
        }
    }

    public static function getGateWayUrl($type)
    {
        self::$gateWayUrl = config('adapay.gate_way_url') ? sprintf(config('adapay.gate_way_url'), $type) : 'https://api.adapay.tech';
    }

    public static function setApiKey($api_key)
    {
        self::$api_key = $api_key;
    }

    public static function setRsaPublicKey($pub_key)
    {
        self::$rsaPublicKey = $pub_key;
    }

    public static function writeLog($message, $level = 'INFO')
    {
        if (self::$isDebug) {
            if (! is_dir(self::$logDir)) {
                mkdir(self::$logDir, 0777, true);
            }

            $log_file = self::$logDir . '/adapay_' . date('Ymd') . '.log';
            $server_addr = '127.0.0.1';
            if (isset($_SERVER['REMOTE_ADDR'])) {
                $server_addr = $_SERVER['REMOTE_ADDR'];
            }
            $message_format = '[' . $level . '] [' . gmdate('Y-m-d\\TH:i:s\\Z') . '] ' . $server_addr . ' ' . $message . "\n";
            $fp = fopen($log_file, 'a+');
            fwrite($fp, $message_format);
            fclose($fp);
        }
    }

    public static function init_mqtt($project_env)
    {
        if (isset($project_env) && $project_env == 'test') {
            self::$mqttAddress = 'post-cn-459180sgc02.mqtt.aliyuncs.com:1883';
            self::$mqttGroupId = 'GID_CRHS_ASYN';
            self::$mqttInstanceId = 'post-cn-459180sgc02';
            self::$mqttAccessKey = 'LTAILQZEm73RcxhY';
        }
    }

    public function isError()
    {
        if (empty($this->result)) {
            return true;
        }
        $this->statusCode = $this->result[0];
        $resp_str = $this->result[1];
        $resp_arr = json_decode($resp_str, true);
        $resp_data = isset($resp_arr['data']) ? $resp_arr['data'] : '';
        $resp_sign = isset($resp_arr['signature']) ? $resp_arr['signature'] : '';
        $resp_data_decode = json_decode($resp_data, true);
        if ($resp_sign && $this->statusCode != 401) {
            if ($this->ada_tools->verifySign($resp_sign, $resp_data)) {
                if ($this->statusCode != 200) {
                    $this->result = $resp_data_decode;
                    return true;
                }
                $this->result = $resp_data_decode;
                return false;
            }
            $this->result = [
                'failure_code' => 'resp_sign_verify_failed',
                'failure_msg' => '接口结果返回签名验证失败',
                'status' => 'failed',
            ];
            return true;
        }
        $this->result = $resp_data_decode;
        return true;
    }

    protected function get_request_header($req_url, $post_data, $header = [])
    {
        $this->__init_params();
        $header['Authorization'] = self::$api_key;
        $header['Signature'] = $this->ada_tools->generateSignature($req_url, $post_data);
        return $header;
    }

    protected function handleResult()
    {
        // KEY: U2hvcEV4
        $json_result_data = json_decode($this->result[1], true);
        if (isset($json_result_data['data'])) {
            return json_decode($json_result_data['data'], true);
        }
        return [];
    }

    protected function do_empty_data($req_params)
    {
        return array_filter($req_params, function ($v) {
            if (! empty($v) || $v == '0') {
                return true;
            }
            return false;
        });
    }
}
