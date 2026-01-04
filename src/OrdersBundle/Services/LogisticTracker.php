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
use OrdersBundle\Services\Kuaidi\KdniaoService;
use OrdersBundle\Services\Kuaidi\Kuaidi100Service;
use SuperAdminBundle\Services\LogisticsService;

class LogisticTracker
{
    public $hqepayApiUrl = 'http://api.kdniao.com/Ebusiness/EbusinessOrderHandle.aspx';

    public $requestType = '8001';

    /**
     * @brief 从快递鸟提供的的物流跟踪API，获取物流轨迹
     *
     * @param string $LogisticCode 物流单号
     * @param string $ShipperCode  快递公司编号
     * @param string $receiver_mobile  收货人手机号
     *
     * @return array
     */
    public function pullFromHqepay($LogisticCode, $ShipperCode, $company_id = '', $receiver_mobile = '')
    {
        app('log')->info('pullFromHqepay LogisticCode===>'.$LogisticCode.', ShipperCode===>'.$ShipperCode.', company_id===>'.$company_id.', receiver_mobile===>'.$receiver_mobile);
        //快递鸟配置数据
        $service = new KuaidiService(new KdniaoService());
        $data = $service->getKuaidiSetting($company_id);
        if ($data['EBusinessID'] == '' || $data['appkey'] == '') {
            throw new ResourceException('无效的快递查询配置');
        }

        $EBusinessID = $data['EBusinessID'];

        $appkey = $data['appkey'];

        if (isset($data['request_type']) && $data['request_type']) {
            $this->requestType = $data['request_type'];
        }

        // 付费接口，处理
        if ($result = $this->__payPullFromHqepay($LogisticCode, $ShipperCode, $company_id, $receiver_mobile)) {
            $RequestType = $result['request_type'];
            $content = $result['content'];
        } else {
            //请求类型 8001表示查询订单轨迹
            $RequestType = $this->requestType; //8001;
            //参数内容
            $content = "{'OrderCode':'', 'ShipperCode':'{$ShipperCode}', 'LogisticCode':'{$LogisticCode}'}";
        }

        //签名
        $DataSign = $this->__hqepayEncrypt($content, $appkey);

        # 返回数据类型: 1-xml,2-json
        $DataType = 2;

        $post = array(
            'RequestType' => $RequestType,
            'EBusinessID' => $EBusinessID,
            'RequestData' => urlencode($content),
            'DataSign' => urlencode($DataSign),
            'DataType' => $DataType,
        );
        app('log')->info('pullFromHqepay post===>'.var_export($post, 1));
        try {
            $client = new Client();
            $resData = $client->post($this->hqepayApiUrl, [
                'verify' => false,
                'form_params' => $post
            ])->getBody();
            $responseData = json_decode($resData->getContents(), true);
            app('log')->info('pullFromHqepay responseData===>'.var_export($responseData, 1));
            if (in_array($ShipperCode, ['JD','SF'])) {
                app('log')->info('pullFromHqepay responseData===>'.var_export($responseData, 1));
            }
        } catch (\Exception $e) {
            $responseData = [];
        }

        if ($responseData['Success'] === true) {
            $traces = array();

            foreach ($responseData['Traces'] as $key => $value) {
                $traces[$key]['AcceptTime'] = $value['AcceptTime'];
                $traces[$key]['AcceptStation'] = strip_tags($value['AcceptStation']);
            }
            rsort($traces);
            return $traces;
        } elseif (isset($responseData['Reason'])) {
            throw new ResourceException($responseData['Reason']);
        } else {
            throw new ResourceException('查询失败，请到快递公司官网查询');
        }
    }

    private function __hqepayEncrypt($content, $appkey)
    {
        return base64_encode(md5($content.$appkey));
    }

    /**
     * @brief 从快递100提供的的物流跟踪API，获取物流轨迹
     *
     * @param string $deliv_com  快递公司编号
     * @param string $deliv_no 物流单号
     * @param string $company_id 公司ID
     * @param string $receiver_mobile 收货人手机号
     *
     * @return array
     */
    public function kuaidi100($deliv_com, $deliv_no, $company_id = '', $receiver_mobile = '')
    {
        $post_url = 'https://poll.kuaidi100.com/poll/query.do';

        $service = new KuaidiService(new Kuaidi100Service());
        $data = $service->getKuaidiSetting($company_id);

        if ($data['app_secret'] == '' || $data['app_key'] == '') {
            throw new ResourceException('无效的快递查询配置');
        }

        // 快递鸟快递公司编号转快递100
        if (strtoupper($deliv_com) == $deliv_com) {
            $logisticsService = new LogisticsService();
            $logistic = $logisticsService->getInfo(['corp_code' => $deliv_com]);
            $deliv_com = $logistic['kuaidi_code'] ?? $deliv_com;
        }

        $customer = $data['app_secret'];

        // 所有快递需要收货人手机号
        $param = json_encode(['com' => $deliv_com,'num' => $deliv_no, 'phone' => $receiver_mobile]);
        // if ($deliv_com == 'shunfeng') {
        //     $param = json_encode(['com' => $deliv_com,'num' => $deliv_no, 'phone' => $receiver_mobile]);
        // } else {
        //     $param = json_encode(['com' => $deliv_com,'num' => $deliv_no]);
        // }
        $key = $data['app_key'];
        $sign = strtoupper(md5($param.$key.$customer));
        $post_data = ['customer' => $customer,'sign' => $sign,'param' => $param];

        $client = new Client();
        $resData = $client->post($post_url, [
            'form_params' => $post_data
        ])->getBody();
        $responseData = json_decode($resData->getContents(), true);

        if ((isset($responseData['result']) && $responseData['result'] == false) || (isset($responseData['status_code']) && $responseData['status_code'] != '200')) {
            throw new ResourceException($responseData['message']);
        }

        $traces = array();
        //rsort($responseData['data']);
        foreach ($responseData['data'] as $key => $value) {
            $traces[$key]['AcceptTime'] = $value['ftime'];
            $traces[$key]['AcceptStation'] = strip_tags($value['context']);
        }
        return $traces;
    }

