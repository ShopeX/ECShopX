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

namespace WsugcBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;

use WsugcBundle\Services\FormTemplateService;
use WsugcBundle\Services\UserDailyRecordService;
use WsugcBundle\Traits\GetFormSettingTemp;

class FormTemplateController extends Controller
{
    use GetFormSettingTemp;

    public $limit;

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/selfform/list",
     *     summary="获取表单模板列表(废弃)",
     *     tags={"报名"},
     *     description="获取表单模板列表(废弃)",
     *     operationId="getDatalist",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="form_data", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="13", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="field_title", type="string", example="指标3", description="表单项标题(中文描述)"),
     *                          @SWG\Property( property="field_name", type="string", example="zhibiao3", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                          @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                          @SWG\Property( property="is_required", type="string", example="true", description="是否必填"),
     *                          @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                          @SWG\Property( property="key_index", type="string", example="true", description="表单关键指数"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getDatalist(Request $request)
    {
        $params = $request->all('page', 'page_size');
        $page = $params['page'] ? : 1;
        $size = $params['page_size'] ?: 20;
        $orderBy = ['id' => 'DESC'];
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['status'] = 1;
        $userDailyRecordService = new UserDailyRecordService();
        $result = $userDailyRecordService->getList($filter, $orderBy, $size, $page);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/selfform/tempinfo",
     *     summary="获取指定模板",
     *     tags={"报名"},
     *     description="获取指定模板",
     *     operationId="getTemplateInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="user_id", in="query", description="用户id", required=true, type="integer" ),
     *     @SWG\Parameter( name="form_type", in="query", description="表单类型(默认为physical:体测报告表单)", required=true, type="string", default="physical" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="form_data", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="13", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="field_title", type="string", example="指标3", description="表单项标题(中文描述)"),
     *                          @SWG\Property( property="field_name", type="string", example="zhibiao3", description="表单项英文名称(英文或拼音描述),唯一标示"),
     *                          @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序，数字越大越靠前"),
     *                          @SWG\Property( property="is_required", type="string", example="true", description="是否必填"),
     *                          @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                          @SWG\Property( property="options", type="string", example="null", description="表单元素为选择类时选择项（json）当form_element in (select, radio, checkbox)时，此项必填"),
     *                          @SWG\Property( property="key_index", type="string", example="true", description="表单关键指数"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getTemplateInfo(Request $request)
    {
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];

        $formType = $request->get('form_type', 'physical');

        $filter['temp_id'] = $this->getTempId($filter['company_id'], $formType);
        $filter['user_id'] = $request->input('user_id');
        //$filter['record_date'] = $request->input('record_date') ?: date('Ymd');

        $validator = app('validator')->make($filter, [
            'company_id' => 'required|integer|min:1',
            'user_id' => 'required|integer|min:1',
            'temp_id' => 'required|integer|min:1',
        ], [
            'company_id'  => '商户id必填',
            'user_id'     => '用户id必传',
            'temp_id'     => '表单id必填',
        ]);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors());
        }

        $userDailyRecordService = new UserDailyRecordService();
        $result = $userDailyRecordService->lists($filter, 1, 1, ['record_date' => 'DESC', 'id' => 'DESC']);
        $result = ($result['list'] ?? []) ? reset($result['list']) : [];
        if (!$result) {
            $formTemplateService = new FormTemplateService();
            $temp = $formTemplateService->getInfoById($filter['temp_id']);
            if ($temp['tem_type'] == 'basic_entry') {
                //$result['form_data'] = $temp['content'][0]['formdata'];
                $result['form_data'] = $temp['content'][0]['formdata'] ?? $temp['content'];
            } else {
                $result['form_data'] = $temp['content'];
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/admin/wxapp/selfform/saveuserform",
     *     summary="保存自助表单内容",
     *     tags={"报名"},
     *     description="保存自助表单内容",
     *     operationId="saveSelfFormData",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="shop_id", in="formData", description="门店id", type="string"),
     *     @SWG\Parameter( name="user_id", in="formData", description="会员id", type="string"),
     *     @SWG\Parameter( name="form_data", in="formData", description="表单数据(json)", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="30", description="ID"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司ID "),
     *                  @SWG\Property( property="user_id", type="string", example="112222", description="用户ID "),
     *                  @SWG\Property( property="operator", type="string", example="刘", description="操作员信息 "),
     *                  @SWG\Property( property="operator_id", type="string", example="45", description="操作员的id"),
     *                  @SWG\Property( property="record_date", type="string", example="20210202", description="记录提交日期"),
     *                  @SWG\Property( property="shop_id", type="string", example="null", description="门店id "),
     *                  @SWG\Property( property="temp_id", type="string", example="21", description="表单模板id "),
     *                  @SWG\Property( property="form_data", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="form_element", type="string", example="number", description="表单元素,text:文本,textarea:文本域,select:选择框,radio:单选,checkbox:多选框,date:日期选择,time:时间选择,area:地区地址选择, image:图片上传,number:纯数字"),
     *                          @SWG\Property( property="field_value", type="string", example="123", description="表单元素值"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="created", type="string", example="1612251385", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="1612251385", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function saveSelfFormData(Request $request)
    {
        $authInfo = $this->auth->user();
        $formType = $request->get('form_type', 'physical');
        $params['company_id'] = $authInfo['company_id'];
        $params['operator_id'] = $authInfo['salesperson_id'] ?? '';
        $params['operator'] = $authInfo['salesperson_name'] ?? '';
        $params['user_id'] = $request->input('user_id');
        $params['shop_id'] = $request->input('shop_id');
        $params['temp_id'] = $request->input('temp_id') ?: $this->getTempId($params['company_id'], $formType);
        $params['record_date'] = intval($request->input('record_date')) ?: date('Ymd');
        $formData = $request->input('form_data');
        if (!is_array($formData)) {
            $formData = json_decode($formData, true);
        }
        $params['form_data'] = $formData;
        $validator = app('validator')->make($params, [
            'user_id' => 'required|integer|min:1',
            'temp_id' => 'required|integer|min:1',
            'record_date' => 'required|integer',
            'company_id' => 'required|integer|min:1',
            'operator_id' => 'required|integer|min:1',
            'operator' => 'required',
            'form_data' => 'required',
        ], [
            'user_id' => '用户必选',
            'temp_id' => '表单模板必填',
            'record_date' => '日期必填',
            'company_id' => '商户id必填',
            'operator_id' => '操作员id必填',
            'operator' => '操作员必填',
            'form_data' => '表单内容必填',
        ]);

        if ($validator->fails()) {
            throw new ResourceException($validator->errors());
        }
        $userDailyRecordService = new UserDailyRecordService();
        // $filter = [
        //     'user_id' => $params['user_id'],
        //     'record_date' => $params['record_date'],
        //     'temp_id'  => $params['temp_id'],
        // ];
        // if ($userDailyRecordService->getInfo($filter)) {
        //     $result = $userDailyRecordService->updateOneBy($filter, $params);
        //     return $this->response->array($result);
        // }
        $result = $userDailyRecordService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/selfform/statisticalAnalysis",
     *     summary="获取指定时间段内的数据统计数据(废弃)",
     *     tags={"报名"},
     *     description="获取指定时间段内的数据统计数据(废弃)",
     *     operationId="statisticalAnalysis",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="days", in="query", description="天数", required=true, type="string" ),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", required=true, type="string" ),
     *     @SWG\Parameter( name="shop_id", in="query", description="门店id", required=true, type="string" ),
     *     @SWG\Parameter( name="timeChoosed", in="query", description="统计日期(Ymd)", required=true, type="string" ),
     *     @SWG\Parameter( name="form_type", in="query", description="表单类型", required=true, type="string" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function statisticalAnalysis(Request $request)
    {
        $params = $request->all('days', 'timeChoosed', 'user_id', 'shop_id', 'form_type');
        $orderBy = ['record_date' => 'DESC', 'id' => 'DESC'];
        $page = 1;
        $size = $request->get('days') ?: 5;
        $filter['record_date|lte'] = date('Ymd');
        if ($request->get('timeChoosed') != 'undefined') {
            $filter['record_date|lte'] = $request->get('timeChoosed') ?: date('Ymd');
        }
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $request->get('user_id');
        if (!$filter['user_id']) {
            return [];
        }
        if (intval($size) > 10) {
            throw new ResourceException('只能获取10天以内的统计数据');
        }
        if ($request->get('shop_id')) {
            $filter['shop_id'] = $request->get('shop_id');
        }
        $formType = $request->get('form_type', 'physical');
        $filter['temp_id'] = $this->getTempId($filter['company_id'], $formType);

        $userDailyRecordService = new UserDailyRecordService();
        $result = $userDailyRecordService->getStatisticalAnalysis($filter, $orderBy, $size, $page);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/selfform/physical/datelist",
     *     summary="获取所有记录的日期列表",
     *     tags={"报名"},
     *     description="获取所有记录的日期列表",
     *     operationId="getRecordDateList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="user_id", in="query", description="会员id", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="form_type", in="query", description="表单类型(默认为physical:体测报告表单)", required=true, type="string", default="physical" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="record_date", type="string", example="20210202", description="记录提交日期"),
     *                          @SWG\Property( property="record_date_str", type="string", example="2021-02-02", description="日期字符串"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getRecordDateList(Request $request)
    {
        $params = $request->all('user_id', 'form_type', 'page', 'page_size');
        $userDailyRecordService = new UserDailyRecordService();
        $authInfo = $this->auth->user();
        $filter['user_id'] = $request->get('user_id', 0);
        if (!$filter['user_id']) {
            $result['list'] = [];
            $result['total_count'] = 0;
            return $this->response->array($result);
        }

        $filter['company_id'] =$authInfo['company_id'];
        $formType = $request->get('form_type', 'physical');
        $filter['temp_id'] = $this->getTempId($filter['company_id'], $formType);
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', $request->get('pageSize', 20));
        $result = $userDailyRecordService->getRecordDateListGroupByRecorddate($filter, $page, $pageSize);
        foreach ($result['list'] as $v) {
            $datelist[] = [
                'record_date' => $v['record_date'],
                'record_date_str' => date('Y-m-d', strtotime($v['record_date'])),
            ];
        }
        $result['list'] = $datelist ?? [];
        return $this->response->array($result);
    }

}
