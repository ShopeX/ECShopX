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

namespace EmployeePurchaseBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Jobs\ExportFileJob;
use EspierBundle\Traits\GetExportServiceTraits;

use EmployeePurchaseBundle\Services\EmployeesService;

class Employee extends Controller
{
    use GetExportServiceTraits;

    /**
     * @SWG\Get(
     *     path="/employees",
     *     summary="获取企业员工列表",
     *     tags={"内购"},
     *     description="获取企业员工列表",
     *     operationId="getList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="当前页数",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="显示数量",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="员工手机号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="account",
     *         in="query",
     *         description="账号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         description="邮箱",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="member_mobile",
     *         in="query",
     *         description="会员手机号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="enterprise_id",
     *         in="query",
     *         description="企业id",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          @SWG\Property( property="id", type="string", description="员工ID"),
     *                          @SWG\Property( property="company_id", type="string", description="公司id"),
     *                          @SWG\Property( property="name", type="string", description="姓名"),
     *                          @SWG\Property( property="mobile", type="string", description="手机号"),
     *                          @SWG\Property( property="account", type="string", description="账号"),
     *                          @SWG\Property( property="auth_code", type="string", description="校验密码"),
     *                          @SWG\Property( property="email", type="string", description="邮箱"),
     *                          @SWG\Property( property="enterprise_id", type="string", description="企业id"),
     *                          @SWG\Property( property="enterprise_sn", type="string", description="企业编号"),
     *                          @SWG\Property( property="enterprise_name", type="string", description="企业名称"),
     *                          @SWG\Property( property="user_id", type="string", description="用户ID"),
     *                          @SWG\Property( property="member_mobile", type="string", description="会员手机号"),
     *                          @SWG\Property( property="created", type="string", description="创建"),
     *                          @SWG\Property( property="updated", type="string", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'mobile', 'account', 'email', 'member_mobile', 'enterprise_id', 'distributor_id');
        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:100','每页显示数量最大100'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = intval($params['page']);
        $pageSize = intval($params['pageSize']);

        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $filter['company_id'] = $companyId;
        $filter = ['company_id' => $companyId];
        if ($authInfo['operator_type'] == 'distributor') {
            $filter['distributor_id'] = $authInfo['distributor_id'];
        } else {
            if (isset($params['distributor_id']) && $params['distributor_id'] != '') {
                $filter['distributor_id'] = $params['distributor_id'];
            }
        }
        if (isset($params['mobile']) && $params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }
        if (isset($params['account']) && $params['account']) {
            $filter['account'] = $params['account'];
        }
        if (isset($params['email']) && $params['email']) {
            $filter['email'] = $params['email'];
        }
        if (isset($params['member_mobile']) && $params['member_mobile']) {
            $filter['member_mobile'] = $params['member_mobile'];
        }
        if (isset($params['enterprise_id']) && $params['enterprise_id']) {
            $filter['enterprise_id'] = $params['enterprise_id'];
        }
        $orderBy = ['created' => 'DESC'];
        $employeesService = new EmployeesService();
        $result = $employeesService->getEmployeeListWithRel($filter, $page, $pageSize, $orderBy);
        // 是否有权限查看加密数据
        $datapassBlock = $request->get('x-datapass-block', 0);
        $result['datapass_block'] = $datapassBlock;
        if ($datapassBlock && $result['list']) {
            foreach ($result['list'] as $key => $value) {
                $value['mobile'] = data_masking('mobile', $value['mobile']);
                $value['name'] = data_masking('truename', $value['name']);
                $value['member_mobile'] = data_masking('mobile', $value['member_mobile']);
                $result['list'][$key] = $value;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/employee/{employeeId}",
     *     summary="获取企业员工信息",
     *     tags={"内购"},
     *     description="获取企业员工信息",
     *     operationId="getInfo",
     *     @SWG\Parameter(
     *         name="employeeId",
     *         in="path",
     *         description="员工ID",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 @SWG\Property( property="id", type="string", description="员工ID"),
     *                 @SWG\Property( property="company_id", type="string", description="公司id"),
     *                 @SWG\Property( property="name", type="string", description="姓名"),
     *                 @SWG\Property( property="mobile", type="string", description="手机号"),
     *                 @SWG\Property( property="account", type="string", description="账号"),
     *                 @SWG\Property( property="auth_code", type="string", description="校验密码"),
     *                 @SWG\Property( property="email", type="string", description="邮箱"),
     *                 @SWG\Property( property="enterprise_id", type="string", description="企业id"),
     *                 @SWG\Property( property="created", type="string", description="创建"),
     *                 @SWG\Property( property="updated", type="string", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getInfo($employeeId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $filter = [
            'id' => $employeeId,
            'company_id' => $companyId,
        ];
        $employeesService = new EmployeesService();
        $result = $employeesService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/employee",
     *     summary="添加企业员工",
     *     tags={"内购"},
     *     description="添加企业员工",
     *     operationId="create",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         description="员工姓名",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="formData",
     *         description="员工手机号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="account",
     *         in="formData",
     *         description="账号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="auth_code",
     *         in="formData",
     *         description="校验密码",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="formData",
     *         description="邮箱",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="enterprise_id",
     *         in="formData",
     *         description="企业id",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 @SWG\Property( property="id", type="string", description="员工ID"),
     *                 @SWG\Property( property="company_id", type="string", description="公司id"),
     *                 @SWG\Property( property="name", type="string", description="姓名"),
     *                 @SWG\Property( property="mobile", type="string", description="手机号"),
     *                 @SWG\Property( property="account", type="string", description="账号"),
     *                 @SWG\Property( property="auth_code", type="string", description="校验密码"),
     *                 @SWG\Property( property="email", type="string", description="邮箱"),
     *                 @SWG\Property( property="enterprise_id", type="string", description="企业id"),
     *                 @SWG\Property( property="created", type="string", description="创建"),
     *                 @SWG\Property( property="updated", type="string", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function create(Request $request)
    {
        $params = $request->all('name', 'mobile', 'account', 'auth_code', 'email', 'enterprise_id');
        $authInfo = app('auth')->user()->get();
        $distributor_id = $authInfo['distributor_id'];
        $operator_id = $authInfo['operator_id'];
        $params['distributor_id'] = $distributor_id;
        $params['operator_id'] = $operator_id;
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = 0;
        $rules = [
            'name' => ['required', '姓名必填'],
            'enterprise_id' => ['required', '企业必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $employeesService = new EmployeesService();
        $result = $employeesService->create($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/employee/{employeeId}",
     *     summary="更新企业员工信息",
     *     tags={"内购"},
     *     description="更新企业员工信息",
     *     operationId="update",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="employeeId",
     *         in="path",
     *         description="员工ID",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="name",
     *         in="formData",
     *         description="员工姓名",
     *         required=false,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="formData",
     *         description="员工手机号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="account",
     *         in="formData",
     *         description="账号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="auth_code",
     *         in="formData",
     *         description="校验密码",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="formData",
     *         description="邮箱",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 @SWG\Property( property="id", type="string", description="员工ID"),
     *                 @SWG\Property( property="company_id", type="string", description="公司id"),
     *                 @SWG\Property( property="name", type="string", description="姓名"),
     *                 @SWG\Property( property="mobile", type="string", description="手机号"),
     *                 @SWG\Property( property="account", type="string", description="账号"),
     *                 @SWG\Property( property="auth_code", type="string", description="校验密码"),
     *                 @SWG\Property( property="email", type="string", description="邮箱"),
     *                 @SWG\Property( property="enterprise_id", type="string", description="企业id"),
     *                 @SWG\Property( property="created", type="string", description="创建"),
     *                 @SWG\Property( property="updated", type="string", description="修改时间"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function update($employeeId, Request $request)
    {
        $params = $inputdata = $request->all('name', 'mobile', 'account', 'auth_code', 'email');
        $authInfo = app('auth')->user()->get();
        $distributor_id = $authInfo['distributor_id'];
        $operator_id = $authInfo['operator_id'];
        $params['distributor_id'] = $distributor_id;
        $params['operator_id'] = $operator_id;
        $companyId = $authInfo['company_id'];
        $filter = [
            'company_id' => $companyId,
            'id' => $employeeId,
        ];

        $employeesService = new EmployeesService();
        $result = $employeesService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/employee/{employeeId}",
     *     summary="删除企业员工信息",
     *     tags={"内购"},
     *     description="删除企业员工信息",
     *     operationId="delete",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="employeeId",
     *         in="path",
     *         description="员工ID",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function delete($employeeId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $filter = [
            'company_id' => $companyId,
            'id' => $employeeId,
        ];

        // todo...记录删除用户的管理员
        $operatorId = app('auth')->user()->get('operator_id');

        $employeesService = new EmployeesService();
        $result = $employeesService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/employees/export",
     *     summary="导出企业员工信息",
     *     tags={"内购"},
     *     description="导出企业员工信息",
     *     operationId="exportData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="员工手机号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="account",
     *         in="query",
     *         description="账号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="email",
     *         in="query",
     *         description="邮箱",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="member_mobile",
     *         in="query",
     *         description="会员手机号",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="enterprise_id",
     *         in="query",
     *         description="企业id",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          @SWG\Property( property="id", type="string", description="员工ID"),
     *                          @SWG\Property( property="company_id", type="string", description="公司id"),
     *                          @SWG\Property( property="name", type="string", description="姓名"),
     *                          @SWG\Property( property="mobile", type="string", description="手机号"),
     *                          @SWG\Property( property="account", type="string", description="账号"),
     *                          @SWG\Property( property="auth_code", type="string", description="校验密码"),
     *                          @SWG\Property( property="email", type="string", description="邮箱"),
     *                          @SWG\Property( property="enterprise_id", type="string", description="企业id"),
     *                          @SWG\Property( property="enterprise_sn", type="string", description="企业编号"),
     *                          @SWG\Property( property="enterprise_name", type="string", description="企业名称"),
     *                          @SWG\Property( property="user_id", type="string", description="用户ID"),
     *                          @SWG\Property( property="member_mobile", type="string", description="会员手机号"),
     *                          @SWG\Property( property="created", type="string", description="创建"),
     *                          @SWG\Property( property="updated", type="string", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function exportData(Request $request)
    {
        $params = $request->all('mobile', 'account', 'email', 'member_mobile', 'enterprise_id', 'distributor_id');

        $authInfo = app('auth')->user()->get();
        $companyId = $authInfo['company_id'];
        $filter['company_id'] = $companyId;
        $filter = ['company_id' => $companyId];
        if ($authInfo['operator_type'] == 'distributor') {
            $filter['distributor_id'] = $authInfo['distributor_id'];
        } else {
            if (isset($params['distributor_id']) && $params['distributor_id'] != '') {
                $filter['distributor_id'] = $params['distributor_id'];
            }
        }
        if (isset($params['mobile']) && $params['mobile']) {
            $filter['mobile'] = $params['mobile'];
        }
        if (isset($params['account']) && $params['account']) {
            $filter['account'] = $params['account'];
        }
        if (isset($params['email']) && $params['email']) {
            $filter['email'] = $params['email'];
        }
        if (isset($params['member_mobile']) && $params['member_mobile']) {
            $filter['member_mobile'] = $params['member_mobile'];
        }
        if (isset($params['enterprise_id']) && $params['enterprise_id']) {
            $filter['enterprise_id'] = $params['enterprise_id'];
        }
        
        $employeesService = new EmployeesService();
        $count = $employeesService->count($filter);

        if ($count <= 0) {
            throw new resourceexception('导出有误,暂无数据导出');
        }

        if ($count > 15000) {
            throw new resourceexception('导出有误，最高导出15000条数据');
        }

        //存储导出操作账号者
        $operator_id = $authInfo['operator_id'];
        // 是否有权限查看加密数据
        $filter['datapass_block'] = $request->get('x-datapass-block', 0);
        $gotoJob = (new ExportFileJob('employee_purchase_employees', $filter['company_id'], $filter, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
