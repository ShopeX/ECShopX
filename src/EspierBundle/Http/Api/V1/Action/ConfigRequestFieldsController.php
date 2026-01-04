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

namespace EspierBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Swagger\Annotations as SWG;

class ConfigRequestFieldsController extends Controller
{
    protected $service;

    public function __construct()
    {
        $this->service = new ConfigRequestFieldsService();
    }

    /**
     * @SWG\Get(
     *     path="api/espier/config/request_fields",
     *     tags={"系统"},
     *     summary="配置请求字段 - 获取列表 - 后台",
     *     description="配置请求字段 - 获取列表 - 后台",
     *     operationId="list",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Parameter(name="module_type", in="query", description="模块类型 【1: 会员个人信息】", required=true, type="string", default="1"),
     *     @SWG\Parameter(name="id", in="query", description="主键id, 如果给-1则表示不过滤id", required=true, type="string", default="-1"),
     *     @SWG\Parameter(name="page", in="query", description="当前页", required=true, type="integer", default="1"),
     *     @SWG\Parameter(name="page_size", in="query", description="每页数量", required=true, type="integer", default="10"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Property(property="data", type="object", description="", required={"total_count", "list"},
     *               @SWG\Property(property="total_count", type="integer", default="1", description=""),
     *               @SWG\Property(property="list", type="array", description="",
     *                    @SWG\Items(type="object", required={"id","company_id","module_type","label","key_name","is_open","is_required","is_edit","field_type","alert_required_message", "alert_validate_message","is_must_start_required","created","updated","created_desc","updated_desc","module_type_desc","field_type_desc","validate_condition","validate_condition_range","validate_condition_radio"},
     *                     @SWG\Property(property="id", type="integer", default="1", description="主键id"),
     *                     @SWG\Property(property="company_id", type="integer", default="2", description="企业id"),
     *                     @SWG\Property(property="module_type", type="integer", default="1", description="模块类型"),
     *                     @SWG\Property(property="label", type="string", default="手机号", description="请求字段的中文说明"),
     *                     @SWG\Property(property="key_name", type="string", default="mobile", description="请求字段的前后端交互的key名"),
     *                     @SWG\Property(property="is_open", type="integer", default="1", description="是否开启，1表示开启，0表示关闭"),
     *                     @SWG\Property(property="is_required", type="integer", default="1", description="是否必填项，1表示必填，0表示非必填"),
     *                     @SWG\Property(property="is_edit", type="integer", default="1", description="是否可以修改，1表示可修改，0表示不可修改"),
     *                     @SWG\Property(property="field_type", type="integer", default="1", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】"),
     *                     @SWG\Property(property="alert_required_message", type="string", default="手机号必填", description="提示信息，该请求字段在验证出错时需要返回出去的错误信息"),
     *                     @SWG\Property(property="alert_validate_message", type="string", default="", description="提示信息，基于validate_condition中验证出错时需要被返回去的错误信息，如果不存在就取alert_required_message的内容"),
     *                     @SWG\Property(property="is_must_start_required", type="integer", default="", description="是否是必须开启和必填, 1为必须开启且必填, 0为可以手动开启关闭"),
     *                     @SWG\Property(property="created", type="string", default="1619427490", description="创建时间（时间戳）"),
     *                     @SWG\Property(property="updated", type="string", default="1619430699", description="更新时间（时间戳）"),
     *                     @SWG\Property(property="created_desc", type="string", default="2021-04-26 16:58:10", description="创建时间（Y-m-d H:i:s）"),
     *                     @SWG\Property(property="updated_desc", type="string", default="2021-04-26 17:51:39", description="更新时间（Y-m-d H:i:s）"),
     *                     @SWG\Property(property="module_type_desc", type="string", default="会员个人信息", description="module_type字段的中文描述"),
     *                     @SWG\Property(property="field_type_desc", type="string", default="单选项", description="field_type字段的中文描述"),
     *                     @SWG\Property(property="validate_condition", type="array", description="验证规则的原数据内容",
     *                         @SWG\Items(type="object", required={"value", "label", "is_checked"},
     *                             @SWG\Property(property="value", type="string", default="1", description="这条规则的值，如果是取值范围则是用用逗号来拼接，比如0,999就是最小值为0, 最大值为999"),
     *                             @SWG\Property(property="label", type="string", default="字段1", description="这条规则的描述"),
     *                             @SWG\Property(property="is_checked", type="string", default="0", description="表示默认是否选中, 【0 不选中】【1 选中】"),
     *                         ),
     *                     ),
     *                     @SWG\Property(property="range", type="object", description="验证规则的取值范围的数组对象",required={"start","end"},
     *                         @SWG\Property(property="start", type="string", default="0", description="最小值，如果是null则不取下限"),
     *                         @SWG\Property(property="end", type="string", default="999", description="最大值，如果是null则不取上限"),
     *                     ),
     *                     @SWG\Property(property="radio_list", type="array", description="验证规则的单选项列表的数组对象",
     *                         @SWG\Items(type="object", required={"value", "label"},
     *                             description="value为该选项的值，label为该选项的描述",
     *                             required={"value", "label", "is_checked"},
     *                             @SWG\Property(property="label", type="string", default="未知", description="描述"),
     *                             @SWG\Property(property="is_checked", type="string", default="0", description="表示默认是否选中, 【0 不选中】【1 选中】"),
     *                         ),
     *                     ),
     *                    )
     *               ),
     *         ),
     *     ),
     * )
     */
    public function list(Request $request)
    {
        // 获取用户信息
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);
        // 过滤条件
        $filter = [
            "module_type" => (int)$request->query("module_type" ?? 0), // 模块类型
            "id" => (int)$request->query("id", -1), // 主键id
            "company_id" => $companyId // 企业id
        ];
        if ($filter["id"] == -1) {
            unset($filter["id"]);
        }
        // 当前页
        $page = (int)$request->input("page", 1);
        // 每页数量
        $pageSize = (int)$request->input("page_size", 10);
        // 检查是否需要初始化
        $this->service->checkIsNeedInit($filter);
        // 获取分页数据
        $data = $this->service->paginate($companyId, $filter, $page, $pageSize, ["id" => "DESC"]);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/api/espier/config/request_fields",
     *     tags={"系统"},
     *     summary="配置请求字段 - 创建字段的内容 - 后台",
     *     description="配置请求字段 - 创建字段的内容 - 后台",
     *     operationId="create",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Parameter(name="module_type", in="query", description="模块类型 【1: 会员个人信息】", required=true, type="integer", default=""),
     *     @SWG\Parameter(name="label", in="query", description="字段的中文描述", required=true, type="string", default=""),
     *     @SWG\Parameter(name="is_open", in="query", description="是否开启的开关，1表示开启，0表示关闭, 默认是关闭", required=false, type="integer", default="0"),
     *     @SWG\Parameter(name="is_required", in="query", description="是否是必填的开关，1表示开启，0表示关闭, 默认是关闭", required=false, type="integer", default="0"),
     *     @SWG\Parameter(name="is_edit", in="query", description="是否可修改的开关，1表示开启，0表示关闭, 默认是关闭", required=false, type="integer", default="0"),
     *     @SWG\Parameter(name="field_type", in="query", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】", required=true, type="integer", default=""),
     *     @SWG\Parameter(name="alert_required_message", in="query", description="验证不通过时需要被弹出的文本信息", required=true, type="string", default=""),
     *     @SWG\Parameter(name="range.*.start", in="query", description="验证信息：取值范围：最小值, 如果是null则不取下限", required=false, type="string", default=""),
     *     @SWG\Parameter(name="range.*.end", in="query", description="验证信息：取值范围：最大值，如果是null则不取上限", required=false, type="string", default=""),
     *     @SWG\Parameter(name="radio_list.*.value", in="query", description="验证信息: 单选或复选的可选列表：表示该选项的实际值", required=true, type="string", default=""),
     *     @SWG\Parameter(name="radio_list.*.label", in="query", description="验证信息: 单选或复选的可选列表：表示需要在前端展示的选项名字", required=true, type="string", default=""),
     *     @SWG\Parameter(name="radio_list.*.is_checked", in="query", description="验证信息: 单选或复选的可选列表：表示默认是否选中【0 不选中】【1 选中】", required=true, type="integer", default=""),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"id","company_id","module_type","label","key_name","is_open","is_required","is_edit","field_type","alert_required_message", "alert_validate_message","is_must_start_required","created","updated","created_desc","updated_desc","module_type_desc","field_type_desc","validate_condition","validate_condition_range","validate_condition_radio"},
     *                 @SWG\Property(property="id", type="integer", default="1", description="主键id"),
     *                 @SWG\Property(property="company_id", type="integer", default="2", description="企业id"),
     *                 @SWG\Property(property="module_type", type="integer", default="1", description="模块类型"),
     *                 @SWG\Property(property="label", type="string", default="手机号", description="请求字段的中文说明"),
     *                 @SWG\Property(property="key_name", type="string", default="mobile", description="请求字段的前后端交互的key名"),
     *                 @SWG\Property(property="is_open", type="integer", default="1", description="是否开启，1表示开启，0表示关闭"),
     *                 @SWG\Property(property="is_required", type="integer", default="1", description="是否必填项，1表示必填，0表示非必填"),
     *                 @SWG\Property(property="is_edit", type="integer", default="1", description="是否可以修改，1表示可修改，0表示不可修改"),
     *                 @SWG\Property(property="field_type", type="integer", default="1", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】"),
     *                 @SWG\Property(property="alert_required_message", type="string", default="手机号必填", description="提示信息，该请求字段在验证出错时需要返回出去的错误信息"),
     *                 @SWG\Property(property="alert_validate_message", type="string", default="", description="提示信息，基于validate_condition中验证出错时需要被返回去的错误信息，如果不存在就取alert_required_message的内容"),
     *                 @SWG\Property(property="is_must_start_required", type="integer", default="", description="是否是必须开启和必填, 1为必须开启且必填, 0为可以手动开启关闭"),
     *                 @SWG\Property(property="created", type="string", default="1619427490", description="创建时间（时间戳）"),
     *                 @SWG\Property(property="updated", type="string", default="1619430699", description="更新时间（时间戳）"),
     *                 @SWG\Property(property="created_desc", type="string", default="2021-04-26 16:58:10", description="创建时间（Y-m-d H:i:s）"),
     *                 @SWG\Property(property="updated_desc", type="string", default="2021-04-26 17:51:39", description="更新时间（Y-m-d H:i:s）"),
     *                 @SWG\Property(property="module_type_desc", type="string", default="会员个人信息", description="module_type字段的中文描述"),
     *                 @SWG\Property(property="field_type_desc", type="string", default="单选项", description="field_type字段的中文描述"),
     *                 @SWG\Property(property="validate_condition", type="array", description="验证规则的原数据内容",
     *                     @SWG\Items(type="object", required={"value", "label", "is_checked"},
     *                         @SWG\Property(property="value", type="string", default="1", description="这条规则的值，如果是取值范围则是用用逗号来拼接，比如0,999就是最小值为0, 最大值为999"),
     *                         @SWG\Property(property="label", type="string", default="字段1", description="这条规则的描述"),
     *                         @SWG\Property(property="is_checked", type="string", default="0", description="表示默认是否选中, 【0 不选中】【1 选中】"),
     *                     ),
     *                 ),
     *                 @SWG\Property(property="range", type="object", description="验证规则的取值范围的数组对象",required={"start","end"},
     *                     @SWG\Property(property="start", type="string", default="0", description="最小值，如果是null则不取下限"),
     *                     @SWG\Property(property="end", type="string", default="999", description="最大值，如果是null则不取上限"),
     *                 ),
     *                 @SWG\Property(property="radio_list", type="array", description="验证规则的单选项列表的数组对象",
     *                     @SWG\Items(type="object", required={"value", "label"},
     *                         description="value为该选项的值，label为该选项的描述",
     *                         required={"value", "label", "is_checked"},
     *                         @SWG\Property(property="label", type="string", default="未知", description="描述"),
     *                         @SWG\Property(property="is_checked", type="string", default="0", description="表示默认是否选中, 【0 不选中】【1 选中】"),
     *                     ),
     *                 ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function create(Request $request)
    {
        // 获取用户信息
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);
        // 参数验证
        $formData = $request->input();
        $error = validator_params($formData, [
            "module_type" => ["required", "模块类型必填！"],
            "label" => ["required|max:50", "字段信息必填且长度不能超过50个字符！"],
            "is_open" => ["nullable", "是否启用的参数有误！"],
            "is_required" => ["nullable", "是否必填的参数有误！"],
            "is_edit" => ["nullable", "是否可修改的参数有误！"],
            "field_type" => ["required|" . Rule::in(array_keys(ConfigRequestFieldsService::FIELD_TYPE_MAP)), "字段信息格式有误！"],
            "alert_required_message" => ["required", "提示信息必填"],
        ]);
        if ($error) {
            throw new ResourceException($error);
        }
        // 创建数据
        $data = $this->service->create($companyId, (int)$formData["module_type"], $formData);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/api/espier/config/request_field_setting",
     *     tags={"系统"},
     *     summary="配置请求字段 - 设置配置项 - 后台",
     *     description="配置请求字段 - 设置配置项 - 后台",
     *     operationId="updateConfig",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="module_type", in="query", description="模块类型 【1: 会员个人信息】", required=true, type="string", default="1"),
     *     @SWG\Parameter(name="switch_first_auth_force_validation", in="query", description="初次授权时强制填写的开关【1 开启】【0 关闭】", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",required={"status"},
     *               @SWG\Property(property="status", type="boolean", example="true", description="更新的状态"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function updateConfig(Request $request)
    {
        // 获取用户信息
        $authData = app('auth')->user()->get();
        // 获取企业id
        $companyId = (int)($authData["company_id"] ?? 0);
        // 获取模块类型
        $moduleType = (string)$request->input("module_type", ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
        // 更新数据
        $this->service->updateSetting($companyId, $moduleType, (array)$request->input());
        return $this->response->array(["status" => true]);
    }

    /**
     * @SWG\Get(
     *     path="/api/espier/config/request_field_setting",
     *     tags={"系统"},
     *     summary="配置请求字段 - 获取配置项 - 后台",
     *     description="配置请求字段 - 获取配置项 - 后台",
     *     operationId="getConfig",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
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
        $authData = app('auth')->user()->get();
        // 获取企业id
        $companyId = (int)($authData["company_id"] ?? 0);
        // 获取模块类型
        $moduleType = (string)$request->input("module_type", ConfigRequestFieldsService::MODULE_TYPE_MEMBER_INFO);
        // 获取hash中的配置
        $data = $this->service->getSetting($companyId, $moduleType);
        return $this->response->array($data);
    }

    /**
     * @SWG\Put(
     *     path="api/espier/config/request_fields/switch",
     *     tags={"系统"},
     *     summary="配置请求字段 - 更新字段的开关 - 后台",
     *     description="配置请求字段 - 更新字段的开关 - 后台",
     *     operationId="updateSwitch",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Parameter(name="id", in="query", description="主键id", required=true, type="integer", default="1"),
     *     @SWG\Parameter(name="type", in="query", description="开关的类型 【1 字段是否开启的开关】【2 字段是否必填的开关】【3 字段是否可以修改的开关】", required=true, type="integer", default="1"),
     *     @SWG\Parameter(name="switch", in="query", description="开关的状态 【0 关闭】【1 开启】", required=true, type="integer", default="1"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"status"},
     *               @SWG\Property(property="status", type="boolean", default="true", description="更新的状态"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function updateSwitch(Request $request)
    {
        // 获取用户信息
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);
        // 获取主键id
        $id = (int)$request->input("id");
        // 更新字段
        if ($id > 0) {
            $this->service->updateSwitch($companyId, $id, (int)$request->input("type"), (bool)$request->input("switch"));
        }
        return $this->response->array(["status" => true]);
    }

    /**
     * @SWG\Put(
     *     path="/api/espier/config/request_fields/info",
     *     tags={"系统"},
     *     summary="配置请求字段 - 更新字段的内容 - 后台",
     *     description="配置请求字段 - 更新字段的内容 - 后台",
     *     operationId="updateInfo",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Parameter(name="label", in="query", description="字段的中文描述", required=true, type="string", default=""),
     *     @SWG\Parameter(name="field_type", in="query", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】", required=true, type="integer", default=""),
     *     @SWG\Parameter(name="id", in="query", description="主键id", required=true, type="integer", default=""),
     *     @SWG\Parameter(name="alert_required_message", in="query", description="验证不通过时需要被弹出的文本信息", required=true, type="string", default=""),
     *     @SWG\Parameter(name="range.*.start", in="query", description="验证信息：取值范围：最小值, 如果是null则不取下限", required=false, type="string", default=""),
     *     @SWG\Parameter(name="range.*.end", in="query", description="验证信息：取值范围：最大值，如果是null则不取上限", required=false, type="string", default=""),
     *     @SWG\Parameter(name="radio_list.*.value", in="query", description="验证信息: 单选或复选的可选列表：表示该选项的实际值", required=true, type="string", default=""),
     *     @SWG\Parameter(name="radio_list.*.label", in="query", description="验证信息: 单选或复选的可选列表：表示需要在前端展示的选项名字", required=true, type="string", default=""),
     *     @SWG\Parameter(name="radio_list.*.is_checked", in="query", description="验证信息: 单选或复选的可选列表：表示默认是否选中【0 不选中】【1 选中】", required=true, type="integer", default=""),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"id","company_id","module_type","label","key_name","is_open","is_required","is_edit","field_type","alert_required_message", "alert_validate_message","is_must_start_required","created","updated","created_desc","updated_desc","module_type_desc","field_type_desc","validate_condition","validate_condition_range","validate_condition_radio"},
     *                 @SWG\Property(property="id", type="integer", default="1", description="主键id"),
     *                 @SWG\Property(property="company_id", type="integer", default="2", description="企业id"),
     *                 @SWG\Property(property="module_type", type="integer", default="1", description="模块类型"),
     *                 @SWG\Property(property="label", type="string", default="手机号", description="请求字段的中文说明"),
     *                 @SWG\Property(property="key_name", type="string", default="mobile", description="请求字段的前后端交互的key名"),
     *                 @SWG\Property(property="is_open", type="integer", default="1", description="是否开启，1表示开启，0表示关闭"),
     *                 @SWG\Property(property="is_required", type="integer", default="1", description="是否必填项，1表示必填，0表示非必填"),
     *                 @SWG\Property(property="is_edit", type="integer", default="1", description="是否可以修改，1表示可修改，0表示不可修改"),
     *                 @SWG\Property(property="field_type", type="integer", default="1", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】"),
     *                 @SWG\Property(property="alert_required_message", type="string", default="手机号必填", description="提示信息，该请求字段在验证出错时需要返回出去的错误信息"),
     *                 @SWG\Property(property="alert_validate_message", type="string", default="", description="提示信息，基于validate_condition中验证出错时需要被返回去的错误信息，如果不存在就取alert_required_message的内容"),
     *                 @SWG\Property(property="is_must_start_required", type="integer", default="", description="是否是必须开启和必填, 1为必须开启且必填, 0为可以手动开启关闭"),
     *                 @SWG\Property(property="created", type="string", default="1619427490", description="创建时间（时间戳）"),
     *                 @SWG\Property(property="updated", type="string", default="1619430699", description="更新时间（时间戳）"),
     *                 @SWG\Property(property="created_desc", type="string", default="2021-04-26 16:58:10", description="创建时间（Y-m-d H:i:s）"),
     *                 @SWG\Property(property="updated_desc", type="string", default="2021-04-26 17:51:39", description="更新时间（Y-m-d H:i:s）"),
     *                 @SWG\Property(property="module_type_desc", type="string", default="会员个人信息", description="module_type字段的中文描述"),
     *                 @SWG\Property(property="field_type_desc", type="string", default="单选项", description="field_type字段的中文描述"),
     *                 @SWG\Property(property="validate_condition", type="array", description="验证规则的原数据内容",
     *                     @SWG\Items(type="object", required={"value", "label", "is_checked"},
     *                         @SWG\Property(property="value", type="string", default="1", description="这条规则的值，如果是取值范围则是用用逗号来拼接，比如0,999就是最小值为0, 最大值为999"),
     *                         @SWG\Property(property="label", type="string", default="字段1", description="这条规则的描述"),
     *                         @SWG\Property(property="is_checked", type="string", default="0", description="表示默认是否选中, 【0 不选中】【1 选中】"),
     *                     ),
     *                 ),
     *                 @SWG\Property(property="range", type="object", description="验证规则的取值范围的数组对象",required={"start","end"},
     *                     @SWG\Property(property="start", type="string", default="0", description="最小值，如果是null则不取下限"),
     *                     @SWG\Property(property="end", type="string", default="999", description="最大值，如果是null则不取上限"),
     *                 ),
     *                 @SWG\Property(property="radio_list", type="array", description="验证规则的单选项列表的数组对象",
     *                     @SWG\Items(type="object", required={"value", "label"},
     *                         description="value为该选项的值，label为该选项的描述",
     *                         required={"value", "label", "is_checked"},
     *                         @SWG\Property(property="label", type="string", default="未知", description="描述"),
     *                         @SWG\Property(property="is_checked", type="string", default="0", description="表示默认是否选中, 【0 不选中】【1 选中】"),
     *                     ),
     *                 ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function updateInfo(Request $request)
    {
        // 获取用户信息
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);
        // 获取主键id
        $id = (int)$request->input("id");
        // 表单参数验证
        $formData = $request->input();
        $error = validator_params($formData, [
            "label" => ["required|max:50", "字段信息必填且长度不能超过50个字符！"],
            "field_type" => ["required|" . Rule::in(array_keys(ConfigRequestFieldsService::FIELD_TYPE_MAP)), "字段信息格式有误！"],
            "alert_required_message" => ["required", "提示信息必填"],
        ]);
        if ($error) {
            throw new ResourceException($error);
        }
        // 更新内容
        $data = $this->service->updateInfo($companyId, $id, $formData);
        return $this->response->array($data);
    }

    /**
     * @SWG\Delete(
     *     path="api/espier/config/request_fields",
     *     tags={"系统"},
     *     summary="配置请求字段 - 删除字段 - 后台",
     *     description="配置请求字段 - 删除字段 - 后台",
     *     operationId="delete",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string", default=""),
     *     @SWG\Parameter(name="id", in="query", description="主键id", required=true, type="integer", default="1"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"status"},
     *               @SWG\Property(property="status", type="boolean", default="true", description="更新的状态"),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function delete(Request $request)
    {
        // 获取用户信息
        $authData = app('auth')->user()->get();
        $companyId = (int)($authData["company_id"] ?? 0);
        // 获取主键id
        $id = (int)$request->input("id");
        // 删除内容
        $this->service->delete($companyId, $id);
        return $this->response->array(["status" => true]);
    }
}
