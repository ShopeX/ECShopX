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

namespace CommunityBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityActivityService;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;

class CommunityActivity extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/confirm_delivery/{activity_id}",
     *     summary="确认发货",
     *     tags={"社区团管理端"},
     *     description="确认发货",
     *     operationId="confirmDeliveryStatus",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function confirmDeliveryStatus(Request $request, $activity_id)
    {
        $companyId            = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        $operator_type = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operator_type == 'distributor') { //店铺端
            $distributor_id = $request->get('distributor_id');
            $filter['distributor_id'] = $distributor_id;
        }

        $filter['activity_id'] = $activity_id;

        $service = new CommunityActivityService();
        $result = $service->updateConfirmStatus($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Definition(
     *     definition="getList",
     *         @SWG\Property(property="activity_id", type="integer", example="1",description="活动ID"),
     *         @SWG\Property(property="chief_id", type="integer", example="1",description="团长ID"),
     *         @SWG\Property(property="activity_name", type="string", example="123",description="活动名称"),
     *         @SWG\Property(property="start_time", type="string", example="1",description="开始时间"),
     *         @SWG\Property(property="end_time", type="string", example="1",description="结束时间"),
     *         @SWG\Property(property="activity_status", type="string", example="1",description="活动状态"),
     *         @SWG\Property(property="activity_status_msg", type="string", example="1",description="活动状态中文"),
     *         @SWG\Property(property="activity_process", type="string", example="1",description="活动进程"),
     *         @SWG\Property(property="activity_process_msg", type="string", example="1",description="活动进程中文"),
     * )
     */
    /**
     * @SWG\Get(
     *     path="/wxapp/community/list",
     *     summary="获取活动管理列表",
     *     tags={"社区团管理端"},
     *     description="获取活动管理列表",
     *     operationId="getList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页面,获取门店列表的初始偏移位置，从1开始计数",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_begin",
     *         in="query",
     *         description="查询开始时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="time_start_end",
     *         in="query",
     *         description="查询结束时间",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="distributor_id",
     *         in="query",
     *         description="店铺id",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="activity_name",
     *         in="query",
     *         description="活动名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="activity_status", in="query", description="活动状态 waiting未开始 ongoing进行中 end已结束", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          ref="#/definitions/getList"
     *                      )
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getList(Request $request)
    {
        $validator = app('validator')->make($request->all(), [
            'page'     => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:50',
        ]);
        $companyId            = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;
        $operator_type = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operator_type == 'distributor') { //店铺端
            $distributor_id = $request->get('distributor_id');
            $filter['distributor_id'] = $distributor_id;
        }
        $page  = $request->input('page', 1);
        $limit = $request->input('pageSize', 20);

        if ($request->input('time_start_begin')) {
            $timeStart = $request->input('time_start_begin');
            $timEnd    = $request->input('time_start_end');
            if (false !== strpos($timeStart, '-')) {
                $timeStart = strtotime($timeStart . ' 00:00:00');
                $timEnd    = strtotime($timEnd . ' 23:59:59');
            }
            $filter['created_at|gte'] = $timeStart;
            $filter['created_at|lte'] = $timEnd;
        }

        $activityStatus = $request->input('activity_status');
        if ($activityStatus) {
            switch ($activityStatus) {
                case "waiting":
                    $filter['start_time|gte'] = time();
                    $filter['end_time|gte']   = time();
                    break;
                case "ongoing":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|gte']   = time();
                    break;
                case "end":
                    $filter['start_time|lte'] = time();
                    $filter['end_time|lte']   = time();
                    break;
            }
        }

        if ($request->input('is_success')) {
            $filter['activity_status'] = 'success';
        }

        if ($request->input('activity_name')) {
            $filter['activity_name|contains'] = $request->input('activity_name');
        }

        $orderBy = ['activity_id' => 'desc'];

        $service = new CommunityActivityService();
        $lists   = $service->getActivityList($filter, $page, $limit, $orderBy);
        return $this->response->array($lists);
    }

    //发货
    public function deliver(Request $request)
    {
        $companyId            = app('auth')->user()->get('company_id');
        $filter['company_id'] = $companyId;

        $operator_type = app('auth')->user()->get('operator_type');
        $filter['distributor_id'] = 0;
        if ($operator_type == 'distributor') { //店铺端
            $distributor_id = $request->get('distributor_id');
            $filter['distributor_id'] = $distributor_id;
        }

        $filter['activity_id'] = $request->get('activity_id');

        $service = new CommunityActivityService();
        $service->deliver($filter);
        $result['status'] = true;
        return response()->json($result);
    }
}
