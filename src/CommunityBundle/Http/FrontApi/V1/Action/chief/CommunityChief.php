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

namespace CommunityBundle\Http\FrontApi\V1\Action\chief;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityChiefService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use EspierBundle\Services\Config\ConfigRequestFieldsService;
use EspierBundle\Services\Config\ValidatorService;
use CommunityBundle\Services\CommunityChiefApplyInfoService;
use CommunityBundle\Services\CommunitySettingService;

class CommunityChief extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/aggrement_and_explanation",
     *     summary="获取团长注册协议及申请说明",
     *     tags={"社区团"},
     *     description="获取团长注册协议及申请说明",
     *     operationId="getAggrementAndExplanation",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="aggrement", type="string", description="注册协议"),
     *                     @SWG\Property(property="explanation", type="string", description="申请说明"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构",@SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse")) )
     * )
     */
    public function getAggrementAndExplanation(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $distributorId = $request->input('distributor_id', 0);
        $settingService = new CommunitySettingService($companyId, $distributorId);
        $setting = $settingService->getSetting();
        $result['aggrement'] = $setting['aggrement'];
        $result['explanation'] = $setting['explanation'];

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/apply_fields",
     *     tags={"社区团"},
     *     summary="获取申请信息",
     *     description="获取申请信息",
     *     operationId="getApplyFields",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"data"},
     *            @SWG\Property(property="data", type="object", description="",
     *              @SWG\Property(property="field_name", type="object", description="",
     *                   @SWG\Property(property="name", type="string", default="年收入", description="展示给用户看的字段内容"),
     *                   @SWG\Property(property="key", type="string", default="income", description="前后端交互的key"),
     *                   @SWG\Property(property="is_open", type="string", default="1", description="是否开启, [0 关闭] [1 开启]"),
     *                   @SWG\Property(property="is_required", type="string", default="", description="是否必填, [0 关闭] [1 开启]"),
     *                   @SWG\Property(property="is_edit", type="string", default="", description="是否可编辑, [0 关闭] [1 开启]"),
     *                   @SWG\Property(property="element_type", type="string", default="select", description="元素类型 [input 文本框] [numeric 数字] [date 日期] [select 下拉框] [checkbox 复选框]"),
     *                   @SWG\Property(property="field_type", type="integer", default="4", description="字段类型【1 文本】【2 数字】【3 日期】【4 单选项】【5 复选框】"),
     *                   @SWG\Property(property="range", type="array", description="数字或日期类型的范围，是一个数组对象",
     *                     @SWG\Items(required={"start","end"},
     *                           @SWG\Property(property="start", type="string", default="0", description="最小值，如果是null则无下限"),
     *                           @SWG\Property(property="end", type="string", default="999", description="最大值，如果是null则无上限"),
     *                     ),
     *                   ),
     *                   @SWG\Property(property="select", type="array", description="单选项的下拉框描述，是一个数组",
     *                       @SWG\Items(required={"value1","value2"},
     *                           @SWG\Property(property="value1", type="string", default="", description="下拉框描述1"),
     *                           @SWG\Property(property="value2", type="string", default="", description="下拉框描述2"),
     *                       ),
     *                   ),
     *                   @SWG\Property(property="checkbox", type="array", description="复选框的每个选项描述和是否默认选中，是一个数组对象",
     *                       @SWG\Items(type="object", required={"name", "ischecked"},
     *                           description="value为该选项的值，label为该选项的描述",
     *                           @SWG\Property(property="name", type="string", default="游戏", description="描述内容"),
     *                           @SWG\Property(property="ischecked", type="string", default="1", description="是否选中，1为选中，0为不选中"),
     *                       ),
     *                   ),
     *
     *              ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getApplyFields(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $distributorId = $request->input('distributor_id', 0);
        // 获取验证字段
        $result = (new ConfigRequestFieldsService())->getListAndHandleSettingFormat($companyId, ConfigRequestFieldsService::MODULE_TYPE_CHIEF_INFO, $distributorId);
        $result = array_values($result);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/apply",
     *     summary="提交申请",
     *     tags={"社区团"},
     *     description="提交申请",
     *     operationId="apply",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Parameter( name="chief_name", in="query", description="姓名", required=true, type="string"),
     *     @SWG\Parameter( name="chief_mobile", in="query", description="手机号", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="apply_id", type="integer", example="", description="申请id"),
     *                 @SWG\Property( property="chief_name", type="string", example="", description="姓名"),
     *                 @SWG\Property( property="chief_mobile", type="string", example="", description="手机号"),
     *             ),
     *          ),
     *     ),
     * )
     */
    public function apply(Request $request)
    {
        $authInfo = $request->get('auth');
        // 公司id
        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];
        $distributorId = $request->input('distributor_id', 0);

        $chiefApplyInfoService = new CommunityChiefApplyInfoService();
        $exist = $chiefApplyInfoService->count(['company_id' => $companyId, 'user_id' => $userId, 'distributor_id' => $distributorId, 'approve_status|neq' => 2]);
        if ($exist) {
            throw new ResourceException('不能重复申请');
        }

        $chiefService = new CommunityChiefService();
        $exist = $chiefService->count(['company_id' => $companyId, 'user_id' => $userId]);
        if ($exist) {
            throw new ResourceException('已经是团长，不能再申请');
        }

        $params = $request->all();
        // 参数转换
        (new ConfigRequestFieldsService())->transformGetValueByDesc($companyId, ConfigRequestFieldsService::MODULE_TYPE_CHIEF_INFO, $params, $distributorId);
        // 验证规则
        $configRequestService = new ValidatorService();
        $applyFields = $configRequestService->check($companyId, ConfigRequestFieldsService::MODULE_TYPE_CHIEF_INFO, $params, true, $distributorId);
        // 用户自定义的数据
        $extraData = [];
        foreach ($applyFields as $key => $applyFiled) {
            $isDefault = (bool)($applyFiled['is_default'] ?? false);
            if ($isDefault || !isset($params[$key])) {
                continue;
            }
            $extraData[$key] = [
                'label' => $applyFiled['name'],
                'value' => $params[$key],
                'type' => $applyFiled['field_type'],
            ];
        }
        $params['extra_data'] = $extraData;
        $params['company_id'] = $companyId;
        $params['user_id'] = $userId;
        $params['distributor_id'] = $distributorId;
        $params['chief_mobile'] = $authInfo['mobile'];
        $result = $chiefApplyInfoService->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/apply",
     *     summary="团长申请详情",
     *     tags={"社区团"},
     *     description="团长申请详情",
     *     operationId="getApplyInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_id", in="query", description="店铺id", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getApplyInfo(Request $request) {
        $authInfo = $request->get('auth');
        // 公司id
        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];
        $distributorId = $request->input('distributor_id', 0);

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $distributorId,
            'user_id' => $userId,
        ];
        $chiefApplyInfoService = new CommunityChiefApplyInfoService();
        $result = $chiefApplyInfoService->getLists($filter, '*', 1, 1, ['created_at' => 'DESC']);
        if (!$result) {
            $result = [['approve_status' => -1]];
        }

        return $this->response->array(current($result));
    }

    /**
     * @SWG\Definition(
     *     definition="CommunityChief",
     *         @SWG\Property(property="chief_id", type="integer", example="1",description="团长ID"),
     *         @SWG\Property(property="company_id", type="integer", example="1",description="company_id"),
     *         @SWG\Property(property="chief_name", type="string", example="123",description="团长名称"),
     *          @SWG\Property(property="chief_avatar", type="string", example="1",description="团长头像"),
     *          @SWG\Property(property="chief_mobile", type="string", example="1611045460",description="团长手机号"),
     *          @SWG\Property(property="chief_desc", type="string", example="desc",description="团长简介"),
     *          @SWG\Property(property="chief_intro", type="integer", example="101",description="团长详情"),
     *          @SWG\Property(property="address", type="string", example="desc",description="团长详细地址"),
     *             @SWG\Property(
     *                 property="distributors",
     *                 type="array",
     *                      @SWG\Items( type="object",required={"distributor_id","company_id","mobile","address","name","created","updated","is_valid","province","city","area","regions_id","regions","contact","child_count","shop_id","is_default","is_ziti","lng","lat","hour","auto_sync_goods","logo","banner","is_audit_goods","is_delivery","shop_code","review_status""source_from","distributor_self","is_distributor","contract_phone","is_domestic","is_direct_store","wechat_work_department_id","regionauth_id","is_open","rate","store_address","store_name","phone","distance_show","distance_unit","tagList"},
     *                          @SWG\Property(property="distributor_id", type="string", default="105", description="店铺id"),
     *                          @SWG\Property(property="company_id", type="string", default="1", description="公司id"),
     *                          @SWG\Property(property="mobile", type="string", default="17621716237", description="电话"),
     *                          @SWG\Property(property="address", type="string", default="徐汇区", description="地址"),
     *                          @SWG\Property(property="name", type="string", default="普天信息产业园", description="名称"),
     *                          @SWG\Property(property="created", type="string", default="1608012760", description="创建时间"),
     *                          @SWG\Property(property="updated", type="string", default="1610533984", description="更新时间"),
     *                          @SWG\Property(property="is_valid", type="string", default="true", description=""),
     *                          @SWG\Property(property="province", type="string", default="上海市", description="省"),
     *                          @SWG\Property(property="city", type="string", default="上海市", description="市"),
     *                          @SWG\Property(property="area", type="string", default="徐汇区", description="区县"),
     *                          @SWG\Property(property="regions_id", type="array",
     *                              @SWG\Items( type="string", default="310000", description="地区码"),
     *                          ),
     *                          @SWG\Property(property="regions", type="array",
     *                              @SWG\Items( type="string", default="上海市", description="地区名称"),
     *                          ),
     *                          @SWG\Property(property="contact", type="string", default="wuqiong ", description=""),
     *                          @SWG\Property(property="child_count", type="string", default="0", description=""),
     *                          @SWG\Property(property="shop_id", type="string", default="0", description="shop_id"),
     *                          @SWG\Property(property="is_default", type="string", default="true", description="是否默认"),
     *                          @SWG\Property(property="is_ziti", type="string", default="true", description="是否自提"),
     *                          @SWG\Property(property="lng", type="string", default="121.43687", description="经度"),
     *                          @SWG\Property(property="lat", type="string", default="31.18826", description="纬度"),
     *                          @SWG\Property(property="hour", type="string", default="08:00-21:00", description=""),
     *                          @SWG\Property(property="auto_sync_goods", type="string", default="false", description="自动同步商品"),
     *                          @SWG\Property(property="logo", type="string", default="null", description="logo"),
     *                          @SWG\Property(property="banner", type="string", default="null", description="banner"),
     *                          @SWG\Property(property="is_audit_goods", type="string", default="false", description="是否审核商品"),
     *                          @SWG\Property(property="is_delivery", type="string", default="true", description=""),
     *                          @SWG\Property(property="shop_code", type="string", default="1234567", description="shop_code"),
     *                          @SWG\Property(property="review_status", type="string", default="0", description=""),
     *                          @SWG\Property(property="source_from", type="string", default="1", description=""),
     *                          @SWG\Property(property="distributor_self", type="string", default="0", description=""),
     *                          @SWG\Property(property="is_distributor", type="string", default="true", description="是否门店"),
     *                          @SWG\Property(property="contract_phone", type="string", default="17621716237", description="电话"),
     *                          @SWG\Property(property="is_domestic", type="string", default="1", description=""),
     *                          @SWG\Property(property="is_direct_store", type="string", default="1", description="是否直营店"),
     *                          @SWG\Property(property="wechat_work_department_id", type="string", default="0", description=""),
     *                          @SWG\Property(property="regionauth_id", type="string", default="1", description=""),
     *                          @SWG\Property(property="is_open", type="string", default="false", description=""),
     *                          @SWG\Property(property="rate", type="string", default="", description=""),
     *                          @SWG\Property(property="distance", type="string", default="0", description="距离"),
     *                          @SWG\Property(property="store_address", type="string", default="上海市徐汇区徐汇区", description="地址"),
     *                          @SWG\Property(property="store_name", type="string", default="普天信息产业园", description="名称"),
     *                          @SWG\Property(property="phone", type="string", default="17621716237", description="手机号"),
     *                      ),
     *                  ),
     *             ),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/wxapp/community/checkChief",
     *     summary="检查用户是否是团长",
     *     tags={"社区团"},
     *     description="检查用户是否是团长",
     *     operationId="checkChief",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="boolean",description="是否是团长"),
     *                     @SWG\Property(property="result", type="array", @SWG\Items(
     *                         ref="#/definitions/CommunityChief"
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function checkChief(Request $request)
    {
        $authInfo = $request->get('auth');

        if (!$authInfo['user_id'] || !$authInfo['mobile']) {
            throw new ResourceException('请重新登录');
        }

        $returnData = [
            'status' => false,
            'result' => [],
        ];

        // 查询账户
        $filter = [
            'company_id' => $authInfo['company_id'],
            'user_id' => $authInfo['user_id'],
        ];
        $chiefService = new CommunityChiefService();
        $chiefData = $chiefService->getChiefInfo($filter);
        if ($chiefData) {
            $returnData = [
                'status' => true,
                'result' => $chiefData,
            ];
            return $this->response->array($returnData);
        }
        $chiefData = $chiefService->checkBindChief($authInfo['company_id'], $authInfo['user_id'], $authInfo['mobile']);
        if ($chiefData) {
            $returnData = [
                'status' => true,
                'result' => $chiefData,
            ];
        }
        return $this->response->array($returnData);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/distributor",
     *     summary="获取团长的门店列表",
     *     tags={"社区团"},
     *     description="获取团长的门店列表",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                      @SWG\Items( type="object",required={"distributor_id","company_id","mobile","address","name","created","updated","is_valid","province","city","area","regions_id","regions","contact","child_count","shop_id","is_default","is_ziti","lng","lat","hour","auto_sync_goods","logo","banner","is_audit_goods","is_delivery","shop_code","review_status""source_from","distributor_self","is_distributor","contract_phone","is_domestic","is_direct_store","wechat_work_department_id","regionauth_id","is_open","rate","store_address","store_name","phone","distance_show","distance_unit","tagList"},
     *                          @SWG\Property(property="distributor_id", type="string", default="105", description="店铺id"),
     *                          @SWG\Property(property="company_id", type="string", default="1", description="公司id"),
     *                          @SWG\Property(property="mobile", type="string", default="17621716237", description="电话"),
     *                          @SWG\Property(property="address", type="string", default="徐汇区", description="地址"),
     *                          @SWG\Property(property="name", type="string", default="普天信息产业园", description="名称"),
     *                          @SWG\Property(property="created", type="string", default="1608012760", description="创建时间"),
     *                          @SWG\Property(property="updated", type="string", default="1610533984", description="更新时间"),
     *                          @SWG\Property(property="is_valid", type="string", default="true", description=""),
     *                          @SWG\Property(property="province", type="string", default="上海市", description="省"),
     *                          @SWG\Property(property="city", type="string", default="上海市", description="市"),
     *                          @SWG\Property(property="area", type="string", default="徐汇区", description="区县"),
     *                          @SWG\Property(property="regions_id", type="array",
     *                              @SWG\Items( type="string", default="310000", description="地区码"),
     *                          ),
     *                          @SWG\Property(property="regions", type="array",
     *                              @SWG\Items( type="string", default="上海市", description="地区名称"),
     *                          ),
     *                          @SWG\Property(property="contact", type="string", default="wuqiong ", description=""),
     *                          @SWG\Property(property="child_count", type="string", default="0", description=""),
     *                          @SWG\Property(property="shop_id", type="string", default="0", description="shop_id"),
     *                          @SWG\Property(property="is_default", type="string", default="true", description="是否默认"),
     *                          @SWG\Property(property="is_ziti", type="string", default="true", description="是否自提"),
     *                          @SWG\Property(property="lng", type="string", default="121.43687", description="经度"),
     *                          @SWG\Property(property="lat", type="string", default="31.18826", description="纬度"),
     *                          @SWG\Property(property="hour", type="string", default="08:00-21:00", description=""),
     *                          @SWG\Property(property="auto_sync_goods", type="string", default="false", description="自动同步商品"),
     *                          @SWG\Property(property="logo", type="string", default="null", description="logo"),
     *                          @SWG\Property(property="banner", type="string", default="null", description="banner"),
     *                          @SWG\Property(property="is_audit_goods", type="string", default="false", description="是否审核商品"),
     *                          @SWG\Property(property="is_delivery", type="string", default="true", description=""),
     *                          @SWG\Property(property="shop_code", type="string", default="1234567", description="shop_code"),
     *                          @SWG\Property(property="review_status", type="string", default="0", description=""),
     *                          @SWG\Property(property="source_from", type="string", default="1", description=""),
     *                          @SWG\Property(property="distributor_self", type="string", default="0", description=""),
     *                          @SWG\Property(property="is_distributor", type="string", default="true", description="是否门店"),
     *                          @SWG\Property(property="contract_phone", type="string", default="17621716237", description="电话"),
     *                          @SWG\Property(property="is_domestic", type="string", default="1", description=""),
     *                          @SWG\Property(property="is_direct_store", type="string", default="1", description="是否直营店"),
     *                          @SWG\Property(property="wechat_work_department_id", type="string", default="0", description=""),
     *                          @SWG\Property(property="regionauth_id", type="string", default="1", description=""),
     *                          @SWG\Property(property="is_open", type="string", default="false", description=""),
     *                          @SWG\Property(property="rate", type="string", default="", description=""),
     *                          @SWG\Property(property="distance", type="string", default="0", description="距离"),
     *                          @SWG\Property(property="store_address", type="string", default="上海市徐汇区徐汇区", description="地址"),
     *                          @SWG\Property(property="store_name", type="string", default="普天信息产业园", description="名称"),
     *                          @SWG\Property(property="phone", type="string", default="17621716237", description="手机号"),
     *                      ),
     *                  ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getDisitrbutorList(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['user_id'] || !$authInfo['mobile']) {
            throw new ResourceException('请重新登录');
        }

        $service = new CommunityChiefService();
        $result = $service->getDistributorListByUserID($authInfo['user_id']);

        return $this->response->array($result);
    }
}
