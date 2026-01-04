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

namespace SystemLinkBundle\Services\ShopexErp;

use OrdersBundle\Entities\NormalOrders;
use SystemLinkBundle\Services\ThirdSettingService;

use GuzzleHttp\Client as Client;
use SystemLinkBundle\Services\OmsQueueLogService;

class Request
{
    public const V = 1;

    public $url = '';

    public $node_id = '';

    public $token = '';

    public $time_out = '10';
    public $companyId = 0;

    public function __construct($company_id)
    {
        $this->companyId = $company_id;
        $service = new ThirdSettingService();

        $erpSetting = $service->getShopexErpSetting($company_id);

        $this->url = config('common.oms_api_url');

        $this->token = config('common.oms_token');

        if (isset($erpSetting['node_id'])) {
            $this->node_id = $erpSetting['node_id'];
        }
    }

    public function _check($method, $data)
    {
        $checkMethods = [
            'ome.order.add',
            'ome.aftersale.logistics_update',
            'ome.aftersale.add',
            'ome.refund.add',
        ];

        if (in_array($method, $checkMethods)) {
            $orderId = $data['order_bn'] ?? '';
            $normalOrderRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
            $orderInfo = $normalOrderRepository->getInfo(['order_id' => $orderId]);
            if ($orderInfo['distributor_id'] ?? []) {
                return true;
            } else {
                return false;
            }

        } else {
            return false;
        }
    }

    public function call($method, $params)
    {
        //限制只同步自营店铺的订单、售后单、退款单
        $status = $this->_check($method, $params);
        if ($status) {
            $orderId = $params['order_bn'] ?? '';
            app('log')->debug('ome : method ===> '. $method .' 订单号 ===> ' . $orderId . '不是自营店铺订单  不同步');
            return [];
        }
        $t1 = microtime(true);
        try {
            $client = new Client();
            $system_params = [
                'app_id' => 'ecos.ome',
                'method' => $method,
                'date' => date('Y-m-d H:i:s', time()),
                'format' => 'json',
                'certi_id' => '',
                'v' => self::V,
                'node_id' => $this->node_id,
                'task' => '',
            ];
            $query_params = array_merge((array)$params, $system_params);
            $query_params['sign'] = self::gen_sign($query_params, $this->token);
            $postdata = [
                'verify' => false,
                'form_params' => $query_params
            ];
            $resData = $client->post($this->url, $postdata)->getBody();
            $response = $resData->getContents();
            $result = json_decode($response, 1);
            if (!$result) {
                $result = $response;
            }
            app('log')->debug('ome request===>method:'.$method.'===token:'.$this->token.'===url:'.$this->url.'=====>params:'.var_export($params, 1).'===>response:'.$response);
        } catch (\Exception $e) {
            $result = [ 'fail_msg' => $e->getMessage()];
            app('log')->debug('ome error:'.var_export($e->getMessage(), 1));
        }
        $t2 = microtime(true);
        $runtime = round($t2 - $t1, 3);
        $params['node_id'] = $this->node_id;
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
            'result' => is_array($result) ? json_encode($result, 256) : $result,
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
