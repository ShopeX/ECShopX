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

namespace EspierBundle\Middleware;

use Closure;
use CompanysBundle\Services\EmployeeService;
use CompanysBundle\Services\CompanysService;
use CompanysBundle\Ego\CompanysActivationEgo;
use SuperAdminBundle\Services\ShopMenuService;

use Exception;

class CheckActivedMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = app('auth')->user();
        if ($user) {
            //验证权限
            $router = $request->route();
            if ($user->get('source') != 'salesperson_workwechat') {
                $result = $this->checkPermission($user, $router);
                if (!$result) {
                    throw new Exception("您没有此操作的权限", 400500);
                }
            }

            //验证激活
            $companyId = app('auth')->user()->get('company_id');
            if (!$companyId) {
                throw new Exception("未激活", 400002);
            }

            if (app('auth')->user()->get('source') != 'salesperson') {
                $companysActivationEgo = new CompanysActivationEgo();
                $activateInfo = $companysActivationEgo->check($companyId);
                if (!$activateInfo['is_valid']) {
                    throw new Exception("未激活或者被禁止登入", 400002);
                }
            }

            $operatorType = $user->get('operator_type');
            $distributors = $user->get('distributor_ids');
            if (!is_array($distributors)) {
                $distributors = json_decode($distributors, true);
            }
            if ($operatorType == 'distributor' && !$distributors) {
                throw new Exception("权限信息有误", 400002);
            }

            $userAuthData = [];
            if ($operatorType == 'distributor') {
                $sid = 'select_distributor'.app('auth')->user()->get('operator_id').'-'.$companyId;
                $distributorId = app('redis')->connection('companys')->get($sid);
                if ($distributorId) {
                    $userAuthData['distributor_id'] = intval($distributorId);
                }
                $distributoreIds = array_column($distributors, 'distributor_id');
                if ($distributorId && !in_array($distributorId, $distributoreIds)) {
                    throw new Exception("您没有权限管理此店铺", 400002);
                }
                $userAuthData['distributorIds'] = $distributoreIds;
            } else {
                $distributorId = $request->get('distributor_id', 0);
                if (in_array($operatorType, ['staff']) && $distributors) {
                    // 后面增加区域来关联店铺功能
                    $regionauth_id = app('auth')->user()->get('regionauth_id');
                    if ($regionauth_id > 0) {
                        $distributoreIds = $distributors;
                    } else {
                        $distributoreIds = array_column($distributors, 'distributor_id');
                    }
                    if ($distributorId && is_numeric($distributorId) && !in_array($distributorId, $distributoreIds)) {
                        throw new Exception("您没有权限管理此店铺", 400002);
                    } elseif ($distributorId && is_array($distributorId) && count($distributorId) != count(array_intersect($distributorId, $distributoreIds))) {
                        throw new Exception("您没有权限管理此店铺", 400002);
                    }

                    $userAuthData['distributorIds'] = $distributoreIds;
                }
            }

            if ($userAuthData) {
                $request->merge($userAuthData);
                $request->attributes->add($userAuthData); // 添加参数
            }
        }

        return $next($request);
    }

    private function checkPermission($user, $router)
    {
        if (!config('common.check_superadmin_permission')) {
            if (!$user->get('operator_type') || $user->get('operator_type') == 'admin') {
                return true;
            }

            // 店铺账号 获取店铺id
            if ($user->get('operator_type') == 'distributor' && in_array($router[1]['as'], ['operator.select.distributor','distributor.list'])) {
                return true;
            }
        }

        $path = $router[1]['as'];
        $pathArr = ['companys.setting', 'account.roles.permission', 'operator.get.data', 'currency.default'];
        if (in_array($path, $pathArr)) {
            return true;
        }

        $deliveryStaffPathArr = ['order.deliverypackag.confirm', 'order.deliverystaff.confirm', 'order.deliverystaff.cancel', 'datacube.deliverystaff.data', 'datacube.deliverystaff.data.export'];
        if ($user->get('operator_type') == 'self_delivery_staff' && in_array($path, $deliveryStaffPathArr)) {
            return true;
        }

        $companyId = $user->get('company_id');
        $operatorId = $user->get('operator_id');
        $employeeService = new EmployeeService();
        $shopmenuAliasName = $employeeService->getRoleDataPermission($companyId, $operatorId);

        if (is_null($shopmenuAliasName) && $user->get('operator_type') == 'staff') {
            throw new Exception("帐号没有绑定角色，请联系管理员添加", 42014);
        }

        if ($shopmenuAliasName) {
            $filter['alias_name|in'] = $shopmenuAliasName;
        }
        if ($user->get('operator_type') == 'distributor') {
            $filter['version'] = 3;
        } else {
            $filter['version'] = 1;
        }

        switch ($user->get('operator_type')) {
            case 'distributor' ://店铺菜单
                $filter['version'] = 3;
                break;
            case 'dealer' ://经销商菜单
                $filter['version'] = 5;
                break;
            case 'merchant' ://商户菜单
                $filter['version'] = 6;
                break;
            case 'supplier' ://供应商菜单
                $filter['version'] = 7;
                break;
            default://默认，商家菜单
                $filter['version'] = 1;
        }

        $shopMenuService = new ShopMenuService();
        $apis = $shopMenuService->getApisByShopmenuAliasName($filter);
        if (in_array($path, $apis)) {
            return true;
        }

        throw new Exception("您没有操作权限【".$path."】", 400500);
    }
}
