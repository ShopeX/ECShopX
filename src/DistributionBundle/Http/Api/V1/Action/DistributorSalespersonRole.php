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

namespace DistributionBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use DistributionBundle\Services\DistributorSalesmanRoleService;
use Dingo\Api\Exception\ResourceException;

class DistributorSalespersonRole extends Controller
{
    /**
     * @SWG\get(
     *     path="/distributor/salesperson/role",
     *     summary="获取门店角色列表",
     *     tags={"店铺"},
     *     description="获取门店角色列表",
     *     operationId="getRoleList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="string"),
     *     @SWG\Parameter( name="page_size", in="query", description="分页条数", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="salesman_role_id", type="string", example="7", description=""),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="role_name", type="string", example="无权限", description="角色名称"),
     *                          @SWG\Property( property="rule_ids", type="string", example="[]", description="导购员角色类型(DC2Type:json_array)"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getRoleList(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 10);

        $filter = [
            'company_id' => $companyId,
        ];
        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();

        $result = $distributorSalesmanRoleService->lists($filter, '*', $page, $pageSize, ['salesman_role_id' => 'desc']);
        return $this->response->array($result);
    }

    /**
     * @SWG\post(
     *     path="/distributors/salesperson/role/{salesmanRoleId}",
     *     summary="获取门店角色",
     *     tags={"店铺"},
     *     description="获取门店角色",
     *     operationId="getRoleInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesmanRoleId", in="path", description="门店角色id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesman_role_id", type="string", example="10", description=""),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="role_name", type="string", example="test", description="角色名称"),
     *                  @SWG\Property( property="rule_ids", type="array",
     *                      @SWG\Items( type="string", example="1", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function getRoleInfo($salesmanRoleId)
    {
        $companyId = app('auth')->user()->get('company_id');

        $filter = [
            'salesman_role_id' => $salesmanRoleId,
            'company_id' => $companyId,
        ];
        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();

        $result = $distributorSalesmanRoleService->getInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\post(
     *     path="/distributors/salesperson/role",
     *     summary="创建门店角色",
     *     tags={"店铺"},
     *     description="创建门店角色",
     *     operationId="createRole",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="role_name", in="formData", description="门店角色名称", required=true, type="string"),
     *     @SWG\Parameter( name="rule_ids", in="formData", description="权限集合", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesman_role_id", type="string", example="10", description=""),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="role_name", type="string", example="test", description="角色名称"),
     *                  @SWG\Property( property="rule_ids", type="array",
     *                      @SWG\Items( type="string", example="1", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function createRole(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $roleName = $request->input('role_name', '');
        $ruleIds = $request->input('rule_ids', []);

        if (!$roleName) {
            throw new ResourceException(trans('DistributionBundle/Controllers/DistributorSalespersonRole.role_not_empty'));
        }
        if (!is_array($ruleIds)) {
            $ruleIds = [];
        }

        $data = [
            'company_id' => $companyId,
            'role_name' => $roleName,
            'rule_ids' => $ruleIds
        ];
        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();

        $result = $distributorSalesmanRoleService->create($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\put(
     *     path="/distributors/salesperson/role/{salesmanRoleId}",
     *     summary="编辑门店角色",
     *     tags={"店铺"},
     *     description="编辑门店角色",
     *     operationId="updateRole",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesman_role_id", in="path", description="门店角色id", required=true, type="string"),
     *     @SWG\Parameter( name="role_name", in="formData", description="门店角色名称", required=true, type="string"),
     *     @SWG\Parameter( name="rule_ids", in="formData", description="权限集合", required=true, type="string"),
     *     @SWG\Parameter( name="company_id", in="formData", description="公司id",  type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="salesman_role_id", type="string", example="10", description=""),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="role_name", type="string", example="test1", description="角色名称"),
     *                  @SWG\Property( property="rule_ids", type="array",
     *                      @SWG\Items( type="string", example="1", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function updateRole($salesmanRoleId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $filter = [
            'salesman_role_id' => $salesmanRoleId,
            'company_id' => $companyId,
        ];

        $roleName = $request->input('role_name', '');
        $ruleIds = $request->input('rule_ids', []);

        if (!$roleName) {
            throw new ResourceException(trans('DistributionBundle/Controllers/DistributorSalespersonRole.role_not_empty'));
        }
        if (!is_array($ruleIds)) {
            $ruleIds = [];
        }

        $data = [
            'company_id' => $companyId,
            'role_name' => $roleName,
            'rule_ids' => $ruleIds
        ];
        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();

        $result = $distributorSalesmanRoleService->updateOneBy($filter, $data);
        return $this->response->array($result);
    }

    /**
     * @SWG\delete(
     *     path="/distributors/salesperson/role/{salesmanRoleId}",
     *     summary="删除门店角色",
     *     tags={"店铺"},
     *     description="删除门店角色",
     *     operationId="delRole",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="salesmanRoleId", in="path", description="门店角色id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     *  )
     * )
     */
    public function delRole($salesmanRoleId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'salesman_role_id' => $salesmanRoleId,
        ];
        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();
        $result = $distributorSalesmanRoleService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }
}
