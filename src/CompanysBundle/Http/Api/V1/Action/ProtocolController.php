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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\Shops\ProtocolService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class ProtocolController extends BaseController
{
    /**
     * @SWG\Put(
     *     path="/shops/protocol",
     *     tags={"企业"},
     *     summary="商城协议 - 更新 - 后台",
     *     description="商城协议 - 更新 - 后台",
     *     operationId="set",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.type", in="query", description="协议类型 【member_resgiter 用户注册协议】【privacy 隐私策略协议】【member_logout 注销协议】【member_logout_config 注销配置】", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.save_type", in="query", description="保存类型 draft 仅保存", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.title", in="query", description="协议标题", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.content", in="query", description="协议内容", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.update_date", in="query", description="更新日期", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.take_effect_date", in="query", description="生效日期", required=true, type="string"),
     *     @SWG\Parameter(name="data.*.new_rights", in="query", description="是否享受新人权益", required=true, type="string"),

     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"status"},
     *               @SWG\Property(property="status", type="boolean", default="true", description="更新状态"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function set(Request $request)
    {
        $data = (array)$request->input("data");
        foreach ($data as &$datum) {
            // 将内容转成数组
            $datum = (array)jsonDecode($datum);
        }
        // 参数验证
        $validator = app("validator")->make(["data" => $data], [
            "data" => ["required"],
            "data.*.type" => ["required"],
        ], [
            "data.required" => "协议格式有误！",
            "data.*.type.required" => "协议类型必填！",
        ]);
        if ($validator->fails()) {
            throw new ResourceException($validator->errors()->first());
        }
        foreach ($data as &$protocol) {
            $timeData = date('Y-m-d', time());
            $time = strtotime($timeData);
            if ($protocol["type"] == ProtocolService::TYPE_MEMBER_LOGOUT) {
                if (isset($protocol["update_date"]) || isset($protocol["take_effect_date"])) {
                    $protocol["update_date"] = empty($protocol["update_date"]) ? $timeData : $protocol["update_date"];
                    $protocol["take_effect_date"] = empty($protocol["take_effect_date"]) ? $timeData : $protocol["take_effect_date"];
                    $update_date = strtotime($protocol["update_date"]);
                    $take_effect_date = strtotime($protocol["take_effect_date"]);
                    if ($take_effect_date < $update_date) {
                        throw new ResourceException('更新日期不能大于生效日期');
                    }
                    if ($time > $update_date) {
                        throw new ResourceException('更新日期不能小于当前日期');
                    }
                    if ($time > $take_effect_date) {
                        throw new ResourceException('生效日期不能小于当前日期');
                    }
                }
            }
        }
        unset($protocol);
        // 获取企业id
        $auth = app('auth')->user()->get();
        $companyId = (int)$auth['company_id'];
        $protocolService = new ProtocolService($companyId);
        // 批量更新协议信息
        foreach ($data as $protocol) {
            $updateData['type'] = $protocol["type"];
            $updateData['title'] = $protocol["title"] ?? "";
            if ($protocol["type"] == ProtocolService::TYPE_MEMBER_LOGOUT_CONFIG) {
                $updateData['new_rights'] = $protocol["new_rights"] ?? '0';
            } else {
                $updateData['content'] = $protocol["content"] ?? "";
                $updateData['update_date'] = $protocol["update_date"] ?? "";
                $updateData['take_effect_date'] = $protocol["take_effect_date"] ?? "";
                $updateData['update_time'] = time();
            }
            if (!empty($protocol['save_type']) && $protocol['save_type'] == 'draft') {
                $protocolType = $protocol["type"].'_draft';
            } else {
                $protocolType = (string)$protocol["type"];
                $updateDataDraft = [];
                $protocolService->set($protocolType.'_draft', $updateDataDraft);
            }
            $protocolService->set($protocolType, $updateData);
        }
        return $this->response->array(["status" => true]);
    }

    /**
     * @SWG\Get(
     *     path="/shops/protocol",
     *     tags={"企业"},
     *     summary="商城协议 - 获取 - 后台",
     *     description="商城协议 - 获取 - 后台",
     *     operationId="get",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"member_resgiter"},
     *              @SWG\Property(property="member_resgiter", type="object", description="key为协议类型，value为协议里的数据",required={"type","title","content"},
     *                   @SWG\Property(property="title", type="string", example="123", description="协议类型"),
     *                   @SWG\Property(property="content", type="string", example="12123", description="协议标题"),
     *                   @SWG\Property(property="type", type="string", example="member_resgiter", description="协议内容"),
     *                   @SWG\Property(property="update_date", type="string", example="2021-11-08", description="更新日期"),
     *                   @SWG\Property(property="take_effect_date", type="string", example="2021-11-08", description="生效日期"),
     *                   @SWG\Property(property="new_rights", type="string", example="1", description="是否享受新人权益"),
     *                   @SWG\Property(property="draft", type="array", example="[]", description="草稿内容", @SWG\Items(
     *                      @SWG\Property(property="title", type="string", example="123", description="协议类型"),
     *                      @SWG\Property(property="content", type="string", example="12123", description="协议标题"),
     *                      @SWG\Property(property="type", type="string", example="member_resgiter", description="协议内容"),
     *                      @SWG\Property(property="update_date", type="string", example="2021-11-08", description="更新日期"),
     *                      @SWG\Property(property="take_effect_date", type="string", example="2021-11-08", description="生效日期"),
     *                      @SWG\Property(property="new_rights", type="string", example="1", description="是否享受新人权益"),
     *                  )),
     *              ),
     *            ),
     *         ),
     *     ),
     *    @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function get(Request $request)
    {
        // fe10e2f6 module
        $auth = app('auth')->user()->get();
        $companyId = (int)$auth['company_id'];
        // 获取类型
        $type = (string)$request->input("type");
        if (!empty($type)) {
            $typeArray = [$type];
        } else {
            $typeArray = null;
        }
        $result = (new ProtocolService($companyId))->get($typeArray);
        return $this->response->array($result);
    }
}
