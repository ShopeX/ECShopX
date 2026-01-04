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

namespace ShuyunBundle\Services;

use Dingo\Api\Exception\ResourceException;

use ShuyunBundle\Services\Client\Request;
use CompanysBundle\Services\RolesService;
use CompanysBundle\Services\EmployeeService;

/**
 * 管理员账号
 */
class OperatorsService
{
    public $role_name = '普通员工';
    public $role_version = 1;

    /**
     * 使用数云Code获取数云的管理员信息
     * @param string $code
     */
    public function getShuyunOperatorData($code)
    {
        $params = [
            'code' => $code,
        ];
        
        $client = new Request();
        $url = '/pcrm-account/1.0/shopex/userInfo';
        $resp = $client->get($url, $params);
        if ($resp->code != 0) {
            throw new ResourceException($resp->message);
        }
        return $resp->data;
    }

    /**
     * 创建普通管理员
     */
    public function createStaff($data)
    {
        // 获取角色 没有则创建
        $rolesService = new RolesService();
        $filter = [
            'company_id' => $data['company_id'],
            'role_name' => $this->role_name,
        ];
        $roleInfo = $rolesService->getInfo($filter);
        if (empty($roleInfo)) {
            $createRoleData = [
                'company_id' => $data['company_id'],
                'role_name' => $this->role_name,
                'permission' => $this->permission(),
                'version' => $this->role_version,
            ];
            $roleInfo = $rolesService->create($createRoleData);
        }
        // 创建普通管理员
        $createStaffData = [
            'company_id' => $data['company_id'],
            'mobile' => $data['phone'],
            'login_name' => $data['phone'],
            'username' => $data['userName'],
            'operator_type' => 'staff',
            'head_portrait' => '',
            'distributor_ids' => [],
            'shop_ids' => [],
            'contact' => 0,
            'password' => (string)rand(100000, 999999),
            'role_id' => [$roleInfo['role_id']],
        ];
        $employeeService = new EmployeeService();
        $operator = $employeeService->createOperatorStaff($createStaffData);
        return $operator;
    }

    private function permission()
    {
        $data['shopmenu_alias_name'] = [
            'index',
            'goodsphysical',
            'goodscategory',
            'goodsmaincategory',
            'goodsbrand',
            'goodsparams',
            'itemtags',
            'shippingtemplates',
            'rate',
            'brandmaterial',
            'arrivalnotice',
            'tradenormalorders',
            'aftersaleslist',
            'normalordersupload',
            'wl-logistics',
            'servicepayment',
            'aftersalesrefundlist',
            'order-Refunderrorlogs',
            'membermarketing',
            'coupongive',
            'couponsend',
            'marketingsfulldiscount',
            'marketingsfullminus',
            'marketingsfullgift',
            'marketingindex',
            'marketingseckill',
            'limitedtimesale',
            'SpecificCrowdDiscount',
            'memberpreference',
            'goodslimit',
            'marketingspluspricebuy',
            'marketingpackage',
            'groupsindex',
            'communitychief',
            'communityactivity',
            'communityorder',
            'communitygoods',
            'communitysetting',
            'achievement',
            'withdraw',
            'popularizesetting',
            'popularizelist',
            'promotersetting',
            'popularizedata',
            'popularizewithdraw',
            'popularizegoods',
            'taskbrokerage',
            'taskbrokeragecount',
            'marketingactivity',
            'ugcindex',
            'ugcflags',
            'ugctopic',
            'ugcreview',
            'ugcpoint',
            'ugctpos',
            'liveroomlist',
            'recommendlike',
            'Registrationactivity',
            'Registrationrecord',
            'formattrs',
            'formtemplate',
            'goodsstatistics',
            'orderstatistics',
            'member-statistics',
        ];
        return $data;
    }

    /**
     * 将开发配置数据，同步数云
     * @param  array $data 
     */
    public function developerDataToShuyun($data)
    {
        app('log')->info('开发配置同步调数云 data=====>'.var_export($data, true));
        $params = [
            'appKey' => $data['app_key'],
            'appSecret' => $data['app_secret'],
        ];
        $client = new Request($data['company_id']);
        $url = '/ucenter-mars-message/v1/subCallback/weixin/shopping';
        $resp = $client->json($url, $params);
        if ($resp->code != 0) {
            throw new ResourceException($resp->message);
        }
        return $resp->data;
    }

}
