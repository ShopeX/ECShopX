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

namespace ThirdPartyBundle\Services\SaasErpCentre;

use OrdersBundle\Entities\NormalOrders;
use SystemLinkBundle\Services\OmsQueueLogService;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;

use GuzzleHttp\Client as Client;
use ThirdPartyBundle\Services\SaasErpLogService;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Ego\CompanysActivationEgo;

class Request
{
    public const V = '1.0';

    public $url = '';
    public $erp_node_id = '';
    public $token = '';
    public $app_id;
    public $time_out = '10';
    public $companyId = 0;
    public $certSetting;

    public function __construct($company_id)
    {
        $this->companyId = $company_id;
        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($company_id);
        $certService = new CertService(false, $company_id, $shopexUid);
        $this->erp_node_id = $certService->getErpBindNode();
        $this->certSetting = $certService->getCertSetting();
        $this->url = config('common.matrix_api_url');
        $this->token = $this->certSetting['token'];
        $this->app_id = config('common.verify_app_id');
    }

    public function _check($method, $data)
    {
        // Powered by ShopEx EcShopX
        $company = (new CompanysActivationEgo())->check($this->companyId);
        if ($company['product_model'] != 'platform') {
            return false;
        }

        $checkMethods = [
            'store.trade.add',//订单创建
            'store.trade.update',//订单更新
            'store.trade.refund.add',//创建退款单
            'store.trade.aftersale.add',//售后单添加
            'store.trade.aftersale.status.update',//售后单更新
            'store.trade.aftersale.logistics.update',//回寄物流信息更新
            ];

        if (in_array($method, $checkMethods)) {
            $orderId = $data['tid'] ?? '';
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

    public function call($method, $data)
    {
        try {
            //限制只同步自营店铺的订单、售后单、退款单
            $status = $this->_check($method, $data);
            if ($status) {
                $orderId = $data['tid'] ?? '';
                app('log')->debug('saaserp : method ===> '. $method .' 订单号 ===> ' . $orderId . '不是自营店铺订单  不同步');
                return [];
            }

            $client = new Client();
            $system_params = [
                'method' => $method,
                'certi_id' => $this->certSetting['cert_id'],
                'from_node_id' => $this->certSetting['node_id'],
                'from_api_v' => '1.0',
                'to_node_id' => $this->erp_node_id,
                'timestamp' => date('Y-m-d H:i:s', time()),
                'format' => 'json',
                'date' => date('Y-m-d H:i:s', time()),
                'refresh_time' => date('Y-m-d H:i:s', time()),
                'v' => '1.0',
                'app_id' => $this->app_id,
                'node_type' => 'ecos.ome',
                'task' => self::create_task_id(),
                // 'callback_url' => config('common.certi_base_url').$this->companyId."/",
            ];
            $system_params['_id'] = "rel_".$system_params['from_node_id']."_".$method."_".$system_params['to_node_id'];
            $query_params = array_merge((array)$data, $system_params);

            $query_params['sign'] = self::get_matrix_sign($query_params, $this->token);

            $t1 = microtime(true);
            $resData = $client->post($this->url, [
                'verify' => false,
                'timeout' => 3,//接口超时设置，秒
                'form_params' => $query_params
            ])->getBody();

            $response = $resData->getContents();
            $t2 = microtime(true);

            $result = json_decode($response, 1);
            if (isset($result['data']) and $result['data']) {
                $result['data'] = json_decode($result['data'], 1);
            }

            app('log')->debug("matrix url ".$this->url);
            app('log')->debug("saaserp params ".var_export($query_params, true));
            app('log')->debug("saaserp res ".var_export($result, true));
            
            $logParams = [
                'result' => $result,
                'runtime' => round($t2 - $t1, 3),
                'company_id' => $this->companyId,
                'api_type' => 'request',
                'worker' => $method,
                'params' => $query_params,
                'msg_id' => $result['msg_id'],
            ];
            if (isset($result['data']['rsp']) && $result['data']['rsp'] == 'succ') {
                $logParams['status'] = 'success';
            } else {
                $logParams['status'] = 'fail';
            }

            $this->saveRequestLog($method, $logParams['runtime'], $query_params, $result);

            //$saasErpLogService = new SaasErpLogService();
            //$logResult = $saasErpLogService->create($logParams);

            //app('log')->debug("saaserp certSetting ".json_encode($this->certSetting));
            //app('log')->debug("saaserp ===>method:".$method."===token:".$this->token."===url:".$this->url."\n=====>params:".json_encode($query_params)."\n===>response:".$response);

            return $result;
        } catch (\Exception $e) {
            $errorMsg = 'Error on line '.$e->getLine().' in '.$e->getFile().': <b>'.$e->getMessage()."\n\n";
            app('log')->debug('saaserp error:'.$errorMsg);
            $response = [];
        }

        return $response;
    }

    //签名
    public static function get_matrix_sign($params, $token)
    {
        //如果参数是数组的话将参数json
        foreach ($params as $k => $v) {
            if (is_array($v)) {
                $params[$k] = json_encode($v);
            }
        }
        return self::gen_sign($params, $token);
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

    public static function create_task_id()
    {
        $i = rand(0, 9999);
        if (9999 == $i) {
            $i = 0;
        }
        $task_id = time().str_pad($i, 4, '0', STR_PAD_LEFT);
        return $task_id;
    }

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
