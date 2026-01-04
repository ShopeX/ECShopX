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

namespace SalespersonBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use SalespersonBundle\Services\ProfitService;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;

class ProfitController extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/profit/statistics",
     *     summary="获取分润统计",
     *     tags={"导购"},
     *     description="获取分润统计",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取分润统计的初始偏移位置，从1开始计数", default="1", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", default="20", type="integer"),
     *     @SWG\Parameter( name="profitType", in="query", description="分润对象类型", required=true, default="1", type="integer"),
     *     @SWG\Parameter( name="distributor", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="salesperson", in="query", description="导购名称", type="string"),
     *     @SWG\Parameter( name="dealer", in="query", description="区域经销商名称", type="string"),
     *     @SWG\Parameter( name="date", in="query", description="数据年月(202011)", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="date", type="string", example="202101", description="分润月份"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="profit_user_id", type="string", example="1", description="分润类型id"),
     *                          @SWG\Property( property="profit_user_type", type="string", example="1", description="1 用户 2 店铺 3 区域经销商 4 总部"),
     *                          @SWG\Property( property="withdrawals_fee", type="string", example="10000", description="提现金额，以分为单位"),
     *                          @SWG\Property( property="name", type="string", example="测试", description="提现对象名称"),
     *                          @SWG\Property( property="params", type="string", example="{}", description="提现对象名称(DC2Type:json_array)"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function lists(Request $request)
    {
        $profitService = new ProfitService();
        $authInfo = app('auth')->user()->get();
        $fields = $request->all('profitType', 'distributor', 'salesperson', 'dealer', 'date');
        $pageSize = $request->input('pageSize');
        $page = $request->input('page');
        $result = $profitService->getWithdrawalList($authInfo['company_id'], $fields, $pageSize, $page);
        return $result;
    }

    /**
     * @SWG\Get(
     *     path="/profit/export",
     *     summary="导出分润统计",
     *     tags={"导购"},
     *     description="导出分润统计",
     *     operationId="lists",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取分润统计的初始偏移位置，从1开始计数", default="1", type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", default="20", type="integer"),
     *     @SWG\Parameter( name="profitType", in="query", description="分润对象类型", type="string"),
     *     @SWG\Parameter( name="distributor", in="query", description="店铺名称", type="string"),
     *     @SWG\Parameter( name="salesperson", in="query", description="导购名称", type="string"),
     *     @SWG\Parameter( name="dealer", in="query", description="区域经销商名称", type="string"),
     *     @SWG\Parameter( name="date", in="query", description="数据年月(202011)", type="string"),
     *     @SWG\Parameter( name="profit_user_type", in="query", description="导出类型(1/2)", type="string"),
     *     @SWG\Parameter( name="type", in="query", description="导出类型(profit_salesperson/profit_distributor)", type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="导出结果"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SalespersonErrorResponse") ) )
     * )
     */
    public function exportProfitData(Request $request)
    {
        $type = $request->input('type');
        $authdata = app('auth')->user()->get();
        $companyId = $authdata['company_id'];
        $distributorId = $request->get('distributor_id', 0);
        $filter = [
            'company_id' => $companyId,
            'date' => $request->input('date', date('Ym')),
            'profit_user_type' => $request->input('profit_user_type')
        ];
        $gotoJob = (new ExportFileJob($type, $companyId, $filter, $distributorId))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
