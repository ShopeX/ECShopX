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

namespace EspierBundle\Services\Export;

use CompanysBundle\Services\EmployeeService;
use DataCubeBundle\Services\DeliveryStaffDataService;
use EspierBundle\Interfaces\ExportFileInterface;
use EspierBundle\Services\ExportFileService;

use ChinaumsPayBundle\Services\ChinaumsPayDivisionService;

class DeliveryStaffDataExportService implements ExportFileInterface
{
    public function exportData($filter)
    {
        $params = ['company_id'=>$filter['company_id'],'operator_type'=>'self_delivery_staff'];
        if(isset($filter['username']) && $filter['username']){
            $params['username'] = $filter['username'];
        }
        if(isset($filter['mobile']) && $filter['mobile']){
            $params['mobile'] = $filter['mobile'];
        }

        if(isset($filter['merchant_id']) && $filter['merchant_id']){
            $params['merchant_id'] = $filter['merchant_id'];
        }

        if(isset($filter['distributor_ids'])){
            if(is_array($filter['distributor_ids'])){
                $params['distributor_ids'] = $filter['distributor_ids'];
            }else{
                $params['distributor_ids|contains'] = $filter['distributor_ids'];
            }
        }
        $divisionService = new EmployeeService();
        $count = $divisionService->operatorsRepository->count($params);
        if (!$count) {
            return [];
        }
        $fileName = date('YmdHis').$filter['company_id']."配送员业绩";
        $title = $this->getTitle();
        $orderList = $this->getLists($filter, $count);

        $exportService = new ExportFileService();
        $result = $exportService->exportCsv($fileName, $title, $orderList);
        return $result;
    }

    private function getTitle()
    {
        $title = [
            'staff_no' => '配送员编码',
            'username' => '配送员姓名',
            'mobile' => '手机号',
            'distributor_names' => '所属店铺',
            'payment_method' => '配送结算方式',
            'user_count' => '配送客户数',
            'order_count' => '配送订单量',
            'payment_fee' => '配送单价',
            'total_fee_count' => '订单金额',
            'self_delivery_fee_count' => '配送费用',
            'staff_type' => '配送员类型',
            'staff_attribute' => '配送员属性',
        ];
        return $title;
    }
    private function getLists($filter, $count)
    {
        $title = $this->getTitle();

        $deliveryStaffDataService = new DeliveryStaffDataService();

        $limit = 500;
        $orderBy = ['id' => 'DESC'];
        $total = ceil($count / $limit);

        for ($i = 1; $i <= $total; $i++) {
            $dataList = [];
            $deliveryStaffList = $deliveryStaffDataService->getDeliveryStaffDataList($filter,  $i, $limit);
            foreach ($deliveryStaffList['list'] as $key => $val) {
                $tmp = [
                    'staff_no'=>$val['staff_no'],
                    'username'=>$val['username'],
                    'mobile'=>$val['mobile'],
                    'distributor_names' => '-',
                    'payment_method' => '-',
                    'user_count'=>$val['user_count'],
                    'order_count'=>$val['order_count'],
                    'payment_fee' => '-',
                    'total_fee_count'=>bcdiv($val['total_fee_count'],100,2),
                    'self_delivery_fee_count'=>bcdiv($val['self_delivery_fee_count'],100,2),
                    'staff_type' => '-',
                    'staff_attribute' => '-',
                ];
                if($val['distributor_ids']){
                    $distributor_names = array_column($val['distributor_ids'],'name');
                    $tmp['distributor_names'] = implode(' ',$distributor_names);
                }
                if($val['payment_method'] == 'order'){
                    $tmp['payment_method'] = '按单笔订单';
                    $tmp['payment_fee'] = $val['payment_fee']/100;
                }elseif ($val['payment_method'] == 'amount'){
                    $tmp['payment_method'] = '按订单金额比例';
                    $tmp['payment_fee'] = '%'.$val['payment_fee']/100;
                }
                if($val['staff_type'] == 'platform'){
                    $tmp['staff_type'] = '平台配送员';
                }elseif ($val['staff_type'] == 'distributor'){
                    $tmp['staff_type'] = '店铺配送员';
                }elseif ($val['staff_type'] == 'shop'){
                    $tmp['staff_type'] = '商家配送员';
                }
                if($val['staff_attribute'] == 'full_time'){
                    $tmp['staff_attribute'] = '全职';
                }elseif ($val['staff_attribute'] == 'part_time'){
                    $tmp['staff_attribute'] = '兼职';
                }

                $dataList[] = $tmp;
            }
            yield $dataList;
        }
    }
}
