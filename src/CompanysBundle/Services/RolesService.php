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

use CompanysBundle\Entities\Roles;
use CompanysBundle\Entities\EmployeeRelRoles;
use SuperAdminBundle\Services\ShopMenuService;

class RolesService
{
    public $entityRepository;

    /**
     * ShopsService 构造函数.
     */
    public function __construct()
    {
        // $this->entityRepository = app('registry')->getManager('default')->getRepository(Roles::class);
        $this->entityRepository = getRepositoryLangue(Roles::class);
    }

    public function deleteBy($roleId, $companyId, $distributorId)
    {
        $conn = app('registry')->getConnection('default');
        $conn->beginTransaction();
        try {
            $filter['role_id'] = $roleId;
            $filter['company_id'] = $companyId;
            $employeeRelRoles = app('registry')->getManager('default')->getRepository(EmployeeRelRoles::class);
            $relRole = $employeeRelRoles->getInfo($filter);
            if ($relRole) {
                throw new \Exception("该角色有账号关联，不可删除");
            }

            if ($distributorId) {
                $filter['distributor_id'] = $distributorId;
            }
            $result = $this->entityRepository->deleteBy($filter);
            if (!$result) {
                throw new \Exception("删除角色失败");
            }
            $conn->commit();
            return true;
        } catch (\Exception $e) {
            $conn->rollback();
            throw $e;
        }
    }
    public function getList($filter, $page = 1, $pageSize = 100, $orderBy = ['role_id' => 'asc'])
    {
        $isDecodePermission = true;
        if (isset($filter['is_decode_permission'])) {
            $isDecodePermission = $filter['is_decode_permission'];
            unset($filter['is_decode_permission']);
        }

        $shopMenuService = new ShopMenuService();

        $rolesList = $this->entityRepository->lists($filter, '*', $page, $pageSize, $orderBy);
        foreach ($rolesList['list'] as &$data) {
            if ($data['permission'] && isset($data['permission']['shopmenu_alias_name']) && $isDecodePermission) {
                $menuFilter['disabled'] = 0;
                $menuFilter['company_id'] = $data['company_id'];
                $menuFilter['version'] = $data['role_source'] == 'platform' ? 1 : 3;
                $data['permission_tree'] = $shopMenuService->getRoleShopMenuTree($menuFilter, $data['permission']['shopmenu_alias_name']);
            }
        }
        return $rolesList;
    }

    public function getDataInfo($roleId, $companyId)
    {
        $filter['role_id'] = $roleId;
        $filter['company_id'] = $companyId;
        $roleInfo = $this->entityRepository->getInfo($filter);
        return $roleInfo;
    }

    public function getPermissionTree($filter = array())
    {
        $shopMenuService = new ShopMenuService();
        $menucount = $shopMenuService->count(['company_id' => $filter['company_id'], 'version' => $filter['version'], 'disabled' => 0]);
        $company_id = $filter['company_id'];
        if ($menucount <= 0) { // 如果没有商家独有菜单，则取默认菜单
            $company_id = 0;
        }
        $menuFilter['company_id'] = $company_id;
        $menuFilter['disabled'] = 0;
        $menuFilter['version'] = $filter['version'] ?? 1; // 商家版菜单

        // 判断是否有不等于条件
        if (!empty($filter['disabled_menus'])) {
            $menuFilter['alias_name|notIn'] = $filter['disabled_menus'];
        }
        if (in_array($filter['operator_type'], ['staff', 'distributor', 'dealer'])) {
            $employeeService = new EmployeeService();
            $shopmenuAliasName = $employeeService->getRoleDataPermission($filter['company_id'], $filter['id']);

            if (is_null($shopmenuAliasName) && $filter['operator_type'] == 'staff') {
                throw new \Exception("帐号没有绑定角色，请联系管理员添加", 42014);
            }

            $menuType = $shopMenuService->getMenuTypeByCompanyId($filter['company_id']);
            $result = $shopMenuService->getRoleShopMenuTree($menuFilter, $shopmenuAliasName);
            $result = $shopMenuService->helperFilterSubMenuType($result, $menuType['menu_type']);
        } else {
            // 判断当前商家是否开启自定义的菜单
            // 如果没有开启则查询通用菜单权限
            $menuType = $shopMenuService->getMenuTypeByCompanyId($filter['company_id']);
            $data = $shopMenuService->getShopMenu($menuFilter, false, true, $menuType['menu_type']);
            $result = $data['tree'];
        }
        return $result;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
