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
use Exception;

class BsPay extends BsPayRequestV2
{
    // Ver: 1e2364-fe10
    # 生产环境接口基础地址 v2
    const BASE_API_URL_V2 = "https://api.huifu.com";
    # 集成环境接口基础地址 v2
    const BASE_API_TEST_URL_V2 = "https://spin-test.cloudpnr.com";

    # 调试模式
    public static $isDebug;
    # 生产模式
    public static $isProdMode;
    # 日志路径
    public static $logDir = "";

    # 商户配置信息
    private static $merConfigMap = array();

    public static function init($config_info, $is_object=false, $merchantKey = 'default'){

        if (empty($config_info)){
            try {
                throw new Exception('缺少SDK配置信息');
            } catch (Exception $e) {
                echo $e->getMessage();
            }
        }

        if ($is_object){
            $config_obj = $config_info;
        }else{
            if (!file_exists($config_info)){
                try {
                    throw new Exception('SDK配置文件不存在');
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
            $cfg_file_str = file_get_contents($config_info);
            $config_obj = json_decode($cfg_file_str,  true);

            // 处理 json 文件格式异常
            if(!$config_obj)
            {
                try {
                    throw new Exception('SDK配置文件格式异常');
                } catch (Exception $e) {
                    echo $e->getMessage();
                }
            }
        }

        // 是否调试模式
        self::$isDebug = defined("DEBUG") && DEBUG;
        // 日志路径
        self::$logDir = defined("DEBUG") ? LOG: dirname(__FILE__)."/log";
        // 是否生产模式
        self::$isProdMode = defined("PROD_MODE") && PROD_MODE;

        $merConfig = new MerConfig();
        $merConfig->product_id = isset($config_obj['product_id']) ? $config_obj['product_id'] : '';
        $merConfig->sys_id = isset($config_obj['sys_id']) ? $config_obj['sys_id'] : '';
        $merConfig->rsa_merch_private_key = isset($config_obj['rsa_merch_private_key']) ? $config_obj['rsa_merch_private_key'] : '';
        $merConfig->rsa_huifu_public_key = isset($config_obj['rsa_huifu_public_key']) ? $config_obj['rsa_huifu_public_key'] : '';
        self::addMerConfig($merchantKey, $merConfig);
    }

    protected function filterEmptyData($req_params){
        return array_filter($req_params, function($v){
            if (!empty($v) || $v == '0') {
                return true;
            }
            return false;
        });
    }

    public static function writeLog($message, $level = "INFO"){
        if (self::$isDebug){
            if (!is_dir(self::$logDir)){
                mkdir(self::$logDir, 0777, true);
            }

            $log_file = self::$logDir."/bspay_".date("Ymd").".log";
            $server_addr = "127.0.0.1";
            if (isset($_SERVER["REMOTE_ADDR"])){
                $server_addr = $_SERVER["REMOTE_ADDR"];
            }
            $message_format = "[". $level ."] [".gmdate("Y-m-d\TH:i:s\Z")."] ". $server_addr." ". $message. "\n";
            $fp = fopen($log_file, "a+");
            fwrite($fp, $message_format);
            fclose($fp);
        }
    }

    /**
     * 根据功能编码获取实际请求路径
     * @param $funcCode
     * @return string $req_url
     */
    public function getRequestUrl($funcCode) {
        // 判断请求环境确定接口域名地址
        $req_url = self::$isProdMode ? self::BASE_API_URL_V2 : self::BASE_API_TEST_URL_V2;

        // 如果末尾不是/结尾，则拼接/在最后
        if (!BsPayTools::endWith($req_url, "/")) {
            $req_url = $req_url . "/";
        }
        
        # 根据功能编码获取实际接口请求地址
        $actualUrl = str_replace(".", "/", $funcCode);
        # 拼接得到完整请求地址
        return $req_url . $actualUrl;
    }

    /**
     * @throws Exception
     */
    public function post($funcCode, $params, $file, $merchantKey){
        # 获取商户配置信息
        $merchantConfig = self::getConfig($merchantKey);
		if(empty($merchantConfig)){
			throw new Exception('系统公私密钥配置不存在');
		}
        # 根据功能编码获取实际请求路径
        $req_url = $this->getRequestUrl($funcCode);
        $request_params = $this->filterEmptyData($params);

        # 获取请求头
		if (empty($file)){
			$header = array('Content-Type:application/json');
            $is_json = true;
		}else{
			$header = array('Content-Type:multipart/form-data');
			$is_json = false;
		}

		// 实例化 http 请求执行
		$bs_request = new BsPayRequestV2();
        return $bs_request->curlRequest($merchantConfig, $req_url, $request_params, $file, $header, $is_json);
    }
    
    public function get($funcCode, $params, $merchantKey){
        ksort($params);
        # 获取商户配置信息
        $merchantConfig = $this->getConfig($merchantKey);
        # 根据功能编码获取实际请求路径
        $req_url = $this->getRequestUrl($funcCode);
        $request_params = $this->filterEmptyData($params);
        # 获取请求头
        $header =  array('Content-Type:text/html');

        // 实例化 http 请求执行
        $bs_request = new BsPayRequestV2();
        return $bs_request->curlRequest($merchantConfig, $req_url . "?" . http_build_query($request_params), "", $header, false);
    }
   
    public static function addMerConfig($merchantKey, MerConfig $merConfig) {
        self::$merConfigMap[$merchantKey] = $merConfig;
    }
    
    public static function getConfig($merChantKey = "default")
    {
        return isset(self::$merConfigMap[$merChantKey]) ? self::$merConfigMap[$merChantKey] : array();
    }

}