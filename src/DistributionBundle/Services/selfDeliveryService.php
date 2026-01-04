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

namespace DistributionBundle\Services;


use CompanysBundle\Services\EmployeeService;
use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Entities\SelfDeliveryStaff;

class selfDeliveryService
{
    public function setSelfDeliverySetting($companyId, $distributorId, $params)
    {

        return app('redis')->set($this->genReidsId($companyId,$distributorId), json_encode($params));
    }


    public function getSelfDeliverySetting($companyId, $distributorId, $distributorSelf = 0)
    {
        $tem = [
            'is_open'=>false,  //是否开启
            'min_amount'=>0,  //最小起配金额
            'freight_fee'=>0,   //基础运费
            'rule'=>[   //规则
                [
                    'selected'=>false,   //是否选中
                    'full'=>0,    // 满多少
                    'freight_fee'=>0   //运费多少
                ],
                [
                    'selected'=>false,
                    'full'=>0,
                    'freight_fee'=>0
                ]
            ]
        ];
        $data = app('redis')->get($this->genReidsId($companyId, (intval($distributorSelf) ? 0 : $distributorId)));
        if ($data && $data != 'null') {
            $data = json_decode($data, true);

            return $data;
        } else {
            return $tem;
        }
    }


    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId,$distributorId)
    {
        return 'SelfDeliverySetting:' . sha1($companyId).':'.$distributorId;
    }

    public function getSelfDeliveryFee($self_delivery_operator_id , $orderInfo)
    {
        $selfDeliveryStaffRepository = app('registry')->getManager('default')->getRepository(SelfDeliveryStaff::class);
        $selfDeliveryStaff = $selfDeliveryStaffRepository->getInfo(['operator_id'=>$self_delivery_operator_id]);
        if(!$selfDeliveryStaff){
            throw new ResourceException("选择的配送员不存在，请确认");
        }
        //分配配送员就计算配送费用
        if($selfDeliveryStaff['payment_method'] == 'order'){
            $self_delivery_fee = $selfDeliveryStaff['payment_fee'];
        }elseif ($selfDeliveryStaff['payment_method'] == 'amount'){
            $self_delivery_fee = bcdiv(bcmul($selfDeliveryStaff['payment_fee'],$orderInfo['total_fee'],0),10000,2);
        }

        return $self_delivery_fee;
    }

    public function getSelfDeliveryStaffDistributorList($params)
    {
        $employeeService = new EmployeeService();
//        $info = $employeeService->getInfoStaff($params['operator_id'], $params['company_id']);
//        if(empty($info)){
//            return false;
//        }
        $list = $employeeService->getListStaff(['company_id'=>$params['company_id'],'operator_id'=>$params['operator_id'],'operator_type'=>'self_delivery_staff']);
        $list = $list['list'];
        $distributor_ids = [];
        foreach ($list as $o ){
            if(!$o['distributor_ids']){
                continue;
            }

//            $distributor_ids = json_decode($o['distributor_ids'],true);
//            if(!$distributor_ids){
//                continue;
//            }
            foreach ($o['distributor_ids'] as $v){
                $distributor_ids[] = $v['distributor_id'];
            }

        }

//        $distributor_ids = array_column($distributorList,'distributor_id');

        $distributorService = new DistributorService();
        $distributorList = $distributorService->getDistributorOriginalList(['distributor_id'=>$distributor_ids]);


        return  $distributorList;
    }

}
