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

namespace DataCubeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use DataCubeBundle\Services\DistributorDataService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

class DistributorData extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/datacube/distributordata",
     *     summary="获取商城门店统计列表",
     *     tags={"统计"},
     *     description="获取商城门店统计列表",
     *     operationId="getDistributorData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="distributor_id", in="query", description="门店ID", type="integer" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="7", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="46234", description=""),
     *                          @SWG\Property( property="count_date", type="string", example="2021-01-20", description="日期"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="85", description="分销商id"),
     *                          @SWG\Property( property="member_count", type="string", example="0", description="新增会员数"),
     *                          @SWG\Property( property="aftersales_count", type="string", example="0", description="新增售后单数"),
     *                          @SWG\Property( property="refunded_count", type="string", example="0", description="新增退款额"),
     *                          @SWG\Property( property="amount_payed_count", type="string", example="0", description="新增交易额"),
     *                          @SWG\Property( property="amount_point_payed_count", type="string", example="0", description="新增交易额(积分)"),
     *                          @SWG\Property( property="order_count", type="string", example="0", description="新增订单数"),
     *                          @SWG\Property( property="order_point_count", type="string", example="0", description="新增订单数(积分)"),
     *                          @SWG\Property( property="order_payed_count", type="string", example="0", description="新增已付款订单数"),
     *                          @SWG\Property( property="order_point_payed_count", type="string", example="0", description="新增已付款订单数(积分)"),
     *                          @SWG\Property( property="gmv_count", type="string", example="0", description="新增gmv"),
     *                          @SWG\Property( property="gmv_point_count", type="string", example="0", description="新增gmv(积分)"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getDistributorData(request $request)
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

        if (app('auth')->user()->get('operator_type') == 'distributor') {
            $distributor_id = app('auth')->user()->get('distributor_id');
        } else {
            $distributor_id = $inputData['distributor_id'];
        }
        if (!isset($distributor_id) && $distributor_id == '') {
            throw new ResourceException('店铺必须选择');
        }
        if (($distributor_id == 0) && (app('auth')->user()->get('operator_type') == 'staff') && app('auth')->user()->get('distributor_ids')) {
            $distributor_id = app('auth')->user()->get('distributor_ids');
        }

        $date_start = date_create($inputData['start']);
        $date_end = date_create($inputData['end']);
        $days = date_diff($date_start, $date_end)->days;
        if ($days > 90) {
            throw new ResourceException('最多查询90天内数据');
        }


        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['distributor_id'] = $distributor_id;
        $params['count_date|gte'] = $inputData['start'];
        $params['count_date|lte'] = $inputData['end'];
        $merchant_id = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = $merchant_id;
        }
        //return $params;
        $page = 1; // $inputData['page'];
        $pageSize = 90; // $inputData['pageSize'];
        $companyDataServiceService = new DistributorDataService();
        $result = $companyDataServiceService->getDistributorDataList($params, $page, $pageSize);

        return $this->response->array($result);
    }
}
