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

namespace CompanysBundle\Http\Api\V1\Action;

use AdaPayBundle\Services\DealerService;
use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\CompanysService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\RolesService;
use AdaPayBundle\Services\OpenAccountService;

use Dingo\Api\Exception\StoreResourceFailedException;
use SupplierBundle\Services\SupplierService;

class RolesController extends BaseController
{
    private $rolesService;

    public function __construct(RolesService $RolesService)
    {
        $this->rolesService = new $RolesService();
    }

    /**
     * @SWG\Definition(
     *     definition="Role",
     *     type="object",
     *     @SWG\Property(property="role_id", type="integer", description="角色id"),
     *     @SWG\Property(property="company_id", type="integer", example="公司id", description=""),
     *     @SWG\Property(property="role_name", type="string", example="店长", description=""),
     *     @SWG\Property(property="role_source", type="string", example="platform", description=""),
     *     @SWG\Property(property="permission", type="object", description="",
     *         @SWG\Property(property="shopmenu_alias_name", type="array",  description="角色菜单权限", @SWG\Items(), example="['memberlist', 'member-list-view', 'member-export']"),
     *         @SWG\Property(property="version", type="string", example="1", description="版本"),
     *     ),
     *     @SWG\Property(property="created", type="string", example="1611640000", description="创建时间"),
     *     @SWG\Property(property="updated", type="string", example="1611640000", description="更新时间"),
     * )
     */

    /**
     * @SWG\Definition(
     *     definition="Child",
     *     type="object",
     *     @SWG\Property(property="shopmenu_id", type="string", example="1", description="菜单id"),
     *     @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *     @SWG\Property(property="name", type="string", example="商品", description="名称"),
     *     @SWG\Property(property="url", type="string", example="/entity/goods/goodsphysical", description="链接地址"),
     *     @SWG\Property(property="sort", type="string", example="2", description="排序"),
     *     @SWG\Property(property="is_menu", type="boolean", description="是否为菜单"),
     *     @SWG\Property(property="pid", type="string", example="1", description="上级菜单id"),
     *     @SWG\Property(property="icon", type="string", example="shopping-bag", description="菜单图标"),
     *     @SWG\Property(property="is_show", type="boolean", description="是否显示"),
     *     @SWG\Property(property="alias_name", type="string", example="entity", description="别名"),
     *     @SWG\Property(property="version", type="string", example="1", description="版本"),
     *     @SWG\Property(property="menu_type", type="string", example="all", description="菜单类型"),
     *     @SWG\Property(property="disabled", type="booLean", description="是否无效"),
     *     @SWG\Property(property="updated", type="string", example="1572338485", description="菜单id"),
     *     @SWG\Property(property="created", type="string", example="1572338485", description="菜单id"),
     *     @SWG\Property(property="level", type="string", example="1", description="层级"),
     *     @SWG\Property(property="isChildrenMenu", type="boolean", example="1", description="是否子菜单"),
     *     @SWG\Property(property="children", type="array", description="子菜单", @SWG\Items(
     *             @SWG\Property(property="shopmenu_id", type="string", example="1", description="菜单id"),
     *             @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *             @SWG\Property(property="name", type="string", example="商品", description="名称"),
     *             @SWG\Property(property="url", type="string", example="/entity/goods/goodsphysical", description="链接地址"),
     *             @SWG\Property(property="sort", type="string", example="2", description="排序"),
     *             @SWG\Property(property="is_menu", type="boolean", description="是否为菜单"),
     *             @SWG\Property(property="pid", type="string", example="1", description="上级菜单id"),
     *             @SWG\Property(property="icon", type="string", example="shopping-bag", description="菜单图标"),
     *             @SWG\Property(property="is_show", type="boolean", description="是否显示"),
     *             @SWG\Property(property="alias_name", type="string", example="entity", description="别名"),
     *             @SWG\Property(property="version", type="string", example="1", description="版本"),
     *             @SWG\Property(property="menu_type", type="string", example="all", description="菜单类型"),
     *             @SWG\Property(property="disabled", type="booLean", description="是否无效"),
     *             @SWG\Property(property="updated", type="string", example="1572338485", description="菜单id"),
     *             @SWG\Property(property="created", type="string", example="1572338485", description="菜单id"),
     *             @SWG\Property(property="level", type="string", example="1", description="层级"),
     *             @SWG\Property(property="isChildrenMenu", type="boolean", example="1", description="是否子菜单"),
     *         )
     *     )
     * )
     */


