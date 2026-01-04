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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use DataCubeBundle\Jobs\GoodsDataJob;
use DataCubeBundle\Services\CompanyDataService;
use DataCubeBundle\Services\GoodsDataService;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Http\Request;

class DataCube extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/datacube/goodsdata",
     *     summary="获取商品统计列表",
     *     tags={"统计"},
     *     description="获取商品统计列表",
     *     operationId="getCompanyData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="company_id", in="query", description="品牌商户id", required=true, type="integer" ),
     *     @SWG\Parameter(
     *         name="start",
     *         in="query",
     *         description="开始时间(Y-m-d)",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="end",
     *         in="query",
     *         description="结束时间(Y-m-d)",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="export",
     *         in="query",
     *         description="是否导出数据",
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="no", type="string", example="1", description="序号"),
     *                          @SWG\Property( property="sap_code", type="string", example="1234", description="SAP编码"),
     *                          @SWG\Property( property="top_level", type="string", example="上装", description="主类目"),
     *                          @SWG\Property( property="product", type="string", example="测试度商品图", description="商品"),
     *                          @SWG\Property( property="quantity", type="string", example="28", description="数量"),
     *                          @SWG\Property( property="fix_price", type="string", example="0.84", description="优惠前价格"),
     *                          @SWG\Property( property="settle_price", type="string", example="0.30", description="实付价"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getGoodsData(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');
        $is_export = $request->input('export', 0);
        $company_id = $request->input('company_id');

        $start = (isset($start) && $start) ? $start : date('Y-m-d', strtotime('-1 day'));
        $end = (isset($end) && $end) ? $end : date('Y-m-d', strtotime('-1 day'));

        //时间校验
        $start_date_timestamp = strtotime($start . ' 00:00:00'); // 开始日期的0点时间戳
        $end_date_timestamp = strtotime($end . ' 00:00:00');// 结束日期的0点时间戳
        $now_date_timestamp = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))); // 昨天日期的0点时间戳
        if ($start_date_timestamp > $end_date_timestamp) {
            throw new ResourceException('结束日期要大于等于开始日期');
        }

        if ($end_date_timestamp > $now_date_timestamp) {
            throw new ResourceException('结束日期必须小于今天');
        }

        $date_start = date_create($start);
        $date_end = date_create($end);
        $days = date_diff($date_start, $date_end)->days;
        if ($days > 30) {
            throw new ResourceException('最多查询30天内数据');
        }

        $params['company_id'] = $company_id;
        $params['date_start'] = $start;
        $params['date_end'] = $end;
        //IT端导出的数据固定为0
        $params['operator_id'] = 0;
        if ($is_export) {
            $gotoJob = (new GoodsDataJob($params))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            $result['status'] = true;
            return response()->json($result);
        } else {
            $companyDataServiceService = new GoodsDataService();
            $result['list'] = $companyDataServiceService->getGoodsDataList($params);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/companydata",
     *     summary="获取统计列表",
     *     tags={"统计"},
     *     description="获取统计列表",
     *     operationId="getCompanyData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter(
     *         name="start",
     *         in="query",
     *         description="开始时间(Y-m-d)",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="end",
     *         in="query",
     *         description="结束时间(Y-m-d)",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="query",
     *         description="品牌商户id",
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="7", description="总数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="28542", description="ID"),
     *                          @SWG\Property( property="count_date", type="string", example="2021-01-29", description="日期"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="member_count", type="string", example="3", description="新增会员数"),
     *                          @SWG\Property( property="aftersales_count", type="string", example="0", description="新增售后单数"),
     *                          @SWG\Property( property="refunded_count", type="string", example="0", description="新增退款额"),
     *                          @SWG\Property( property="amount_payed_count", type="string", example="257", description="新增交易额"),
     *                          @SWG\Property( property="amount_point_payed_count", type="string", example="257", description="新增交易额(积分)"),
     *                          @SWG\Property( property="order_count", type="string", example="16", description="新增订单数"),
     *                          @SWG\Property( property="order_point_count", type="string", example="16", description="新增订单数(积分)"),
     *                          @SWG\Property( property="order_payed_count", type="string", example="9", description="新增已付款订单数"),
     *                          @SWG\Property( property="order_point_payed_count", type="string", example="9", description="新增已付款订单数(积分)"),
     *                          @SWG\Property( property="gmv_count", type="string", example="22180", description="新增gmv"),
     *                          @SWG\Property( property="gmv_point_count", type="string", example="22180", description="新增gmv(积分)"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function getCompanyData(request $request)
    {
        $inputData = $request->input();
        // 默认查询7天内数据
        $inputData['start'] = (isset($inputData['start']) && $inputData['start']) ? $inputData['start'] : date('Y-m-d', strtotime('-7 day'));
        $inputData['end'] = (isset($inputData['end']) && $inputData['end']) ? $inputData['end'] : date('Y-m-d', strtotime('-1 day'));

        $start_date_timestamp = strtotime($inputData['start'].' 00:00:00'); // 开始日期的0点时间戳
        $end_date_timestamp = strtotime($inputData['end'].' 00:00:00');// 结束日期的0点时间戳
        $now_date_timestamp = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))); // 昨天日期的0点时间戳
        if ($start_date_timestamp > $end_date_timestamp) {
            throw new ResourceException('结束日期要大于等于开始日期');
        }
        if ($end_date_timestamp > $now_date_timestamp) {
            throw new ResourceException('结束日期必须小于当前日期');
        }

        $date_start = date_create($inputData['start']);
        $date_end = date_create($inputData['end']);
        $days = date_diff($date_start, $date_end)->days;
        if ($days > 90) {
            throw new ResourceException('最多查询90天内数据');
        }

        $params['company_id'] = isset($inputData['company_id']) && !empty($inputData['company_id']) ? $inputData['company_id'] : '';
        $params['count_date|gte'] = $date_start;
        $params['count_date|lte'] = $date_end;
        $page = 1; // $inputData['page'];
        $pageSize = 90; // $inputData['pageSize'];
        $companyDataServiceService = new CompanyDataService();
        $result = $companyDataServiceService->getCompanyDataList($params, $page, $pageSize);

        return $this->response->array($result);
    }
}
