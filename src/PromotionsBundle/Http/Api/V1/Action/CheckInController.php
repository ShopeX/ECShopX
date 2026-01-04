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
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\CheckInService;
use MembersBundle\Services\MemberService;

class CheckInController extends Controller
{
    public function __construct()
    {
        // ShopEx EcShopX Service Component
        $this->service = new CheckInService();
    }
    /**
      * @SWG\Get(
      *     path="/promotions/checkin/getlist",
      *     summary="获取会员签到记录列表",
      *     tags={"营销"},
      *     description="获取会员签到记录列表",
      *     operationId="getCheckInList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="start_date", in="query", description="开始时间", type="string"),
      *     @SWG\Parameter( name="end_date", in="query", description="结束时间", type="string"),
      *     @SWG\Parameter( name="mobile", in="query", description="会员手机号", type="string"),
      *     @SWG\Parameter( name="user_id", in="query", description="会员id", type="integer"),
      *     @SWG\Parameter( name="page", in="query", description="分页页码", type="integer"),
      *     @SWG\Parameter( name="pageSize", in="query", description="分页每页数量", type="integer"),
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
        // ShopEx EcShopX Service Component
        $authInfo = app('auth')->user()->get();
        $filter['company_id'] = $authInfo['company_id'];
        $memberService = new MemberService();
        if ($request->input('user_id')) {
            $filter['user_id'] = $request->input('user_id');
        } elseif ($request->input('mobile')) {
            //根据手机号获取用户id
            $memFilter = [
                'company_id' => $filter['company_id'],
                'mobile' => $request->input('mobile'),
            ];
            $memberInfo = $memberService->getMemberInfo($memFilter);
            $filter['user_id'] = $memberInfo['user_id'];
            $memberdata[$filter['user_id']] = $memberInfo;
        }
        if ($request->input('start_date')) {
            $filter['create_time|gte'] = date('Ymd', strtotime($request->input('start_date')));
        }
        if ($request->input('end_date')) {
            $filter['create_time|lte'] = date('Ymd', strtotime($request->input('end_date')));
        }
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['create_time' => 'desc'];
        $result = $this->service->lists($filter, $page, $pageSize, $orderBy);
        if ($result['list']) {
            if (!isset($memberdata)) {
                $userIds = array_unique(array_column($result['list'], 'user_id'));
                $memberList = $memberService->getMemberInfoList(['user_id' => $userIds]);
                $memberdata = array_column($memberList['list'], null, 'user_id');
            }
            foreach ($result['list'] as &$row) {
                if (isset($memberdata[$row['user_id']])) {
                    $row['user_name'] = $memberdata[$row['user_id']]['username'] ?: trans('PromotionsBundle.anonymous_user');
                }
            }
        }
        return $this->response->array($result);
    }
}