    /**
     * @SWG\Post(
     *     path="/roles/management",
     *     summary="创建企业员工角色",
     *     tags={"企业"},
     *     description="创建企业员工角色",
     *     operationId="createDataRoles",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="role_name",
     *         in="query",
     *         description="角色名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="role_source",
     *         in="query",
     *         description="角色平台来源",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="permission",
     *         in="query",
     *         description="角色权限",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/Role"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function createDataRole(Request $request)
    {
        // This module is part of ShopEx EcShopX system
        $params = $request->all('role_name', 'permission');
        $params['role_source'] = $request->get('role_source', 'platform');

        $rules = [
            'role_name' => ['required', '角色名称必填'],
            'role_source' => ['required', '角色平台来源'],
            'permission' => ['required', '请选中角色菜单权限'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (!isset($params['permission']['shopmenu_alias_name'])) {
            throw new StoreResourceFailedException('请选中角色菜单权限');
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['operator_name'] = app('auth')->user()->get('username');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $params['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        $result = $this->rolesService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Patch(
     *     path="/roles/management/{role_id}",
     *     summary="更新企业员工角色",
     *     tags={"企业"},
     *     description="更新企业员工角色",
     *     operationId="updateDataRole",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="ID",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="role_name",
     *         in="query",
     *         description="角色名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="permission",
     *         in="query",
     *         description="角色权限",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/Role"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateDataRole($role_id, Request $request)
    {
        $params = $request->all('role_id', 'role_name', 'permission');
        $params['role_source'] = $request->get('role_source', 'platform');

        $rules = [
            'permission' => ['required', '请选中角色菜单权限'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }
        if (!isset($params['permission']['shopmenu_alias_name'])) {
            throw new StoreResourceFailedException('请选中角色菜单权限');
        }

        $filter['role_id'] = $params['role_id'];
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        $params['operator_name'] = app('auth')->user()->get('username');
        $result = $this->rolesService->updateBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/roles/management/{role_id}",
     *     summary="删除角色",
     *     tags={"企业"},
     *     description="删除角色",
     *     operationId="deleteDataRole",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="path",
     *         description="ID",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteDataRole($role_id)
    {
        $company_id = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        $distributorId = 0;
        if ($operatorType == 'distributor') {
            $distributorId = app('auth')->user()->get('distributor_id');
        }

        $result = $this->rolesService->deleteBy($role_id, $company_id, $distributorId);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/roles/management",
     *     summary="获取角色列表",
     *     tags={"企业"},
     *     description="获取角色列表",
     *     operationId="getDataList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="ID",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="role_name",
     *         in="query",
     *         description="角色名称",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="页值",
     *         type="integer",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="total_count", type="integer", example="1", description="总数"),
     *                 @SWG\Property(property="list", type="array", description="",
     *                     @SWG\Items(
     *                        @SWG\Property(property="role_id", type="integer", description="角色id"),
     *                        @SWG\Property(property="company_id", type="integer", example="公司id", description=""),
     *                        @SWG\Property(property="role_name", type="string", example="店长", description=""),
     *                        @SWG\Property(property="role_source", type="string", example="platform", description=""),
     *                        @SWG\Property(property="permission", type="object", description="",
     *                            @SWG\Property(property="shopmenu_alias_name", type="array",  description="角色菜单权限", @SWG\Items(), example="['memberlist', 'member-list-view', 'member-export']"),
     *                        @SWG\Property(property="version", type="string", example="1", description="版本"),
     *
     *                        ),
     *                   ),
     *                   ),
     *                @SWG\Property(property="created", type="string", example="1611640000", description="创建时间"),
     *                @SWG\Property(property="updated", type="string", example="1611640000", description="更新时间"),
     *                @SWG\Property(property="permission_tree", type="array", description="",
     *                    @SWG\Items(
     *                        ref="#/definitions/Child"
     *                    )
     *                ),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDataList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 1000);
        $orderBy = ['role_id' => 'ASC'];
        if ($request->get('role_id')) {
            $filter['role_id'] = $request->get('role_id');
        }
        if ($request->get('role_name')) {
            $filter['role_name'] = $request->get('role_name');
        }
        $filter['role_source'] = $request->get('role_source', 'platform');
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'distributor') {
            $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        }
        $result = $this->rolesService->getList($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/roles/management/{role_id}",
     *     summary="获取角色详情",
     *     tags={"企业"},
     *     description="获取角色详情",
     *     operationId="getDataInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="role_id",
     *         in="query",
     *         description="ID",
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="role_name",
     *         in="query",
     *         description="角色名称",
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 ref="#/definitions/Role"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDataInfo($role_id)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['role_id'] = $role_id;
        $result = $this->rolesService->getInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/permission",
     *     summary="获取权限详情",
     *     tags={"企业"},
     *     description="获取权限详情",
     *     operationId="getPermission",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="shopmenu_id", type="string", example="1", description="菜单id"),
     *                     @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *                     @SWG\Property(property="name", type="string", example="商品", description="名称"),
     *                     @SWG\Property(property="url", type="string", example="/entity/goods/goodsphysical", description="链接地址"),
     *                     @SWG\Property(property="sort", type="string", example="2", description="排序"),
     *                     @SWG\Property(property="is_menu", type="boolean", description="是否为菜单"),
     *                     @SWG\Property(property="pid", type="string", example="1", description="上级菜单id"),
     *                     @SWG\Property(property="icon", type="string", example="shopping-bag", description="菜单图标"),
     *                     @SWG\Property(property="is_show", type="boolean", description="是否显示"),
     *                     @SWG\Property(property="alias_name", type="string", example="entity", description="别名"),
     *                     @SWG\Property(property="version", type="string", example="1", description="版本"),
     *                     @SWG\Property(property="menu_type", type="string", example="all", description="菜单类型"),
     *                     @SWG\Property(property="disabled", type="booLean", description="是否无效"),
     *                     @SWG\Property(property="updated", type="string", example="1572338485", description="菜单id"),
     *                     @SWG\Property(property="created", type="string", example="1572338485", description="菜单id"),
     *                     @SWG\Property(property="level", type="string", example="1", description="层级"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getPermission(Request $request)
    {
        $menu_version = $request->input('version');
        $userAuth = app('auth')->user();        
        $companyId = $userAuth->get('company_id');
        $operator_type = $userAuth->get('operator_type');
        $operator_id = $userAuth->get('operator_id');

        $result = app('authorization')->getMenu($userAuth, $menu_version);
        //判断商户是否开启pc模板
        $service = new CompanysService();
        $companysInfo = $service->getInfo(['company_id' => $companyId]);

        //仅超级管理员可见的菜单
        $superPermission = ['/setting/operatorlogs'];//操作日志
        foreach ($result as $k => $v) {
            if (!isset($v['children']) || !$v['children']) {
                if ($v['url'] == '/shopadmin/applications') {
                    unset($result[$k]);
                }
                continue;
            }

            foreach ($v['children'] as $kk => $vv) {
                if (in_array($vv['url'], $superPermission)) {
                    $result[$k]['children'][$kk]['is_super'] = 'Y';
                    if ($userAuth->get('operator_type') != 'admin') {
                        unset($result[$k]['children'][$kk]);
                    }
                }

                if (($companysInfo['is_open_pc_template'] ?? 2) == 2) {
                    if (($vv['url'] ?? '') == '/wxapp/pcmall') {
                        unset($result[$k]['children'][$kk]);
                    }
                }

                if (($companysInfo['is_open_domain_setting'] ?? 2) == 2) {
                    if (($vv['url'] ?? '') == '/setting/domain_setting') {
                        unset($result[$k]['children'][$kk]);
                    }
                }
            }
        }

        //adapay菜单控制
        //根据开户状态决定需要禁用的菜单项
        // $adapayDenyMenus = config('adapay.deny_menus');
        // $denyMenus = [];        
        // if ($operator_type == 'dealer') {
        //     $dealerService = new DealerService();
        //     $isMain = $dealerService->isDealerMain($operator_id);
        //     if (!$isMain) {
        //         $adapayDenyMenus[$operator_type]['succ'] = array_merge($adapayDenyMenus[$operator_type]['succ'], $adapayDenyMenus[$operator_type]['sub_deny']);
        //         $adapayDenyMenus[$operator_type]['fail'] = array_merge($adapayDenyMenus[$operator_type]['fail'], $adapayDenyMenus[$operator_type]['sub_deny']);
        //     }
        // }
        // $state = (new OpenAccountService())->isOpenAccount($companyId);

        // if ($state != 'ADMIN_NO_ACCOUNT') {
        //     if ($state == 'SUCCESS') {
        //         $denyMenus = $adapayDenyMenus[$operator_type]['succ'] ?? [];
        //     } else {
        //         $denyMenus = $adapayDenyMenus[$operator_type]['fail'] ?? [];
        //     }
        // } else {
        //     if (isset($adapayDenyMenus[$operator_type]['admin_fail'])) {
        //         $denyMenus = $adapayDenyMenus[$operator_type]['admin_fail'];
        //     } else {
        //         //店铺端｜经销商端  如果主商户未开户  那么有关adapay的菜单全部隐藏掉
        //         $denyMenus = array_merge($adapayDenyMenus[$operator_type]['succ'], $adapayDenyMenus[$operator_type]['fail']);
        //     }
        // }

        // if ($denyMenus && $result) {
        //     $this->denyMenus($result, $denyMenus);
        // }
        
        //处理供应商的菜单
        $supplierService = new SupplierService();
        $supplierService->setMenu($operator_id, $operator_type, $result);

        $result = array_values($result);
        foreach ($result as $key => $val) {
            if (isset($val['children'])) {
                $result[$key]['children'] = array_values($val['children']);
            }
        }

        return $this->response->array($result);
    }

    //禁用菜单
    private function denyMenus(&$result, $denyMenus)
    {
        foreach ($denyMenus as $key => $value) {
            $this->recursion($key, $value, $result);
        }
    }

    private function recursion($alias_name, $level, &$menus)
    {
        foreach ($menus as $key => $value) {
            if (!isset($value['level'])) {
                continue;
            }

            if ($value['level'] < $level && ($value['isChildrenMenu'] ?? false)) {
                if ($this->recursion($alias_name, $level, $menus[$key]['children'])) {
                    if (!$menus[$key]['children']) {
                        unset($menus[$key]['isChildrenMenu'], $menus[$key]['children']);
                    }
                    return true;
                }
            }
            if ($value['level'] == $level) {
                if ($alias_name == $value['url']) {
                    unset($menus[$key]);
                    $menus = array_values($menus);
                    return true;
                }
            }
        }
        return false;
    }
}
