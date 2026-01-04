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

namespace CompanysBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\Shops\ProtocolService;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class ProtocolController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/shops/protocol",
     *     tags={"企业"},
     *     summary="商城协议 - 获取",
     *     description="获取商城内的协议信息",
     *     operationId="get",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="type", in="query", description="协议类型 【member_register 用户注册协议】【privacy 隐私策略协议】", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"type","title","content"},
     *               @SWG\Property(property="type", type="string", example="member_register", description="协议类型"),
     *               @SWG\Property(property="title", type="string", example="", description="协议标题"),
     *               @SWG\Property(property="content", type="string", example="", description="协议内容"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function get(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = (int)$authInfo['company_id'];

        $type = (string)$request->input("type");
        $result = [
            "type" => "",
            "title" => "",
            "content" => "",
            "update_date" => "",
            "take_effect_date" => "",
        ];
        if (!empty($type)) {
            // 获取用户的注册协议
            $data = (new ProtocolService($companyId))->get([$type]);
            $result["type"] = (string)($data[$type]["type"] ?? "");
            $result["title"] = (string)($data[$type]["title"] ?? "");
            $result["content"] = (string)($data[$type]["content"] ?? "");
            $result['update_date'] = (string)($data[$type]["update_date"] ?? "");
            $result['take_effect_date'] = (string)($data[$type]["take_effect_date"] ?? "");
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/shops/protocolUpdateTime",
     *     tags={"企业"},
     *     summary="商城协议更新时间 - 获取",
     *     description="获取商城内的协议更新时间",
     *     operationId="get",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"type","title","content"},
     *               @SWG\Property(property="update_time", type="string", example="", description="协议更新时间")
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getUpdateTime(Request $request)
    {
        $companyId = $request->get('company_id');
        $member_register = ProtocolService::TYPE_MEMBER_REGISTER;
        $privacy = ProtocolService::TYPE_PRIVACY;
        $privacyData = (new ProtocolService($companyId))->get([$privacy]);
        $memberRegisterData = (new ProtocolService($companyId))->get([$member_register]);
        $member_register_update_time = $memberRegisterData[$member_register]['update_time'] ?? '';
        $privacy_update_time = $privacyData[$privacy]['update_time'] ?? '';
        $update_time = max($member_register_update_time, $privacy_update_time);
        $result['update_time'] = empty($update_time) ? 0 : $update_time;
        return $this->response->array($result);
    }



    // protocolsaleman
    
    /**
     * @SWG\Get(
     *     path="/wxapp/shops/protocolsaleman",
     *     tags={"企业"},
     *     summary="商城协议 - 获取",
     *     description="获取商城内的协议信息",
     *     operationId="get",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="type", in="query", description="协议类型 【member_register 用户注册协议】【privacy 隐私策略协议】", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"type","title","content"},
     *               @SWG\Property(property="type", type="string", example="member_register", description="协议类型"),
     *               @SWG\Property(property="title", type="string", example="", description="协议标题"),
     *               @SWG\Property(property="content", type="string", example="", description="协议内容"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function protocolsaleman(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = (int)$authInfo['company_id'];

        // $type = (string)$request->input("type");

        $type_arr = ['salesman_service','salesman_privacy'];

        $result = array();
        foreach($type_arr as  $k => $type){
            if (!empty($type)) {
                // 获取用户的注册协议
                $type_tmp = $type;
                $data = (new ProtocolService($companyId))->get([$type]);
                $result[$type]["type"] = (string)($data[$type_tmp]["type"] ?? "");
                $result[$type]["title"] = (string)($data[$type_tmp]["title"] ?? "");
                $result[$type]["content"] = (string)($data[$type_tmp]["content"] ?? "");
                $result[$type]['update_date'] = (string)($data[$type_tmp]["update_date"] ?? "");
                $result[$type]['take_effect_date'] = (string)($data[$type_tmp]["take_effect_date"] ?? "");
                
            }
        }

        return $this->response->array($result);
    }

}
