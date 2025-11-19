<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services\ShopexErp\OpenApi;

use GuzzleHttp\Client as Client;
use SystemLinkBundle\Services\OmsQueueLogService;
use SystemLinkBundle\Services\ThirdSettingService;

class Request
{
    public const V = 1;

    public $url = '';

    public $flag = '';

    public $token = '';

    public $time_out = '10';

    public $companyId = 0;

    public function __construct($company_id)
    {
        $this->companyId = $company_id;
        $service = new ThirdSettingService();
        $erpSetting = $service->getShopexErpSetting($company_id);

        $this->url = config('common.oms_openapi_url');

        if (isset($erpSetting['openapi_flag'])) {
            $this->flag = $erpSetting['openapi_flag'];
        }

        if (isset($erpSetting['openapi_token'])) {
            $this->token = $erpSetting['openapi_token'];
        }
    }

    public function call($method, $params)
    {
        $t1 = microtime(true);
        try {
            $client = new Client();

            $system_params = [
                'flag' => $this->flag,
                'method' => $method,
                'type' => 'json',
                'charset' => 'utf-8',
                'ver' => self::V,
                'timestamp' => time(),
            ];

            $query_params = array_merge((array)$params, $system_params);
            $query_params['sign'] = self::gen_sign($query_params, $this->token);
            $postdata = [
                'verify' => false,
                'form_params' => $query_params
            ];
            $resData = $client->post($this->url, $postdata)->getBody();
            $response = $resData->getContents();
            if ($response) {
                $result['rsp'] = 'succ';
                $response = json_decode($response, 1);
                if (isset($response['response'])) {
                    $result['data'] = $response['response'] ?: [];
                    $result['data']['rsp'] = 'succ';
                } else {
                    $result['data'] = $response['error_response'] ?? [];
                    $result['data']['rsp'] = 'fail';
                }
            }
            app('log')->debug('ome openapi request===>method:'.$method.'===token:'.$this->token.'===url:'.$this->url.'=====>params:'.var_export($params, 1).'===>response:'.var_export($response, 1));
        } catch (\Exception $e) {
            $result = [ 'fail_msg' => $e->getMessage()];
            app('log')->debug('ome openapi error:'.var_export($e->getMessage(), 1));
        }
        $t2 = microtime(true);
        $runtime = round($t2 - $t1, 3);
        $params['url'] = $this->url;
        $params['time_out'] = $this->time_out;
        $this->saveRequestLog($method, $runtime, $params, $result);
        return $result;
    }

    public static function gen_sign($params, $token)
    {
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$token));
    }

    public static function assemble($params)
    {
        if (!is_array($params)) {
            return null;
        }
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            if (is_null($val)) {
                continue;
            }
            if (is_bool($val)) {
                $val = ($val) ? 1 : 0;
            }
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }//End Function

    private function saveRequestLog($api, $runtime, $params, $result)
    {
        $logParams = [
            'result' => $result,
            'runtime' => $runtime,
            'company_id' => $this->companyId,
            'api_type' => 'request',
            'worker' => $api,
            'params' => $params,
        ];
        if (isset($result['data']['rsp']) && $result['data']['rsp'] == 'succ') {
            $logParams['status'] = 'success';
        } else {
            $logParams['status'] = 'fail';
        }
        $omsQueueLogService = new OmsQueueLogService();
        $logResult = $omsQueueLogService->create($logParams);
        return true;
    }
}
