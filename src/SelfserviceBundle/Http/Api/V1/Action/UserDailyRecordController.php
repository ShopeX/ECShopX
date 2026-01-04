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

namespace SelfserviceBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\StoreResourceFailedException;

use SelfserviceBundle\Services\FormTemplateService;
use SelfserviceBundle\Services\UserDailyRecordService;
use SelfserviceBundle\Traits\GetFormSettingTemp;

class UserDailyRecordController extends Controller
{
    use GetFormSettingTemp;
    public $service;
    public $limit;

    public function __construct()
    {
        $this->service = new UserDailyRecordService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/selfhelp/setting/physical",
     *     summary="配置体测表单",
     *     tags={"报名"},
     *     description="配置体测表单",
     *     operationId="settingPhysical",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="temp_id", in="query", description="表单模板ID", required=true, type="integer"),
     *     @SWG\Parameter( name="status", in="query", description="状态(0, 1)", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="temp_id", type="string", example="21", description="模板id"),
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="temp_name", type="string", example="一普体测表单", description="模板名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function settingPhysical(Request $request)
    {
        $params['temp_id'] = $request->get('temp_id', 0);
        $params['status'] = $request->get('status', 0);
        if ($params['status'] && !$params['temp_id']) {
            throw new StoreResourceFailedException(trans('SelfserviceBundle.please_select_form_template'));
        }
        $companyId = app('auth')->user()->get('company_id');
        $key = 'settingPhysical:'.$companyId;
        app('redis')->connection('companys')->set($key, json_encode($params));

        if ($params['status'] && ($params['temp_id'] ?? 0)) {
            $service = new FormTemplateService();
            $temp = $service->getInfo(['company_id' => $companyId, 'id' => $params['temp_id']]);
            $params['temp_name'] = $temp['tem_name'];
        }
        return $this->response->array($params);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/setting/physical",
     *     summary="获取体测表单配置",
     *     tags={"报名"},
     *     description="获取体测表单配置",
     *     operationId="getSettingPhysical",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="temp_id", type="string", example="21", description="模板id"),
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  @SWG\Property( property="temp_name", type="string", example="一普体测表单", description="模板名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getSettingPhysical(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $key = 'settingPhysical:'.$companyId;
        $result = app('redis')->connection('companys')->get($key);
        $result = $result ? json_decode($result, true) : ['status' => 0, 'temp_id' => 0];
        if ($result['temp_id'] ?? 0) {
            $service = new FormTemplateService();
            $temp = $service->getInfo(['company_id' => $companyId, 'id' => $result['temp_id']]);
            $result['temp_name'] = $temp['tem_name'];
        }
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/selfhelp/physical/alluserlist",
     *     summary="获取所有会员体测数据（最近一次的记录）",
     *     tags={"报名"},
     *     description="获取所有会员体测数据（最近一次的记录）",
     *     operationId="getAllUserList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="path",
     *         description="会员手机号",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="username",
     *         in="path",
     *         description="会员用户名",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="form_type",
     *         in="path",
     *         description="表单类型(默认为physical:体测报告表单)",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="path",
     *         description="页码",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="path",
     *         description="单页数量",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="30", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="112222", description="用户id "),
     *                          @SWG\Property( property="record_date", type="string", example="20210202", description="记录提交日期"),
     *                          @SWG\Property( property="shop_id", type="string", example="null", description="店铺id"),
     *                          @SWG\Property( property="created", type="string", example="1612251385", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="1612251385", description="修改时间"),
     *                          @SWG\Property( property="operator_id", type="string", example="45", description="操作者id"),
     *                          @SWG\Property( property="operator", type="string", example="刘", description="操作员名称或手机"),
     *                          @SWG\Property( property="temp_id", type="string", example="21", description="表单模板id "),
     *                          @SWG\Property( property="mobile", type="string", example="", description="用户手机号"),
     *                          @SWG\Property( property="username", type="string", example="", description="姓名 |"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="6", description="总数"),
     *                  @SWG\Property( property="colstitle", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="prop", type="string", example="zhibiao3", description="prop"),
     *                          @SWG\Property( property="label", type="string", example="指标3", description="标签名"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getAllUserList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $formType = $request->get('form_type', 'physical');
        $tempId = $this->getTempId($companyId, $formType);
        $filter = [
            'company_id' => $companyId,
            'temp_id' => $tempId,
        ];
        if ($request->get('mobile')) {
            $filter['mobile'] = $request->get('mobile');
        }
        if ($request->get('username')) {
            $filter['username'] = $request->get('username');
        }
        $result = $this->service->getAllUserStatisticalAnalysis($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/physical/userdata",
     *     summary="获取指定会员最近5次的记录",
     *     tags={"报名"},
     *     description="获取指定会员最近5次的记录",
     *     operationId="getUserPersonalRecord",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="day", in="query", description="天数", required=true, type="integer"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户ID", required=true, type="integer"),
     *     @SWG\Parameter( name="timeChoosed", in="query", description="日期(Ymd)", required=true, type="integer"),
     *     @SWG\Parameter( name="form_type", in="query", description="表单类型(physical)", required=false, type="string"),
     *     @SWG\Parameter( name="shop_id", in="query", description="店铺ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="fieldname", type="string", example="指标3", description="字段名"),
     *                          @SWG\Property( property="fieldkey", type="string", example="zhibiao3", description="字段Key"),
     *                          @SWG\Property( property="fieldvalue", type="array",
     *                              @SWG\Items( type="string", example="15", description="字段值"),
     *                          ),
     *                          @SWG\Property( property="thisweek", type="string", example="15", description="本周数据"),
     *                          @SWG\Property( property="lastweek", type="string", example="1", description="上周数据"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="keyindex", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="fieldvalue", type="array",
     *                              @SWG\Items( type="string", example="15", description="字段值"),
     *                          ),
     *                          @SWG\Property( property="fieldname", type="string", example="指标3", description="字段名"),
     *                          @SWG\Property( property="fieldkey", type="string", example="zhibiao3", description="字段Key"),
     *                          @SWG\Property( property="thisweek", type="string", example="15", description="本周数据"),
     *                          @SWG\Property( property="lastweek", type="string", example="1", description="上周数据"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorResponse") ) )
     * )
     */
    public function getUserPersonalRecord(Request $request)
    {
        $params = $request->all('day', 'user_id', 'timeChoosed', 'form_type', 'shop_id');

        $pageSize = $request->get('day', 5);
        $orderBy = ['record_date' => 'DESC', 'id' => 'DESC'];
        $page = 1;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['user_id'] = $request->get('user_id');
        $filter['record_date|lte'] = date('Ymd');
        if ($request->get('timeChoosed') != 'undefined') {
            $filter['record_date|lte'] = $request->get('timeChoosed') ?: date('Ymd');
        }
        $formType = $request->get('form_type', 'physical');
        $filter['temp_id'] = $this->getTempId($filter['company_id'], $formType);
        if ($request->get('shop_id')) {
            $filter['shop_id'] = $request->get('shop_id');
        }
        $result = $this->service->getStatisticalAnalysis($filter, $orderBy, $pageSize, $page);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/selfhelp/physical/datelist",
     *     summary="获取所有记录的日期列表",
     *     tags={"报名"},
     *     description="获取所有记录的日期列表",
     *     operationId="getRecordDateList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="form_type", in="query", description="表单类型(默认为physical:体测报告表单)", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="单页数量", required=true, type="integer"),
     *     @SWG\Parameter( name="user_id", in="query", description="用户ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="record_date", type="string", example="20210202", description="记录提交日期"),
     *                          @SWG\Property( property="record_date_str", type="string", example="2021-02-02", description="日期"),
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
        $params = $request->all('form_type', 'user_id', 'page', 'pageSize');
        $userDailyRecordService = new UserDailyRecordService();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $formType = $request->get('form_type', 'physical');
        $filter['temp_id'] = $this->getTempId($filter['company_id'], $formType);
        if ($request->get('user_id')) {
            $filter['user_id'] = $request->get('user_id');
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 100);
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
