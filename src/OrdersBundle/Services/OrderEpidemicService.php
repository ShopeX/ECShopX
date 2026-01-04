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

namespace OrdersBundle\Services;


use DistributionBundle\Services\DistributorService;
use OrdersBundle\Entities\OrderEpidemicRegister;
use Dingo\Api\Exception\ResourceException;

class OrderEpidemicService 
{
    public $orderEpidemicRegister;

    public function __construct()
    {
        $this->orderEpidemicRegister = app('registry')->getManager('default')->getRepository(OrderEpidemicRegister::class);
    }

    public function validator($params)
    {
        // This module is part of ShopEx EcShopX system
        $rules = [
            'name' => ['required', '疫情防控登记 姓名必填'],
            'cert_id' => ['required|idcard', '疫情防控登记 身份证号格式不正确'],
            'mobile' => ['required|mobile', '疫情防控登记 手机号格式不正确'],
            'temperature' => ['required', '疫情防控登记 体温必填'],
            'job' => ['required', '疫情防控登记 用药人职业必填'],
            'symptom' => ['required', '疫情防控登记 症状必填'],
            'is_risk_area' => ['required', '疫情防控登记 是否去过中高风险地区必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        
        return true;
    }

    public function epidemicRegisterCreate($epidemicRegisterInfo, $orderInfo)
    {
        $data = [
            'order_id' => $orderInfo['order_id'],
            'user_id' => $orderInfo['user_id'],
            'company_id' => $orderInfo['company_id'],
            'distributor_id' => $orderInfo['distributor_id'],
            'name' => $epidemicRegisterInfo['name'],
            'mobile' => $epidemicRegisterInfo['mobile'],
            'cert_id' => $epidemicRegisterInfo['cert_id'],
            'temperature' => $epidemicRegisterInfo['temperature'],
            'job' => $epidemicRegisterInfo['job'],
            'symptom' => $epidemicRegisterInfo['symptom'],
            'symptom_des' => $epidemicRegisterInfo['symptom_des'] ?? '',
            'is_risk_area' => $epidemicRegisterInfo['is_risk_area'],
            'order_time' => $orderInfo['create_time'],
            'is_use' => 1,
        ];
        $filter = [
            'user_id' => $orderInfo['user_id'],
            'company_id' => $orderInfo['company_id'],
            'cert_id' => $epidemicRegisterInfo['cert_id'],
        ];
        $info = $this->getLists($filter, '*', 1, 1);
        if ($info) {
            $this->updateBy($filter, ['is_use' => 0]);
        }
        
        
        $this->create($data);
        
        return true;
    }

    public function epidemicRegisterListService($filter, $cols='*', $page = 1, $pageSize = -1, $orderBy = array())
    {
        $list = $this->lists($filter, $cols, $page, $pageSize, $orderBy);

        if (!$list['list']) {
            return $list;
        }
        $distributorIds = array_column($list['list'], 'distributor_id');

        $distributorService = new DistributorService();
        $distributorList = $distributorService->getLists(['distributor_id' => $distributorIds]);
        foreach ($list['list'] as &$v) {
            $v['created'] = date('Y-m-d H:i:s', $v['created']);
            foreach ($distributorList as $distributor) {
                if ($v['distributor_id'] == $distributor['distributor_id']) {
                    $v['distributor_name'] = $distributor['name'];
                }
            }
        }
        return $list;
    }
    
    public function __call($method, $parameters)
    {
        return $this->orderEpidemicRegister->$method(...$parameters);
    }

}
