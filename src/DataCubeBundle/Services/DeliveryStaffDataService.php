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

namespace DataCubeBundle\Services;


use AftersalesBundle\Entities\Aftersales;
use CompanysBundle\Services\EmployeeService;
use DistributionBundle\Entities\SelfDeliveryStaff;
use OrdersBundle\Entities\NormalOrders;
use function Amp\Iterator\toArray;

class DeliveryStaffDataService
{
    public function getDeliveryStaffDataList($params, $page, $pageSize)
    {
        $filter = ['company_id'=>$params['company_id'],'operator_type'=>'self_delivery_staff'];
        if(isset($params['username']) && $params['username']){
            $filter['username'] = $params['username'];
        }
        if(isset($params['mobile']) && $params['mobile']){
            $filter['mobile'] = $params['mobile'];
        }

        if(isset($params['merchant_id']) && $params['merchant_id']){
            $filter['merchant_id'] = $params['merchant_id'];
        }

        if(isset($params['distributor_ids']) && $params['distributor_ids']){
            if(is_array($params['distributor_ids'])){
                $filter['distributor_ids'] = $params['distributor_ids'];
            }else{
                $filter['distributor_ids|contains'] = $params['distributor_ids'];
            }
        }

        $employeeService = new EmployeeService();
        $result = $employeeService->getListStaff($filter, $page, $pageSize);
        if($result['total_count'] == 0){
            return $result;
        }
        $operator_ids = array_column($result['list'],'operator_id');
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $oFilter = [
            'self_delivery_status'=>'DONE',
            'company_id'=>$params['company_id'],
            'self_delivery_end_time|gte'=>$params['start_date'],
            'self_delivery_end_time|lte'=>$params['end_date'],
            'self_delivery_operator_id'=>$operator_ids
        ];

        if(isset($params['merchant_id']) && $params['merchant_id']){
            $oFilter['merchant_id'] = $params['merchant_id'];
        }

        if(isset($params['distributor_id']) && $params['distributor_id']){
            $oFilter['distributor_id'] = $params['distributor_id'];
        }
//        app('log')->info(__FUNCTION__.' oFilter:'.json_encode($oFilter).',line:'.__LINE__);

        $count_list = $normalOrdersRepository->getDeliveryStaffDataCountByOperatorIds($oFilter);

        $count_list = array_column($count_list,null,'self_delivery_operator_id');

        foreach ($result['list'] as &$v){
            $v['distributor_ids'] = $v['distributor_ids']?:[];
            $v['user_count'] = 0;
            $v['order_count'] = 0;
            $v['total_fee_count'] = 0;
            $v['self_delivery_fee_count'] = 0;
            if(isset($count_list[$v['operator_id']])){
                $v = array_merge($v,$count_list[$v['operator_id']]);
            }
        }


        return  $result;
    }

    public function getDeliveryStaffData($params)
    {
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $aftersalesRepository = app('registry')->getManager('default')->getRepository(Aftersales::class);
        $oFilter = [
            'self_delivery_status'=>'DONE',
            'company_id'=>$params['company_id'],
            'self_delivery_end_time|gte'=>$params['start_date'],
            'self_delivery_end_time|lte'=>$params['end_date'],
            'self_delivery_operator_id'=>$params['operator_id']
        ];


        if(isset($params['distributor_id']) && $params['distributor_id']){
            $oFilter['distributor_id'] = $params['distributor_id'];
        }
        app('log')->info(__FUNCTION__.' oFilter:'.json_encode($oFilter).',line:'.__LINE__);

        $count_list = $normalOrdersRepository->getDeliveryStaffDataCountByOperatorIds($oFilter);
        $order_count = 0;
        $total_fee_count = 0;
        $self_delivery_fee_count = 0;
        foreach ($count_list as $count){
            $order_count += $count['order_count'];
            $total_fee_count += $count['total_fee_count'];
            $self_delivery_fee_count += $count['self_delivery_fee_count'];
        }
        $c['order_count'] = $order_count;
        $c['total_fee_count'] = $total_fee_count;
        $c['self_delivery_fee_count'] = $self_delivery_fee_count;
        $aFilter = [
            'aftersales_status'=>'2',
            'company_id'=>$params['company_id'],
            'self_delivery_operator_id'=>$params['operator_id']
        ];
        $rFilter = [
            'refund_success_time|gte'=>$params['start_date'],
            'refund_success_time|lte'=>$params['end_date'],
        ];


        if(isset($params['distributor_id']) && $params['distributor_id']){
            $aFilter['distributor_id'] = $params['distributor_id'];
        }
        app('log')->info(__FUNCTION__.' aFilter:'.json_encode($aFilter).',line:'.__LINE__);
        app('log')->info(__FUNCTION__.' rFilter:'.json_encode($rFilter).',line:'.__LINE__);
        $a_count = $aftersalesRepository->getDeliveryStaffDataCountByOperatorId($aFilter,$rFilter);
        $aftersales_count = 0;
        $refund_fee_count = 0;
        foreach ($a_count as $count){
            $aftersales_count += $count['aftersales_count'];
            $refund_fee_count += $count['refund_fee_count'];
        }
        $c['aftersales_count'] = $aftersales_count;
        $c['refund_fee_count'] = $refund_fee_count;

        return $c;
    }

