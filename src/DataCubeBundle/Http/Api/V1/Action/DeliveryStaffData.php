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
use DataCubeBundle\Services\DeliveryStaffDataService;
use DataCubeBundle\Services\DistributorDataService;
use DistributionBundle\Entities\Distributor;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

class DeliveryStaffData extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/datacube/Deliverystaffdata",
     *     summary="获取配送员统计列表",
     *     tags={"统计"},
     *     description="获取配送员统计列表",
     *     operationId="getDeliverystaffdata",
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
    public function getDeliveryStaffData(request $request)
    {
        $merchantId = app('auth')->user()->get('merchant_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $inputData = $request->input();

        if(isset($inputData['delivery_staff_name']) && $inputData['delivery_staff_name']){
            $params['username'] = $inputData['delivery_staff_name'];
        }

        if(isset($inputData['delivery_staff_mobile']) && $inputData['delivery_staff_mobile']){
            $params['mobile'] = $inputData['delivery_staff_mobile'];
        }


        if(isset($inputData['merchant_id']) && $inputData['merchant_id']){
            $params['merchant_id'] = $inputData['merchant_id'];
        }

        $distributor_id = app('auth')->user()->get('distributor_id');
        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_id'] = $distributor_id = $inputData['distributor_id'];
        }

        if ($distributor_id) {
            $params['distributor_ids'] = '"distributor_id":"'.$distributor_id.'"';
        }

        if($merchantId > 0 && $params['operator_type'] == 'merchant'){
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributorIds = $distributorRepository->getLists(['merchant_id'=>$merchantId,'company_id'=>$params['company_id']],'distributor_id,merchant_id');
            $distributor_ids = [];
            foreach ($distributorIds as $d_id){
                $distributor_ids[] = '"distributor_id":"'.$d_id['distributor_id'].'"';
            }
            if($distributor_ids){
                $params['distributor_ids'] =$distributor_ids;
                unset($params['merchant_id']);
            }
        }


        // 默认查询7天内数据
        $start_date_timestamp = (isset($inputData['start']) && $inputData['start']) ? $inputData['start'] : strtotime(date('Y-m-d 00:00:00'));
        $end_date_timestamp = (isset($inputData['end']) && $inputData['end']) ? $inputData['end'] : strtotime(date('Y-m-d 23:59:59'));

        if(isset($inputData['year']) && $inputData['year']){
            $year = $inputData['year'];
            $nextYear = ($year + 1)."-01-01";
            $lastDay = date("Y-m-d", strtotime($nextYear."-1 day"));
            $start_date_timestamp = strtotime($year."-01-01 00:00:00");
            $end_date_timestamp = strtotime($lastDay."23:59:59");
        }
        if(isset($inputData['month']) && $inputData['month']){
            $month = $inputData['month'];
            $start_date_timestamp = strtotime($month."-01 00:00:00");
            $end_date_timestamp = strtotime(date('Y-m-t', strtotime($month.'-01'))."23:59:59");
        }
        if(isset($inputData['day']) && $inputData['day']){
            $start_date_timestamp = strtotime(date($inputData['day'].' 00:00:00'));
            $end_date_timestamp = strtotime(date($inputData['day'].' 23:59:59'));
        }
        $now_date_timestamp = strtotime(date('Y-m-d 00:00:00', strtotime('-1 day'))); // 昨天日期的0点时间戳
        if ($start_date_timestamp > $end_date_timestamp) {
            throw new ResourceException('结束日期要大于等于开始日期');
        }
//        if ($end_date_timestamp > $now_date_timestamp) {
//            throw new ResourceException('结束日期必须小于当前日期');
//        }



        $params['start_date'] =$start_date_timestamp;
        $params['end_date'] = $end_date_timestamp;

        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $is_sort = $inputData['is_sort']??0;
        $companyDataServiceService = new DeliveryStaffDataService();
        $result = $companyDataServiceService->getDeliveryStaffDataList($params, $page, $pageSize);
        if($is_sort){
            $self_delivery_fee_count = array_column($result['list'], 'self_delivery_fee_count');

            array_multisort($self_delivery_fee_count, SORT_DESC, $result['list']);
//        $result['list']= toArray($result['list']);
        }

        return $this->response->array($result);
    }

     /**
      * @SWG\Get(
      *     path="/datacube/Deliverystaffdata/export",
      *     summary="获取配送员统计列表",
      **/
    public function exportDeliverystaffdata(Request $request)
    {
        $merchantId = app('auth')->user()->get('merchant_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_type'] = app('auth')->user()->get('operator_type');
        $exportType = 'delivery_staffdata';
        $inputData = $request->input();

        if(isset($inputData['delivery_staff_name']) && $inputData['delivery_staff_name']){
            $params['username'] = $inputData['delivery_staff_name'];
        }

        if(isset($inputData['delivery_staff_mobile']) && $inputData['delivery_staff_mobile']){
            $params['mobile'] = $inputData['delivery_staff_mobile'];
        }

        $distributor_id = app('auth')->user()->get('distributor_id');
        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_id'] = $distributor_id = $inputData['distributor_id'];
        }

        if ($distributor_id) {
            $params['distributor_ids'] = '"distributor_id":"'.$distributor_id.'"';
        }

        if(isset($inputData['merchant_id']) && $inputData['merchant_id']){
            $params['merchant_id'] = $inputData['merchant_id'];
        }
        if($merchantId > 0 && $params['operator_type'] == 'merchant'){
            $distributorRepository = app('registry')->getManager('default')->getRepository(Distributor::class);
            $distributorIds = $distributorRepository->getLists(['merchant_id'=>$merchantId,'company_id'=>$params['company_id']],'distributor_id,merchant_id');
            $distributor_ids = [];
            foreach ($distributorIds as $d_id){
                $distributor_ids[] = '"distributor_id":"'.$d_id['distributor_id'].'"';
            }
            if($distributor_ids){
                $params['distributor_ids'] =$distributor_ids;
                unset($params['merchant_id']);
            }
        }

        // 默认查询7天内数据
        $inputData['start'] = (isset($inputData['start']) && $inputData['start']) ? $inputData['start'] : strtotime(date('Y-m-d 00:00:00'));
        $inputData['end'] = (isset($inputData['end']) && $inputData['end']) ? $inputData['end'] : strtotime(date('Y-m-d 23:59:59'));

        $start_date_timestamp = $inputData['start']; // 开始日期的0点时间戳
        $end_date_timestamp = $inputData['end'];// 结束日期的0点时间戳
        if ($start_date_timestamp > $end_date_timestamp) {
            throw new ResourceException('结束日期要大于等于开始日期');
        }



        $operator_id = app('auth')->user()->get('operator_id');
        $params['start_date'] = $inputData['start'];
        $params['end_date'] = $inputData['end'];
        $gotoJob = (new ExportFileJob($exportType, $params['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
