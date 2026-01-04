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

namespace ThirdPartyBundle\Http\ThirdApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use ThirdPartyBundle\Http\Controllers\Controller;
use ThirdPartyBundle\Services\CustomsCentre\CustomsService;

class Customs extends Controller
{
    public function setPlatData(Request $request)
    {
        // Ver: 1e2364-fe10
        $params = $request->all();
        $openReq = json_decode($params['openReq'], 1);

        $validator = app('validator')->make($openReq, [
            'orderNo' => 'required',
            'sessionID' => 'required',
            'serviceTime' => 'required',
        ], [
            'orderNo.*' => '订单编号不能为空',
            'sessionID.*' => '会话ID不能为空',
            'serviceTime.*' => '系统时间不能为空',
        ]);
        $return = array(
            'code' => '10000',
            'message' => '',
            'serviceTime' => time()
        );
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            $return['code'] = '000001';
            $return['message'] = $errmsg;
            exit(json_encode($return));
        }
        try {
            $cstomsService = new CustomsService();
            $data['order_id'] = $openReq['orderNo'];
            $data['session_id'] = $openReq['sessionID'];
            $data['service_time'] = $openReq['serviceTime'];
            $cstomsService->create($data);
        } catch (\Exception $e) {
            $return['code'] = '000001';
            $return['message'] = $e->getMessage();
            exit(json_encode($return));
        }
        exit(json_encode($return));
    }

    public function getOrderData(Request $request)
    {
        $params = $request->all();
        $validator = app('validator')->make($params, [
            'timestamp' => 'required',
            'sign' => 'required',
        ], [
            'timestamp.*' => '时间戳不能为空',
            'sign.*' => '签名不能为空',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $customsService = new CustomsService();
        $list = $customsService->getOrderStruct($params);
        return $this->response->array($list);
    }

    public function updateOrderData(Request $request)
    {
        // Ver: 1e2364-fe10
        $params = $request->all();
        $validator = app('validator')->make($params, [
            'order_id' => 'required',
            'response' => 'required',
        ], [
            'order_id.*' => '订单编号不能为空',
            'response.*' => 'response不能为空',
        ]);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = '';
            foreach ($errorsMsg as $v) {
                $msg = implode("，", $v);
                $errmsg .= $msg . "，";
            }
            throw new ResourceException($errmsg);
        }

        $customsService = new CustomsService();
        $customsService->updateOrderData($params);

        return $this->response->array([]);
    }
}
