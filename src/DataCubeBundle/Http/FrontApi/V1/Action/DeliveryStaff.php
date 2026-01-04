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

namespace DataCubeBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use DataCubeBundle\Services\DeliveryStaffDataService;
use DataCubeBundle\Services\DistributorDataService;
use DistributionBundle\Entities\Distributor;
use DistributionBundle\Services\selfDeliveryService;
use EspierBundle\Jobs\ExportFileJob;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;

class DeliveryStaff extends BaseController
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
        // 0x456353686f7058
        $authInfo = $request->get('auth');
        $params['company_id'] =  $authInfo['company_id'];
        $inputData = $request->input();
        $operator_id = $inputData['self_delivery_operator_id'] ?? [];
        $params['operator_id'] = $operator_id;
        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_id'] = $inputData['distributor_id'];
        }else{
            $selfDeliveryService = new selfDeliveryService();
            $data = $selfDeliveryService->getSelfDeliveryStaffDistributorList($params);
            $params['distributor_id'] = array_column($data,'distributor_id');
        }


        // 默认查询7天内数据
//        $start_date_timestamp = (isset($inputData['date']) && $inputData['date']) ? $inputData['date'] : strtotime(date('Y-m-d 00:00:00'));
//        $end_date_timestamp = (isset($inputData['end']) && $inputData['end']) ? $inputData['end'] : strtotime(date('Y-m-d 23:59:59'));

        if(isset($inputData['datetype']) && $inputData['datetype'] == 'y'){
            $year = $inputData['date'];
            $nextYear = ($year + 1)."-01-01";
            $lastDay = date("Y-m-d", strtotime($nextYear."-1 day"));
            $start_date_timestamp = strtotime($year."-01-01 00:00:00");
            $end_date_timestamp = strtotime($lastDay."23:59:59");
        }
        if(isset($inputData['datetype']) && $inputData['datetype'] == 'm'){
            $month = $inputData['date'];
            $start_date_timestamp = strtotime($month."-01 00:00:00");
            $end_date_timestamp = strtotime(date('Y-m-t', strtotime($month.'-01'))."23:59:59");
        }
        if(isset($inputData['datetype']) && $inputData['datetype'] == 'd'){
            $start_date_timestamp = strtotime(date($inputData['date'].' 00:00:00'));
            $end_date_timestamp = strtotime(date($inputData['date'].' 23:59:59'));
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


        $companyDataServiceService = new DeliveryStaffDataService();
        $result = $companyDataServiceService->getDeliveryStaffData($params);


        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/DeliverystaffdataDetail",
     *     summary="获取配送员统计详情",
     */
    public function getDeliveryStaffDataDetail(request $request)
    {
        // 0x456353686f7058
        $authInfo = $request->get('auth');
        $params['company_id'] =  $authInfo['company_id'];
        $inputData = $request->input();
        $operator_id = $inputData['self_delivery_operator_id'] ?? [];
        $params['operator_id'] = $operator_id;
        $params['datetype'] = $inputData['datetype'];
        $params['date'] = $inputData['date'];
        if(isset($inputData['distributor_id']) && $inputData['distributor_id']){
            $params['distributor_id'] = $inputData['distributor_id'];
        }else{
            $selfDeliveryService = new selfDeliveryService();
            $data = $selfDeliveryService->getSelfDeliveryStaffDistributorList($params);
            $params['distributor_id'] = array_column($data,'distributor_id');
        }


        // 默认查询7天内数据
//        $start_date_timestamp = (isset($inputData['date']) && $inputData['date']) ? $inputData['date'] : strtotime(date('Y-m-d 00:00:00'));
//        $end_date_timestamp = (isset($inputData['end']) && $inputData['end']) ? $inputData['end'] : strtotime(date('Y-m-d 23:59:59'));

        if(isset($inputData['datetype']) && $inputData['datetype'] == 'y'){
            $year = $inputData['date'];
            $nextYear = ($year + 1)."-01-01";
            $lastDay = date("Y-m-d", strtotime($nextYear."-1 day"));
            $start_date_timestamp = strtotime($year."-01-01 00:00:00");
            $end_date_timestamp = strtotime($lastDay."23:59:59");
        }
        if(isset($inputData['datetype']) && $inputData['datetype'] == 'm'){
            $month = $inputData['date'];
            $start_date_timestamp = strtotime($month."-01 00:00:00");
            $end_date_timestamp = strtotime(date('Y-m-t', strtotime($month.'-01'))."23:59:59");
        }
        if(isset($inputData['datetype']) && $inputData['datetype'] == 'd'){
            $start_date_timestamp = strtotime(date($inputData['date'].' 00:00:00'));
            $end_date_timestamp = strtotime(date($inputData['date'].' 23:59:59'));
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


        $companyDataServiceService = new DeliveryStaffDataService();
        $result = $companyDataServiceService->getDeliveryStaffDataDetail($params);


        return $this->response->array($result);
    }


}
