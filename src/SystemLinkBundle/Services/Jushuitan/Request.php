<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services\Jushuitan;

use Dingo\Api\Exception\ResourceException;

use SystemLinkBundle\Services\JushuitanSettingService;

use GuzzleHttp\Client as Client;
use SystemLinkBundle\Services\OmsQueueLogService;

class Request
{

    public $is_online = false;
    public $url = '';
    public $appKey = '';
    public $appSecret = '';
    public $accessToken = '';
    public $timeOut = 10;
    public $companyId = 0;

    public function __construct($companyId)
    {
        $this->companyId = $companyId;
        $service = new JushuitanSettingService();
        $setting = $service->getJushuitanSetting($companyId);

        $this->is_online = config('jushuitan.is_online');
        $this->url = config('jushuitan.api_base_url');
        $this->appKey = config('jushuitan.app_key');
        $this->appSecret = config('jushuitan.app_secret');
        if (isset($setting['access_token']) && $setting['access_token']){
            $this->accessToken = $setting['access_token'];
        }
    }

    public function getOauthUrl()
    {
        $system_params = [
            'app_key' => $this->appKey,
            'timestamp' => time(),
            'state' => $this->companyId,
            'charset' => 'utf-8',
        ];
        $system_params['sign'] = self::gen_sign($system_params, $this->appSecret);
        $query = http_build_query($system_params);
        $base_url = config('jushuitan.oauth_base_url');
        $url = "{$base_url}?{$query}";
        return $url;
    }

    public function call($method, $params)
    {
        $t1 = microtime(true);
        try {
            
            $client = new Client();
            $params = self::filter($params);
            if ($method == 'oauth_token') {
                if (!$this->is_online) {
                    $method = 'oauth_token_isv';
                }
                $system_params = [
                    'app_key' => $this->appKey,
                    'timestamp' => time(),
                    'grant_type' => 'authorization_code',
                    'charset' => 'utf-8',
                ];
                $query_params = array_merge((array)$params,$system_params);
            } else {
               $system_params = [
                   'app_key' => $this->appKey,
                   'access_token' => $this->accessToken,
                   'timestamp' => time(),
                   'charset' => 'utf-8',
                   'version' => '2',
               ]; 
               $newParams = ['biz' => json_encode($params)];
               $query_params = array_merge((array)$newParams,$system_params);
            }
            $path = config('jushuitan.methods.'.$method);
            $query_params['sign'] = self::gen_sign($query_params, $this->appSecret);
            $postdata = [
                'verify' => false,
                'form_params' => $query_params
            ];
            $resData = $client->post($this->url.$path, $postdata)->getBody();
            $response = $resData->getContents();
            $result = json_decode($response, 1);
            app('log')->debug('jushuitan request===>method:'.$method.'===appSecret:'.$this->appSecret.'===url:'.$this->url.$path.'=====>params:'.var_export($params,1).'===>response:'.$response);
        } catch (\Exception $e ) {
            $result = [ 'fail_msg' => $e->getMessage()];
            app('log')->debug('jushuitan error:'.var_export($e->getMessage(),1));
        }

        $t2 = microtime(true);
        $runtime = round($t2-$t1,3);
        $params['url'] = $this->url;
        $params['time_out'] = $this->timeOut;
        $this->saveRequestLog($method, $runtime, $params, $result);
        return $result;
    }

    static function filter($params) {
        if(!is_array($params)) return $params;

        foreach($params AS $key=>$val){
            if (is_array($val)) {
                $params[$key] = $val = self::filter($val);
            }

            if (is_null($val) || (is_array($val) && empty($val))) {
                unset($params[$key]);
            }
        }

        return $params;
    }

    static function gen_sign($params,$appSecret){
        return bin2hex(md5($appSecret.self::assemble($params), true));
        // return strtoupper(md5(self::assemble($params).$token));
    }

    static function assemble($params)
    {
        if(!is_array($params)) return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }//End Function

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

    public function getOauthSign($params)
    {
        return self::gen_sign($params, $this->appSecret);
    }
}
