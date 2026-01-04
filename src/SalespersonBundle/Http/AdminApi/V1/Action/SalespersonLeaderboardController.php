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

namespace SalespersonBundle\Http\AdminApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use SalespersonBundle\Services\LeaderboardService;

class SalespersonLeaderboardController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/admin/wxapp/leaderboard",
     *     summary="导购端首页排名相关",
     *     tags={"导购"},
     *     description="导购端首页排名相关",
     *     operationId="getLeaderboardInfo",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesperson_rank_top", type="object",
     *                          @SWG\Property( property="total_count", type="string", example="3", description="总数"),
     *                          @SWG\Property( property="list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                                  @SWG\Property( property="distributor_id", type="string", example="21", description=" 店铺id "),
     *                                  @SWG\Property( property="salesperson_id", type="string", example="80", description="导购员id "),
     *                                  @SWG\Property( property="date", type="string", example="202102", description=" 统计日期 Ym"),
     *                                  @SWG\Property( property="sales", type="string", example="4900", description=" 销售额"),
     *                                  @SWG\Property( property="number", type="string", example="23", description="销售订单数量"),
     *                                  @SWG\Property( property="name", type="string", example="石学峰", description=" 导购员姓名 "),
     *                                  @SWG\Property( property="avatar", type="string", example="http://wework.qpic.cn/bizmail/...", description=" 企业微信头像"),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="salesperson_rank", type="string", example="1", description="排名"),
     *                  @SWG\Property( property="distributor_rank_top", type="object",
     *                          @SWG\Property( property="total_count", type="string", example="3", description="总数"),
     *                          @SWG\Property( property="list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                                  @SWG\Property( property="distributor_id", type="string", example="21", description=" 店铺id "),
     *                                  @SWG\Property( property="date", type="string", example="202102", description=" 统计日期 Ym"),
     *                                  @SWG\Property( property="sales", type="string", example="4900", description=" 销售额"),
     *                                  @SWG\Property( property="number", type="string", example="23", description="销售订单数量 "),
     *                                  @SWG\Property( property="name", type="string", example="标准版测试用店铺，开启自提自动同步", description=" 店铺名称 "),
     *                               ),
     *                          ),
     *                  ),
     *                  @SWG\Property( property="distributor_rank", type="string", example="1", description="店铺排名"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getLeaderboardInfo(Request $request)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $distributorId = $authInfo['distributor_id'];
        $salespersonId = $authInfo['salesperson_id'];
        $leaderboardService = new LeaderboardService();
        $result = $leaderboardService->getLeaderboardInfo($companyId, $distributorId, $salespersonId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/leaderboard/salesperson",
     *     summary="导购端导购排名列表",
     *     tags={"导购"},
     *     description="导购端导购排名列表",
     *     operationId="getSalespersonLeaderboardList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="month", in="query", description="导购排名月份(Ym)", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="21", description=" 店铺id "),
     *                          @SWG\Property( property="salesperson_id", type="string", example="80", description="导购员id "),
     *                          @SWG\Property( property="date", type="string", example="202102", description="统计日期 Ym"),
     *                          @SWG\Property( property="sales", type="string", example="4900", description="销量 "),
     *                          @SWG\Property( property="number", type="string", example="23", description=" 销售订单数量 "),
     *                          @SWG\Property( property="name", type="string", example="石学峰", description="导购员姓名"),
     *                          @SWG\Property( property="avatar", type="string", example="http://wework.qpic.cn/bizmail/...", description=" 企业微信头像"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="salesperson_rank", type="string", example="1", description="排行"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getSalespersonLeaderboardList(Request $request)
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $distributorId = $authInfo['distributor_id'];
        $salespersonId = $authInfo['salesperson_id'];
        $month = $request->input('month', date('Ym'));
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 20);
        $leaderboardService = new LeaderboardService();
        $result = $leaderboardService->getSalespersonLeaderboard($companyId, $distributorId, $month, $page, $pageSize);
        $result['salesperson_rank'] = $leaderboardService->getSalespersonLeaderboardZrank($companyId, $distributorId, $salespersonId, $month);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/admin/wxapp/leaderboard/distributor",
     *     summary="导购端店铺排名列表",
     *     tags={"导购"},
     *     description="导购端店铺排名列表",
     *     operationId="getDistributorLeaderboardList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="integer", default="1"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=true, type="integer", default="20"),
     *     @SWG\Parameter( name="month", in="query", description="店铺排名月份(Ym)", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id "),
     *                          @SWG\Property( property="distributor_id", type="string", example="21", description=" 店铺id "),
     *                          @SWG\Property( property="date", type="string", example="202102", description=" 统计日期 Ym"),
     *                          @SWG\Property( property="sales", type="string", example="4900", description="商品销量 | 销量 | 销售额"),
     *                          @SWG\Property( property="number", type="string", example="23", description=" 销售订单数量 "),
     *                          @SWG\Property( property="name", type="string", example="标准版测试用店铺，开启自提自动同步", description=" 店铺名称"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="distributor_rank", type="string", example="1", description="排行"),
     *          ),
     *     )),
     *     @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse")))
     * )
     */
    public function getDistributorLeaderboardList(Request $request)
    {
        $authInfo = $this->auth->user();
        $companyId = $authInfo['company_id'];
        $distributorId = $authInfo['distributor_id'];
        $month = $request->input('month', date('Ym'));
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);
        $leaderboardService = new LeaderboardService();
        $result = $leaderboardService->getDistributorLeaderboard($companyId, $month, $page, $pageSize);
        $result['distributor_rank'] = $leaderboardService->getDistributorLeaderboardZrank($companyId, $distributorId, $month);
        return $this->response->array($result);
    }
}
