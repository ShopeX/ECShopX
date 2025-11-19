<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services\MarketingCenter;

use CompanysBundle\Services\SettingService;
use GuzzleHttp\Client as Client;
use OpenapiBundle\Services\DeveloperService;
use CompanysBundle\Services\CompanysService;

class Request
{
    public function call($companyId, $method, $params)
    {
//        if (config('common.product_model') != 'standard') {
//            app('log')->debug('MarketingCenter:call-----非云店不做同步');
//            return [];
//        }
//        $settingService = new SettingService();
//        $result = $settingService->getNostoresSetting($companyId);
//        if ($result['nostores_status'] != 'true') {
//            app('log')->debug('MarketingCenter:call-----b2c不做同步');
//            return [];
//        }

        $developerService = new DeveloperService();

        if (! $info = $developerService->detail($companyId)) {
            app('log')->debug('MarketingCenter:call-----参数配置错误');
            return [];
        }
        $baseUri = rtrim($info['external_base_uri'], '/');
        if (!$baseUri) {
            return [];
        }

        $input['data'] = $params;
        $input['timestamp'] = date('Y-m-d H:i:s');
        $input['app_key'] = $info['external_app_key'];
        $input['version'] = '1.0';
        $input['method'] = $method;
        $input = $this->strval($input);
        $string = $this->assemble($input);
        $appSecret = $info['external_app_secret'];
        $stringMd5 = md5($appSecret . $string . $appSecret);
        $input['sign'] = strtoupper($stringMd5);
        $url = $baseUri . '/api/openapi';
        $client = new Client(['timeout' => 2]);
        try {
            app('log')->debug('MarketingCenter:input===>' . var_export($input, 1));
            $resData = $client->post($url, ['form_params' => $input])->getBody();
            app('log')->debug('MarketingCenter:result===>' . $resData);
        } catch (\Exception $e) {
            return [];
        }
        return json_decode($resData, 1);
    }

    private function assemble(array $params): string
    {
        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            if (is_null($val)) {
                continue;
            }
            if (is_bool($val)) {
                $val = ($val) ? 1 : 0;
            }
            $sign .= $key . (is_array($val) ? json_encode($val) : $val);
        }
        return $sign;
    }

    private function strval(array $params)
    {
        foreach ($params as $key => $val) {
            if (is_array($val)) {
                $params[$key] = $this->strval($val);
            } else {
                $params[$key] = strval($val);
            }
        }

        return $params;
    }
}