    /**
     * @brief 从快递鸟提供的的物流跟踪API，付费接口，获取参数内容和请求类型
     *
     * @param string $LogisticCode 物流单号
     * @param string $ShipperCode  快递公司编号
     *
     * @return array
     */
    private function __payPullFromHqepay($LogisticCode, $ShipperCode, $company_id = '', $receiver_mobile = '')
    {
        app('log')->info('__payPullFromHqepay LogisticCode===>'.$LogisticCode.', ShipperCode===>'.$ShipperCode.', company_id===>'.$company_id.', receiver_mobile===>'.$receiver_mobile);
        if (!in_array($ShipperCode, ['JD','SF','ZTO'])) {
            return false;
        }
        app('log')->info('__payPullFromHqepay ShipperCode===>'.$ShipperCode);
        $result = [];
        switch ($ShipperCode) {
            case 'JD':
                $result = $this->__jdPullFromHqepay($LogisticCode, $ShipperCode, $company_id);
                break;
            case 'SF':
                $result = $this->__sfPullFromHqepay($LogisticCode, $ShipperCode, $company_id, $receiver_mobile);
                break;
            case 'ZTO':
                app('log')->info('__payPullFromHqepay ZTO===>'.$LogisticCode.', ShipperCode===>'.$ShipperCode.', company_id===>'.$company_id.', receiver_mobile===>'.$receiver_mobile);
                $result = $this->__ztoPullFromHqepay($LogisticCode, $ShipperCode, $company_id, $receiver_mobile);
                break;
            default:
                # code...
                break;
        }
        return $result;
    }

    /**
    * 快递鸟，京东物流，获取参数内容和请求类型
    *
    * @param string $LogisticCode 物流单号
    * @param string $ShipperCode  快递公司编号
    */
    private function __jdPullFromHqepay($LogisticCode, $ShipperCode, $company_id = '')
    {
        // 查询青龙编码
        $service = new KdniaoService();
        $CustomerName = $service->getQingLongCode($company_id);
        $content = "{'OrderCode':'', 'ShipperCode':'{$ShipperCode}', 'LogisticCode':'{$LogisticCode}', 'CustomerName':'{$CustomerName}'}";
        $result = [
            'request_type' => $this->requestType, //'8001',
            'content' => $content,
        ];
        app('log')->info('__jdPullFromHqepay result===>'.var_export($result, 1));
        return $result;
    }

    /**
    * 快递鸟，顺丰快递，获取参数内容和请求类型
    *
    * @param string $LogisticCode 物流单号
    * @param string $ShipperCode  快递公司编号
    * @param string $receiver_mobile:收货人手机号
    */
    private function __sfPullFromHqepay($LogisticCode, $ShipperCode, $company_id = '', $receiver_mobile = '')
    {
        // 截取收货人手机号后4位
        $CustomerName = substr($receiver_mobile, -4);
        $content = "{'OrderCode':'', 'ShipperCode':'{$ShipperCode}', 'LogisticCode':'{$LogisticCode}', 'CustomerName':'{$CustomerName}'}";
        $result = [
            'request_type' => $this->requestType, //'8001',
            'content' => $content,
        ];
        app('log')->info('__sfPullFromHqepay result===>'.var_export($result, 1));
        return $result;
    }

    private function __ztoPullFromHqepay($LogisticCode, $ShipperCode, $company_id = '', $receiver_mobile = '')
    {
        $CustomerName = substr($receiver_mobile, -4);
        $content = "{'OrderCode':'', 'ShipperCode':'{$ShipperCode}', 'LogisticCode':'{$LogisticCode}', 'CustomerName':'{$CustomerName}'}";
        app('log')->info('__ztoPullFromHqepay content===>'.$content);
        $result = [
            'request_type' => $this->requestType, //'8001',
            'content' => $content,
        ];
        app('log')->info('__ztoPullFromHqepay result===>'.var_export($result, 1));
        return $result;
    }

    /**
    * 顺丰bsp快递，获取参数内容和请求类型
    *
    * @param string $LogisticCode 物流单号
    * @param string $ShipperCode  快递公司编号
    */
    public function sfbspCheck($LogisticCode, $ShipperCode, $company_id, $receiver_mobile)
    {
        if ($ShipperCode != 'SF') {
            return false;
        }
        if (empty($company_id)) {
            return false;
        }
        $service = new SfbspServices($company_id);
        $setting = $service->getSfbspSetting();
        if (empty($setting)) {
            return false;
        }
        if (isset($setting['is_open']) && $setting['is_open'] != 'true') {
            return false;
        }
        $result = $service->RouteService($LogisticCode, '1', '1', $receiver_mobile);
        if (isset($result['errorCode']) && $result['errorCode'] == 'S0000') {
            $RouteResponse = $result['msgData']['routeResps'][0]['routes'];

            $traces = array();
            foreach ($RouteResponse as $key => $value) {
                $traces[$key]['AcceptTime'] = $value['acceptTime'];
                $traces[$key]['AcceptStation'] = strip_tags($value['acceptAddress']).'-'.strip_tags($value['remark']);
            }
            rsort($traces);
            return $traces;
        }
        return false;
    }
}
