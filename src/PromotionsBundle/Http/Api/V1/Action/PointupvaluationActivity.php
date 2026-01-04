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

namespace PromotionsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\PointupvaluationActivityService;

class PointupvaluationActivity extends Controller
{
    public function __construct()
    {
        $this->service = new PointupvaluationActivityService();
    }
    /**
     * @SWG\Definition(
     * definition="PointupvaluationList",
     * type="object",
     * @SWG\Property( property="activity_id", type="string", example="3", description="活动ID"),
     * @SWG\Property( property="title", type="string", example="测试1", description="活动名称"),
     * @SWG\Property( property="begin_time", type="string", example="1611158400", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611331199", description="活动结束时间"),
     * @SWG\Property( property="trigger_condition", type="object",
     * @SWG\Property( property="trigger_time", type="object", description="日期数据",
     *     @SWG\Property( property="type", type="string", example="every_week", description="类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段"),
     *     @SWG\Property( property="month", type="string", example="", description="月份"),
     *     @SWG\Property( property="week", type="string", example="4", description="星期值"),
     *     @SWG\Property( property="day", type="string", example="", description="日期"),
     *     @SWG\Property( property="begin_time", type="string", example="", description="开始时间"),
     *     @SWG\Property( property="end_time", type="string", example="", description="结束时间"),
     *     ),
     * ),
     * @SWG\Property( property="upvaluation", type="string", example="3", description="升值倍数"),
     * @SWG\Property( property="max_up_point", type="string", example="10000", description="每日升值积分上限（商家补贴积分上限）"),
     * @SWG\Property( property="valid_grade", type="array",
     *     @SWG\Items( type="string", example="4", description="会员等级id"),
     * ),
     * @SWG\Property( property="used_scene", type="array",
     *     @SWG\Items( type="string", example="1", description="应用场景 1:订单抵扣"),
     * ),
     * @SWG\Property( property="created", type="string", example="1611216662", description=""),
     * @SWG\Property( property="updated", type="string", example="1611216662", description=""),
     * @SWG\Property( property="begin_date", type="string", example="2021-01-21", description="有效期开始时间"),
     * @SWG\Property( property="end_date", type="string", example="2021-01-22", description="有效期结束时间"),
     * @SWG\Property( property="activity_status", type="string", example="it_has_ended", description="活动状态 waiting:未开始 ongoing:进行中 it_has_ended:已结束"),
     * )
     */

    /**
     * @SWG\Definition(
     * definition="PointupvaluationDetail",
     * type="object",
     * @SWG\Property( property="activity_id", type="string", example="3", description="活动ID"),
     * @SWG\Property( property="title", type="string", example="测试1", description="活动名称"),
     * @SWG\Property( property="begin_time", type="string", example="1611158400", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611331199", description="活动结束时间"),
     * @SWG\Property( property="trigger_condition", type="object",
     * @SWG\Property( property="trigger_time", type="object", description="日期数据",
     *     @SWG\Property( property="type", type="string", example="every_week", description="类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段"),
     *     @SWG\Property( property="month", type="string", example="", description="月份"),
     *     @SWG\Property( property="week", type="string", example="4", description="星期值"),
     *     @SWG\Property( property="day", type="string", example="", description="日期"),
     *     @SWG\Property( property="begin_time", type="string", example="", description="开始时间"),
     *     @SWG\Property( property="end_time", type="string", example="", description="结束时间"),
     *     ),
     * ),
     * @SWG\Property( property="upvaluation", type="string", example="3", description="升值倍数"),
     * @SWG\Property( property="max_up_point", type="string", example="10000", description="每日升值积分上限（商家补贴积分上限）"),
     * @SWG\Property( property="valid_grade", type="array",
     *     @SWG\Items( type="string", example="4", description="会员等级id"),
     * ),
     * @SWG\Property( property="used_scene", type="array",
     *     @SWG\Items( type="string", example="1", description="应用场景 1:订单抵扣"),
     * ),
     * @SWG\Property( property="created", type="string", example="1611216662", description=""),
     * @SWG\Property( property="updated", type="string", example="1611216662", description=""),
     * @SWG\Property( property="begin_date", type="string", example="2021-01-21", description="有效期开始时间"),
     * @SWG\Property( property="end_date", type="string", example="2021-01-22", description="有效期结束时间"),
     * )
     */

