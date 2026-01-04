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

namespace CompanysBundle\Services;

class PrismIshopexService
{
    protected $prismClient;
    /**
     * PrismIshopexService 构造函数.
     */
    public function __construct()
    {
        $this->prismClient = new \PrismClient(
            config('common.ishopex_prism_url'), //$url
            config('common.ishopex_prism_key'), //$key
            config('common.ishopex_prism_secret') //$secret
        );
    }

    public function onlineOpenCallback($params)
    {
        // $prismClient = new \PrismClient(
        //     'http://openapi.ishopex.cn/api', //$url
        //     'vjvwjkiz', //$key
        //     'aedmxsbrs6sm74pyttwx' //$secret
        // );
        $data = [
            'issue_id' => $params['issue_id'],
            'issue_status' => 'success',
            'url' => $params['url']
        ];
        app('log')->debug('Prism callback data:'.json_encode($data));
        $result = $this->prismClient->post('/online/callback', $data, null, ['connect_timeout' => 30]);
        $result = json_decode($result, 1);

        app('log')->debug('Prism callback:'.json_encode($result));
        return $result;
    }

    /**
    * 云店线索创建
    *
    */
    public function opaYdleadsCreate($params)
    {
        app('log')->info('Prism opaYdleadsCreate params===>'.var_export($params, 1));
        $result = $this->prismClient->post('/opa/ydleads/create', [
            'shopexid' => $params['shopexid'],
            'entid' => $params['entid'],
            'goods_name' => $params['goods_name'],
            'call_name' => $params['call_name'],
            'sex' => $params['sex'],
            'mobile' => $params['mobile'],
        ], null, ['connect_timeout' => 30]);
        app('log')->debug('Prism opaYdleadsCreate result:'.$result);
        return json_decode($result, 1);
    }

    /**
     * 获取Nirvana用户中心shopexid.
     * @param $mobile
     * @return mixed
     */
    public function getYunqiaccountInfo($mobile)
    {
        $result = $this->prismClient->post('/yunqiaccount/passport/getinfo', [
            'login_name' => $mobile,
        ], null, ['connect_timeout' => 30]);
        return json_decode($result, 1);
    }

    /**
     * 获取Nirvana用户中心shopexid. 并拼装正常数据
     * @param $mobile
     * @return mixed
     */
    public function searchBussinessID($mobile)
    {
        $result_data = [];
        $data['login_name'] = $mobile;
        $url = '/yunqiaccount/passport/getinfo';
        $result = $this->prismClient->post($url, $data, null, ['connect_timeout' => 3]);
        $result = json_decode($result, true);
        if (isset($result['data']['uid']) && !empty($result['data']['uid'])) {
            $data = $result['data'];
            $result_data = [
                'eid' => $data['eid'],
                'passport_uid' => $data['uid'],
                'loginname' => $data['login_name'],
                'shopexid' => $data['mobile'],
                'nickname' => $data['name'] ?: $data['mobile'],
            ];
        }

        return $result_data;
    }
}
