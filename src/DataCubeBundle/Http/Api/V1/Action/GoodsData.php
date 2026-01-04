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
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use DataCubeBundle\Services\GoodsDataService;
use DataCubeBundle\Jobs\GoodsDataJob;

class GoodsData extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/datacube/goodsdata",
     *     summary="获取商品统计列表",
     *     tags={"统计"},
     *     description="获取商品统计列表",
     *     operationId="getGoodsData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="no", type="string", example="1", description=""),
     *                          @SWG\Property( property="sap_code", type="string", example="S5F51DB7DB250A", description=""),
     *                          @SWG\Property( property="top_level", type="string", example="阿根达斯", description=""),
     *                          @SWG\Property( property="product", type="string", example="圣代夏日组合", description=""),
     *                          @SWG\Property( property="quantity", type="string", example="18", description="数量"),
     *                          @SWG\Property( property="fix_price", type="string", example="558.00", description=""),
     *                          @SWG\Property( property="settle_price", type="string", example="55.80", description=""),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getGoodsData(request $request)
    {
        $inputData = $request->input();
        $is_export = $inputData['export'] ?? 0;
        // 默认查询7天内数据
        $inputData['start'] = (isset($inputData['start']) && $inputData['start']) ? $inputData['start'] : date('Y-m-d', strtotime('-1 day'));
        $inputData['end'] = (isset($inputData['end']) && $inputData['end']) ? $inputData['end'] : date('Y-m-d', strtotime('-1 day'));

        $start_date_timestamp = strtotime($inputData['start'].' 00:00:00'); // 开始日期的0点时间戳
        $end_date_timestamp = strtotime($inputData['end'].' 00:00:00');// 结束日期的0点时间戳
        $now_date_timestamp = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))); // 昨天日期的0点时间戳
        if ($start_date_timestamp > $end_date_timestamp) {
            throw new ResourceException('结束日期要大于等于开始日期');
        }
        if ($end_date_timestamp > $now_date_timestamp) {
            throw new ResourceException('结束日期必须小于今天');
        }

        $date_start = date_create($inputData['start']);
        $date_end = date_create($inputData['end']);
        $days = date_diff($date_start, $date_end)->days;
        if ($days > 30) {
            throw new ResourceException('最多查询30天内数据');
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['date_start'] = $inputData['start'];
        $params['date_end'] = $inputData['end'];
        if (isset($inputData['order_class'])) {
            $params['order_class'] = $inputData['order_class'];
        }
        if (isset($inputData['act_id'])) {
            $params['act_id'] = $inputData['act_id'];
        }
        $merchant_id = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $params['merchant_id'] = $merchant_id;
        }
        //存储导出操作账号者
        $params['operator_id'] = app('auth')->user()->get('operator_id');
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
}