    /**
     * @SWG\Post(
     *     path="/promotions/pointupvaluation/create",
     *     summary="创建积分升值活动",
     *     tags={"营销"},
     *     description="创建积分升值活动",
     *     operationId="createActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", type="string"),
     *     @SWG\Parameter( name="begin_time", in="formData", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="活动结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="is_forever", in="formData", description="是否永久有效", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][type]", in="formData", description="日期数据,类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段", type="string", required=true),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][month]", in="formData", description="日期数据,月份", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][week]", in="formData", description="日期数据,星期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][day]", in="formData", description="日期数据,日期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][begin_time]", in="formData", description="日期数据,开始时间", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][end_time]", in="formData", description="日期数据,结束时间", type="string", required=false),
     *     @SWG\Parameter( name="upvaluation", in="formData", description="升值倍数", required=true, type="integer"),
     *     @SWG\Parameter( name="max_up_point", in="formData", description="每日最大升值积分", required=true, type="integer"),
     *     @SWG\Parameter( name="valid_grade", in="formData", description="会员等级集合数组", type="string"),
     *     @SWG\Parameter( name="used_scene", in="formData", description="适用场景数组", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 ref="#/definitions/PointupvaluationDetail"
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        $rules = [
            'title' => ['required', trans('PromotionsBundle.activity_title_required')],
            'begin_time' => ['required_if:is_forever,false', trans('PromotionsBundle.activity_begin_time_required')],
            'end_time' => ['required_if:is_forever,false', trans('PromotionsBundle.activity_end_time_required')],
            'company_id' => ['required', trans('PromotionsBundle.company_id_required')],
            'trigger_condition' => ['required', trans('PromotionsBundle.date_required')],
            'upvaluation' => ['required', trans('PromotionsBundle.upvaluation_required')],
            'max_up_point' => ['required', trans('PromotionsBundle.max_up_point_required')],
            'used_scene' => ['required', trans('PromotionsBundle.used_scene_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = $this->service->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/pointupvaluation/updatestatus",
     *     summary="作废积分升值活动",
     *     tags={"营销"},
     *     description="更新积分升值活动状态为作废",
     *     operationId="updateStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function updateStatus(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $result = $this->service->endActivity($authUser['company_id'], $request->input('activity_id'));
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/pointupvaluation/update",
     *     summary="编辑积分升值活动",
     *     tags={"营销"},
     *     description="编辑积分升值活动",
     *     operationId="updateStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="formData", description="活动名称", type="string"),
     *     @SWG\Parameter( name="begin_time", in="formData", description="活动开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="formData", description="活动结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="is_forever", in="formData", description="是否永久有效", required=true, type="string"),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][type]", in="formData", description="日期数据,类型 every_year:每年，every_month:每月，every_week:每周，date:指定时间段", type="string", required=true),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][month]", in="formData", description="日期数据,月份", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][week]", in="formData", description="日期数据,星期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][day]", in="formData", description="日期数据,日期", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][begin_time]", in="formData", description="日期数据,开始时间", type="string", required=false),
     *     @SWG\Parameter( name="trigger_condition[trigger_time][end_time]", in="formData", description="日期数据,结束时间", type="string", required=false),
     *     @SWG\Parameter( name="upvaluation", in="formData", description="升值倍数", required=true, type="integer"),
     *     @SWG\Parameter( name="max_up_point", in="formData", description="每日最大升值积分", required=true, type="integer"),
     *     @SWG\Parameter( name="valid_grade", in="formData", description="会员等级集合数组", type="string"),
     *     @SWG\Parameter( name="used_scene", in="formData", description="适用场景数组", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                 ref="#/definitions/PointupvaluationDetail"
     *             )
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updateActivity(Request $request)
    {
        // XXX: review this code
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        $rules = [
            'activity_id' => ['required', trans('PromotionsBundle.activity_id_required')],
            'title' => ['required', trans('PromotionsBundle.activity_title_required')],
            'begin_time' => ['required_if:is_forever,false', trans('PromotionsBundle.activity_begin_time_required')],
            'end_time' => ['required_if:is_forever,false', trans('PromotionsBundle.activity_end_time_required')],
            'company_id' => ['required', trans('PromotionsBundle.company_id_required')],
            'trigger_condition' => ['required', trans('PromotionsBundle.date_required')],
            'upvaluation' => ['required', trans('PromotionsBundle.upvaluation_required')],
            'max_up_point' => ['required', trans('PromotionsBundle.max_up_point_required')],
            'used_scene' => ['required', trans('PromotionsBundle.used_scene_required')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter['company_id'] = $authUser['company_id'];
        $filter['activity_id'] = $params['activity_id'];
        $result = $this->service->updateActivity($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/pointupvaluation/getinfo",
     *     summary="获取积分升值活动详情",
     *     tags={"营销"},
     *     description="获取积分升值活动详情",
     *     operationId="getActivityInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/PointupvaluationDetail"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityInfo(Request $request)
    {
        // XXX: review this code
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['activity_id'] = $request->input('activity_id');
        $result = $this->service->getActivityInfo($filter);

        $result['begin_date'] = date('Y-m-d H:i:s', $result['begin_time']);
        $result['end_date'] = date('Y-m-d H:i:s', $result['end_time']);
        $trigger_time = $result['trigger_condition']['trigger_time'];
        if ($trigger_time['begin_time'] && $trigger_time['end_time']) {
            $trigger_time['begin_date'] = date('Y-m-d', $trigger_time['begin_time']);
            $trigger_time['end_date'] = date('Y-m-d', $trigger_time['end_time']);
        } else {
            $trigger_time['begin_date'] = '';
            $trigger_time['end_date'] = '';
        }
        $result['valid_grade'] = $result['valid_grade'] ?? [];
        $result['trigger_condition']['trigger_time'] = $trigger_time;
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/pointupvaluation/lists",
     *     summary="获取积分升值活动列表",
     *     tags={"营销"},
     *     description="获取积分升值活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="title", in="query", description="活动名称", type="string"),
     *     @SWG\Parameter( name="begin_time", in="query", description="活动开始时间", required=false, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=false, type="string"),
     *     @SWG\Parameter( name="activity_status", in="query", description="活动状态 0:全部 waiting:未开始 ongoing:进行中 it_has_ended:已结束", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/PointupvaluationList"
     *                       ),
     *                  ),
     *          ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];

        $input = $request->all('title', 'activity_status', 'begin_time', 'end_time');

        if ($input['title']) {
            $filter['title|contains'] = $input['title'];
        }
        if ($input['activity_status']) {
            switch ($input['activity_status']) {
                case "waiting":// 未开始
                    $filter['begin_time|gte'] = time();
                    $filter['end_time|gte'] = time();
                    break;
                case "ongoing":// 进行中
                    $filter['begin_time|lte'] = time();
                    $filter['end_time|gt'] = time();
                    break;
                case "it_has_ended":// 已结束
                    $filter['end_time|lte'] = time();
                    break;
            }
        }

        if (isset($input['begin_time'], $input['end_time']) && $input['begin_time'] && $input['end_time']) {
            $filter['begin_time|gte'] = $input['begin_time'];
            $filter['end_time|lte'] = $input['end_time'];
        }


        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['activity_id' => 'desc'];

        $result = $this->service->lists($filter, '*', $page, $pageSize, $orderBy);
        if ($result['total_count'] > 0 && $result['list']) {
            foreach ($result['list'] as $key => $row) {
                $row['begin_date'] = date('Y-m-d', $row['begin_time']);
                $row['end_date'] = date('Y-m-d', $row['end_time']);
                if ($row['begin_time'] >= time() && $row['end_time'] >= time()) {
                    $row['activity_status'] = 'waiting';
                } elseif ($row['begin_time'] <= time() && $row['end_time'] > time()) {
                    $row['activity_status'] = 'ongoing';
                } else {
                    $row['activity_status'] = 'it_has_ended';
                }
                $result['list'][$key] = $row;
            }
        }
        return $this->response->array($result);
    }
}