    public function getDeliveryStaffDataDetail($params)
    {
        // ShopEx EcShopX Core Module
        $normalOrdersRepository = app('registry')->getManager('default')->getRepository(NormalOrders::class);
        $oFilter = [
            'self_delivery_status'=>'DONE',
            'company_id'=>$params['company_id'],
            'self_delivery_end_time|gte'=>$params['start_date'],
            'self_delivery_end_time|lte'=>$params['end_date'],
            'self_delivery_operator_id'=>$params['operator_id']
        ];


        if(isset($params['distributor_id']) && $params['distributor_id']){
            $oFilter['distributor_id'] = $params['distributor_id'];
        }
        app('log')->info(__FUNCTION__.' oFilter:'.json_encode($oFilter).',line:'.__LINE__);


        $orderList = $normalOrdersRepository->getList($oFilter,0,-1,null,'order_id,total_fee,self_delivery_fee,self_delivery_end_time');
        $date = $params['date'];
        if ($params['datetype'] === 'y') {
            // 生成一年12个月的数据结构
            $year = $date; // 获取当前年份
            for ($month = 1; $month <= 12; $month++) {
                $count[$year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT)] = ['order_count'=>0,'total_fee_count'=>0,'self_delivery_fee_count'=>0];
            }
        } elseif ($params['datetype'] === 'm') {
            // 生成一个月每一天的数据结构
            $year = date('Y',strtotime($date."-01 00:00:00")); // 获取当前年份
            $month = date('m',strtotime($date."-01 00:00:00")); // 获取当前月份
            $daysInMonth = date('t', strtotime($date."-01 00:00:00")); // 计算当月天数
            for ($day = 1; $day <= $daysInMonth; $day++) {
                $count[$year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($day, 2, '0', STR_PAD_LEFT)] = ['order_count'=>0,'total_fee_count'=>0,'self_delivery_fee_count'=>0];
            }
        }else{
            $count[$date] = ['order_count'=>0,'total_fee_count'=>0,'self_delivery_fee_count'=>0];
        }
        // 如果 $datetype 等于 y  返回列表包括 12个月的结构，key 是月份（2024-01）的空数据，如果$datetype 等于 m 返回列表包括 1个月每天的结构，key 是每天日期（2024-01-01）的空数据，用php 代码实现
        foreach ($orderList as $order){
            if($params['datetype'] == 'y'){
                $format = 'Y-m';
            }elseif ($params['datetype'] == 'm'){
                $format = 'Y-m-d';
            }elseif($params['datetype'] == 'd'){
                $format = 'Y-m-d';
            }
            $self_delivery_end_date = date($format,$order['self_delivery_end_time']);
            $count[$self_delivery_end_date]['order_count'] = ($count[$self_delivery_end_date]['order_count'] ?? 0) + 1;
            $count[$self_delivery_end_date]['total_fee_count'] = ($count[$self_delivery_end_date]['total_fee_count'] ?? 0) + $order['total_fee'];
            $count[$self_delivery_end_date]['self_delivery_fee_count'] = ($count[$self_delivery_end_date]['self_delivery_fee_count'] ?? 0) + $order['self_delivery_fee'];

        }

        return array_values($count);
    }
}
