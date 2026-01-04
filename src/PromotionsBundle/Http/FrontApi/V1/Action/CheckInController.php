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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\CheckInService;

class CheckInController extends Controller
{
    public function __construct()
    {
        $this->service = new CheckInService();
    }

    /**
      * @SWG\Post(
      *     path="/wxapp/promotion/checkin/create",
      *     summary="记录会员签到",
      *     tags={"营销"},
      *     description="记录会员签到",
      *     operationId="createCheckIn",
      *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
      *     @SWG\Parameter( name="check_day", in="query", description="签到日期", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="id", type="integer", description="id"),
      *                 @SWG\Property(property="company_id", type="integer", example="1", description="企业ID"),
      *                 @SWG\Property(property="user_id", type="integer", example="1", description="会员id"),
      *                 @SWG\Property(property="create_time", type="integer", example="2019130714", description="签到时间"),
      *                 @SWG\Property(property="tag", type="integer", example="签到总数"),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */

    public function createCheckIn(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];
        $checkDay = date('Ymd');
        if ($request->input('check_day')) {
            $checkDay = date('Ymd', strtotime($request->input('check_day')));
        }
        $result = $this->service->createCheckIn($companyId, $userId, $checkDay);
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/wxapp/promotion/checkin/getlist",
      *     summary="获取会员签到记录列表",
      *     tags={"营销"},
      *     description="获取会员签到记录列表",
      *     operationId="getCheckInList",
      *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5app必填)", required=true, type="string"),
      *     @SWG\Parameter( name="start_date", in="query", description="开始时间", type="string"),
      *     @SWG\Parameter( name="end_date", in="query", description="结束时间", type="string"),
      *     @SWG\Parameter( name="check_type", in="query", description="查询类型：month=按月查，week=按周查，day=按天查", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="total_count", type="string", example="23", description="总条数"),
      *                 @SWG\Property(
      *                     property="list",
      *                     type="object",
      *                     @SWG\Items(
      *                         type="object",
      *                         @SWG\Property(property="id", type="integer", description="id"),
      *                         @SWG\Property(property="user_id", type="integer", example="1"),
      *                         @SWG\Property(property="create_time", type="integer", example="2019130714"),
      *                         @SWG\Property(property="tag", type="string", example="签到总数", description="标签"),
      *                         @SWG\Property(property="user_name", type="string", example="会员名称", description="会员名称"),
      *                     )
      *                 ),
      *
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构")
      * )
      */

    public function getCheckInList(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];
        $type = $request->input('check_type', 'month');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $result = $this->service->getCheckInList($companyId, $userId, $type, $startDate, $endDate);
        return $this->response->array($result);
    }
}
