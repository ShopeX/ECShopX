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

namespace SelfserviceBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

use SelfserviceBundle\Services\UserDailyRecordService;
use SelfserviceBundle\Traits\GetFormSettingTemp;

class FormTemplateController extends Controller
{
    use GetFormSettingTemp;
    public $limit;

    /**
     * @SWG\Get(
     *     path="/h5app/wxapp/selfform/statisticalAnalysis",
     *     summary="获取指定时间段内的数据统计数据",
     *     tags={"报名"},
     *     description="获取指定时间段内的数据统计数据",
     *     operationId="statisticalAnalysis",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="days", in="query", description="天数", required=true, type="integer"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function statisticalAnalysis(Request $request)
    {
        $params = $request->all('days', 'user_id', 'timeChoosed', 'form_type', 'shop_id');

        $orderBy = ['record_date' => 'DESC', 'id' => 'DESC'];
        $page = 1;
        $size = $request->get('days') ?: 5;
        $filter['record_date|lte'] = date('Ymd');
        if ($request->get('timeChoosed') != 'undefined') {
            $filter['record_date|lte'] = $request->get('timeChoosed') ?: date('Ymd');
        }
        $authInfo = $request->get('auth');
        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $request->get('user_id') ?: ($authInfo['user_id'] ?? 0);
        if (!$filter['user_id']) {
            return [];
        }
        if (intval($size) > 10) {
            throw new ResourceException(trans('SelfserviceBundle.only_get_statistics_within_10_days'));
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
     *     path="/h5app/wxapp/selfform/physical/datelist",
     *     summary="获取所有记录的日期列表",
     *     tags={"报名"},
     *     description="获取所有记录的日期列表",
     *     operationId="getRecordDateList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="单页数量", required=true, type="integer"),
     *     @SWG\Parameter( name="form_type", in="query", description="表单类型(默认为physical:体测报告表单)", required=true, type="string"),
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SelfserviceErrorRespones") ) )
     * )
     */
    public function getRecordDateList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'form_type');

        $userDailyRecordService = new UserDailyRecordService();
        $authInfo = $request->get('auth');
        $filter['user_id'] = $authInfo['user_id'] ?? 0;

        if (!$filter['user_id']) {
            $result['list'] = [];
            $result['total_count'] = 0;
            return $this->response->array($result);
        }

        $filter['company_id'] = $authInfo['company_id'];
        $formType = $request->get('form_type', 'physical');
        $filter['temp_id'] = $this->getTempId($filter['company_id'], $formType);
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
