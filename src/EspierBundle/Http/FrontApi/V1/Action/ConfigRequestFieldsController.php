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

namespace EspierBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use Illuminate\Http\Request;
use Swagger\Annotations as SWG;

class ConfigRequestFieldsController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/espier/config/request_field",
     *     tags={"系统"},
     *     summary="配置请求字段 - 获取列表",
     *     description="配置请求字段 - 获取列表",
     *     operationId="list",
     *     @SWG\Parameter(name="module_type", in="query", description="模块类型 【1: 会员个人信息】", required=true, type="string", default="1"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *             @SWG\Property(property="data", type="object", description="", required={"list", "config"},
     *               @SWG\Property(property="config", type="object", description="配置项内容（是对象）"),
     *               @SWG\Property(property="list", type="array", description="字段内容的列表（是数组对象）",
     *                   @SWG\Items(type="object", required={"name","key","is_open","is_required","is_edit","is_default","element_type", "field_type","required_message","validate_message","range","select","checkbox"},
     *                       @SWG\Property(property="name", type="string", example="年收入", description="字段的中文含义"),
     *                       @SWG\Property(property="key", type="string", example="income", description="字段的key名，用于前后端传递的字段标识"),
     *                       @SWG\Property(property="is_open", type="boolean", example="true", description="是否是启用,【true 开启】，【false 关闭】"),
     *                       @SWG\Property(property="is_required", type="boolean", example="true", description="是否是必填,【true 开启】，【false 关闭】"),
     *                       @SWG\Property(property="is_edit", type="boolean", example="true", description="是否可修改,【true 开启】，【false 关闭】"),
     *                       @SWG\Property(property="is_default", type="boolean", example="true", description="是否是预设字段,【true 是】，【false 不是】"),
     *                       @SWG\Property(property="element_type", type="string", example="select", description="字段类型，【input 文本】【numeric 数字】【date 日期】【select 单选项】【checkbox 复选框】"),
     *                       @SWG\Property(property="field_type", type="integer", example="4", description="字段类型，【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】"),
     *                       @SWG\Property(property="required_message", type="string", example="请输入您的年收入", description="字段没有填写时所出现的提示文案"),
     *                       @SWG\Property(property="validate_message", type="string", example="", description=""),
     *                       @SWG\Property(property="range", type="array", description="是一个数组对象，验证时的取值范围",
     *                           @SWG\Items(required={"start", "end"},type="object",
     *                               @SWG\Property(property="start", type="string", description="最小值"),
     *                               @SWG\Property(property="end", type="string", description="最大值"),
     *                           ),
     *                       ),
     *                       @SWG\Property(property="select", type="array", description="是一个数组字符串，验证时的可选择的选项",
     *                          @SWG\Items(),
     *                       ),
     *                       @SWG\Property(property="checkbox", type="array", description="是一个数组对象，验证时的可勾选的选项",
     *                           @SWG\Items(required={"name","ischecked"},type="object",
     *                               @SWG\Property(property="name", type="string", description="选项的名称"),
     *                               @SWG\Property(property="ischecked", type="boolean", description="是否被选中,【true 选中】，【false 未选中】"),
     *                           ),
     *                       ),
     *                   ),
     *               ),
     *             ),
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        // 获取用户信息
        $user = $request->get('auth');
        // 获取企业id
        $companyId = (int)($user["company_id"] ?? 0);
        // 获取模块类型
        $moduleType = (string)$request->input("module_type", ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
        $list = [];
        if (!empty($moduleType)) {
            $list = (new ConfigRequestFieldsService())->getListAndHandleSettingFormat($companyId, $moduleType);
        }
        // 获取配置信息
        $config = (new ConfigRequestFieldsService())->getSetting($companyId, $moduleType);

        return $this->response->array([
            "list" => $list,
            "config" => $config
        ]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/espier/config/request_field_setting",
     *     tags={"系统"},
     *     summary="配置请求字段 - 获取配置项",
     *     description="配置请求字段 - 获取配置项",
     *     operationId="getConfig",
     *     @SWG\Parameter(name="module_type", in="query", description="模块类型 【1: 会员个人信息】", required=true, type="string", default="1"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="配置项的信息",
     *               @SWG\Property(property="key", type="string", example="value", description=""),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getConfig(Request $request)
    {
        // 获取用户信息
        $user = $request->get('auth');
        // 获取企业id
        $companyId = (int)($user["company_id"] ?? 0);
        // 获取模块类型
        $moduleType = (string)$request->input("module_type", ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
        // 批量存入hash中
        $data = (new ConfigRequestFieldsService())->getSetting($companyId, $moduleType);
        return $this->response->array($data);
    }
}
