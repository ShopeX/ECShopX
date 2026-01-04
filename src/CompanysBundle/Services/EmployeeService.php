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

namespace CompanysBundle\Services;

use AdaPayBundle\Services\AdapayLogService;
use CompanysBundle\Entities\DistributorWorkWechatRel;
use CompanysBundle\Entities\Operators;
use CompanysBundle\Entities\EmployeeRelRoles;
use CompanysBundle\Entities\Roles;
use CompanysBundle\Jobs\EmployeeJob;

use DistributionBundle\Entities\SelfDeliveryStaff;
use MerchantBundle\Services\MerchantService;

use Exception;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class EmployeeService
{
    /** @var \CompanysBundle\Repositories\OperatorsRepository */
    public $operatorsRepository;

    private $employeeRelRoleRepository;

    private $roleRepository;

    public function __construct()
    {
        // $this->operatorsRepository = app('registry')->getManager('default')->getRepository(Operators::class);
        $this->employeeRelRoleRepository = app('registry')->getManager('default')->getRepository(EmployeeRelRoles::class);
        // $this->roleRepository = app('registry')->getManager('default')->getRepository(Roles::class);
        $this->operatorsRepository = getRepositoryLangue(Operators::class);
        $this->roleRepository = getRepositoryLangue(Roles::class);
    }

    /**
     * 添加员工账号
     */
    public function createOperatorStaff($params)
    {
        $operatorParams['operator_type'] = $params['operator_type'];
        $operatorParams['login_name'] = $params['login_name'];
        $operatorParams['mobile'] = $params['mobile'];
        $operatorParams['company_id'] = $params['company_id'];
        $operatorParams['username'] = $params['username'];
        $operatorParams['head_portrait'] = $params['head_portrait'];
        $operatorParams['distributor_ids'] = $params['distributor_ids'] ?: [];
        $operatorParams['regionauth_id'] = $params['regionauth_id'] ?? 0;
        $operatorParams['shop_ids'] = $params['shop_ids'] ?: [];
        $operatorParams['password'] = $params['password'];
        $operatorParams['dealer_parent_id'] = $params['dealer_parent_id'] ?? '';
        $operatorParams['is_dealer_main'] = $params['is_dealer_main'] ?? 0;
        $operatorParams['is_distributor_main'] = $params['is_distributor_main'] ?? 0;
        $operatorParams['merchant_id'] = $params['merchant_id'] ?? 0;
        if ($params['contact'] ?: 0) {
            $operatorParams['contact'] = $params['contact'];
        }
        $operatorsService = new OperatorsService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $operator = $operatorsService->createOperator($operatorParams);

            if (isset($params['role_id']) && $params['role_id']) {
                foreach ($params['role_id'] as $roleId) {
                    $relRole = [
                        'role_id' => $roleId,
                        'operator_id' => $operator['operator_id'],
                        'company_id' => $params['company_id'],
                    ];
                    $this->employeeRelRoleRepository->create($relRole);
                }
            }

            if($params['operator_type'] =='self_delivery_staff'){
                $params['operator_id'] = $operator['operator_id'];
                $params['payment_fee'] = $params['payment_fee'] * 100;
                $params['shop_id'] = $params['merchant_id']??0;
                $selfDeliveryRepository = app('registry')->getManager('default')->getRepository(SelfDeliveryStaff::class);
                $selfDeliveryRepository->create($params);
            }
            $conn->commit();
            $eventData = [
                'company_id' => $operator['company_id'],
                'login_name' => $operator['login_name'],
                'mobile' => $operator['mobile'],
                'user_name' => $operator['username'],
                'password' => $operator['password'],
                'synctype' => 'add'
            ];
            $gotoJob = (new EmployeeJob($eventData))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

            if ($params['operator_type'] == 'dealer') {
                $logParams = [
                    'company_id' => $params['company_id'],
                    'name' => $params['username']
                ];
                $adapayLogService = new AdapayLogService();

                if (isset($operator['is_dealer_main']) && !$operator['is_dealer_main']) {
                    $relId = $operator['dealer_parent_id'];
                } else {
                    $relId = $operator['operator_id'];
                }

                $adapayLogService->logRecord($logParams, $params['operator_id'], 'create_operator_dealer', 'merchant');
                $adapayLogService->logRecord($logParams, $relId, 'create_operator_dealer', 'dealer');
            }

            return $operator;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     * 编辑员工账号
     */
    public function updateOperatorStaff($params, $filter)
    {
        $operatorParams['operator_type'] = $params['operator_type'] ?? 'staff';
        if (isset($params['password']) && $params['password']) {
            $operatorParams['password'] = $params['password'];
        }
        // $operatorParams['login_name'] = $params['login_name'];
        $operatorParams['mobile'] = $params['mobile'];
        $operatorParams['username'] = $params['username'];
        $operatorParams['head_portrait'] = $params['head_portrait'];
        $operatorParams['regionauth_id'] = $params['regionauth_id'] ?? 0;
        if (isset($params['distributor_ids'])) {
            $operatorParams['distributor_ids'] = $params['distributor_ids'] ?: [];
        }
        $operatorParams['shop_ids'] = $params['shop_ids'] ?: [];
        $operatorsService = new OperatorsService();
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $operator = $operatorsService->updateOperator($filter['operator_id'], $operatorParams);

            if (isset($params['role_id']) && $params['role_id']) {
                $this->employeeRelRoleRepository->deleteBy($filter);
                foreach ($params['role_id'] as $roleId) {
                    $relRole = [
                        'role_id' => $roleId,
                        'operator_id' => $filter['operator_id'],
                        'company_id' => $filter['company_id'],
                    ];
                    $this->employeeRelRoleRepository->create($relRole);
                }
            } else {
                $this->employeeRelRoleRepository->deleteBy($filter);
            }
            if($params['operator_type'] =='self_delivery_staff'){
                $params['payment_fee'] = $params['payment_fee'] * 100;
                $selfDeliveryRepository = app('registry')->getManager('default')->getRepository(SelfDeliveryStaff::class);
                $selfDeliveryRepository->updateOneBy(['operator_id'=>$operator['operator_id']],$params);
            }
            $conn->commit();

            // 更改的用户token需要更新
            (new AuthService())->setBlackTokenCache($filter['operator_id'], $params['operator_type']);

            $eventData = [
                'company_id' => $operator['company_id'],
                'login_name' => $operator['login_name'],
                'mobile' => $operator['mobile'],
                'user_name' => $operator['username'],
                'password' => $operator['password'],
                'synctype' => 'update'
            ];
            $gotoJob = (new EmployeeJob($eventData))->onQueue('slow');
            app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
            return $operator;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     *  获取员工账号及信息列表
     */
    public function getListStaff($filter, $page = 1, $pageSize = 500, $orderBy = ['created' => 'desc'])
    {
        if (!isset($filter['company_id'])) {
            throw new Exception("参数company_id错误");
        }

        $operator = $this->operatorsRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        $selfDeliveryStaffs = [];
        if($filter['operator_type'] == 'self_delivery_staff' && $operator['total_count'] > 0){
            $operator_id = array_column($operator['list'],'operator_id');
            if($operator_id){
                $selfDeliveryStaffRepository = app('registry')->getManager('default')->getRepository(SelfDeliveryStaff::class);
                $selfDeliveryStaffs = $selfDeliveryStaffRepository->getLists(['operator_id'=>$operator_id]);
                $selfDeliveryStaffs = array_column($selfDeliveryStaffs,null,'operator_id');
            }

        }
        foreach ($operator['list'] as &$value) {
            $value['role_data'] = $this->getRoleData($value['company_id'], $value['operator_id']);
            if(isset($selfDeliveryStaffs[$value['operator_id']])){
                $value = array_merge($value,$selfDeliveryStaffs[$value['operator_id']]);
            }
        }
        return $operator;
    }

    /**
     *  获取员工账号及信息
     */
    public function getInfoStaff($operatorId, $companyId = null)
    {
        $operator = $this->operatorsRepository->getInfo(['operator_id' => $operatorId, 'company_id' => $companyId]);
        if ($operator) {
            $operator['role_data'] = $this->getRoleData($operator['company_id'], $operator['operator_id']);
        }
        if($operator['operator_type'] =='self_delivery_staff'){
            $selfDeliveryStaffRepository = app('registry')->getManager('default')->getRepository(SelfDeliveryStaff::class);
            $selfDeliveryStaffs = $selfDeliveryStaffRepository->getInfo(['operator_id'=>$operator['operator_id']]);
            $operator = array_merge($operator,$selfDeliveryStaffs);
        }
        return $operator;
    }

    public function getRoleData($companyId, $operatorId)
    {
        $operator = $this->operatorsRepository->getInfo(['operator_id' => $operatorId, 'company_id' => $companyId]);
        $roleService = new RolesService();
        $relRle = $this->employeeRelRoleRepository->lists(['operator_id' => $operatorId, 'company_id' => $companyId]);
        $roleIds = array_column($relRle, 'role_id');
        $filter = [
            'company_id' => $companyId,
            'role_id' => $roleIds,
            'is_decode_permission' => false
        ];
        if ($operator['operator_type'] == 'distributor' && !$operator['is_distributor_main']) {
            if (!$operator['distributor_ids']) {
                return [];
            }
            $filter['distributor_id'] = $operator['distributor_ids'][0]['distributor_id'];
        }
        $roleList = $roleService->getList($filter);
        return $roleList['list'];
    }

    public function getRoleDataPermission($companyId, $operatorId)
    {
        $roleService = new RolesService();
        $relRle = $this->employeeRelRoleRepository->lists(['operator_id' => $operatorId, 'company_id' => $companyId]);
        if (!$relRle) {
            return null;
        }
        $roleIds = array_column($relRle, 'role_id');
        $roleList = $roleService->getList(['company_id' => $companyId, 'role_id' => $roleIds, 'is_decode_permission' => false]);

        $shopmenuAliasName = [];
        foreach ($roleList['list'] as $row) {
            $shopmenuAliasName = array_merge($shopmenuAliasName, ($row['permission']['shopmenu_alias_name'] ?? []));
        }

        return array_unique($shopmenuAliasName);
    }

    /**
     * 删除员工账号及信息
     */
    public function deleteStaff($operatorId, $companyId, $params = [])
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $operatorsData = $this->getInfoStaff($operatorId, $companyId);
            $operator = $this->operatorsRepository->deleteBy(['operator_id' => $operatorId, 'company_id' => $companyId]);
            $employeeRelRole = $this->employeeRelRoleRepository->deleteBy(['operator_id' => $operatorId, 'company_id' => $companyId]);
            if ($operator && $employeeRelRole) {
                // 移除绑定的企业微信信息
                $workWechatRelRepository = app('registry')->getManager('default')->getRepository(DistributorWorkWechatRel::class);
                $workWechatRelRepository->deleteBy(['operator_id' => $operatorId, 'company_id' => $companyId]);
                $conn->commit();
                $eventData = [
                    'company_id' => $companyId,
                    'mobile' => $operatorsData['mobile'],
                    'synctype' => 'del'
                ];
                $gotoJob = (new EmployeeJob($eventData))->onQueue('slow');
                app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);

                if ($operatorsData['operator_type'] == 'dealer') {
                    $logParams = [
                        'company_id' => $companyId,
                        'name' => $operatorsData['username']
                    ];

                    if (isset($operatorsData['is_dealer_main']) && !$operatorsData['is_dealer_main']) {
                        $relId = $operatorsData['dealer_parent_id'];
                    } else {
                        $relId = $operatorsData['operator_id'];
                    }

                    $merchantRelId = app('auth')->user()->get('operator_id');
                    (new AdapayLogService())->logRecord($logParams, $merchantRelId, 'delete_operator_dealer', 'merchant');
                    (new AdapayLogService())->logRecord($logParams, $relId, 'delete_operator_dealer', 'dealer');
                }

                return true;
            }
            throw new Exception('删除账号失败');
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }

    /**
     *  员工账号登录验证
     */
    public function employeeLogin($params)
    {
        if (preg_match("/^1[3456789]{1}\d{9}$/", $params['username'])) {
            $filter['mobile'] = $params['username'];
        } else {
            $filter['login_name'] = $params['username'];
        }
        $filter['operator_type'] = $params['logintype'] ?? 'staff';

        $operator = $this->operatorsRepository->getInfo($filter);
        if (!$operator) {
            throw new AccessDeniedHttpException('账号不存在');
        }
        if ($operator['is_disable'] == 1) {
            throw new AccessDeniedHttpException('账号已禁用');
        }

        $checkPassword = password_verify($params['password'], $operator['password']);
        if (!$checkPassword) {
            throw new AccessDeniedHttpException('账号密码错误，请重新登录');
        }
        // 如果是店铺管理员，检查关联商户是否可用
        if ($filter['operator_type'] == 'distributor') {
            $this->checkDistributorMerchantDisabled($operator['company_id'], $operator['merchant_id']);
        }
        return $operator;
    }

    // 验证码登录验证成功后可以直接获取登录信息
    public function getStaffInfoByMobile($mobile)
    {
        $filter['mobile'] = $mobile;
        $filter['operator_type'] = 'staff';
        $operator = $this->operatorsRepository->getInfo($filter);
        if (!$operator) {
            throw new AccessDeniedHttpException('账号不存在');
        }
        return $operator;
    }

    /**
     * 检查店铺相关的商户是否开启
     * @param  string $companyId  企业ID
     * @param  string $merchantId 商户ID
     */
    private function checkDistributorMerchantDisabled($companyId, $merchantId)
    {
        if (intval($merchantId) <= 0) {
            return true;
        }
        $merchantService = new MerchantService();
        $filter = [
            'company_id' => $companyId,
            'id' => $merchantId,
            'disabled' => false,
        ];
        $merchantInfo = $merchantService->getInfo($filter);
        if (!$merchantInfo) {
            throw new AccessDeniedHttpException('商户未开启，请确认后再重试');
        }
        return true;
    }
}
