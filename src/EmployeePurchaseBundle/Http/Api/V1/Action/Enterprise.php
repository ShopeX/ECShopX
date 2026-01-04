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

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\EnterprisesService;
use EmployeePurchaseBundle\Services\EmployeesService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Swagger\Annotations as SWG;
use WechatBundle\Services\WeappService;
use WechatBundle\Services\OpenPlatform;

class Enterprise extends Controller
{
    /**
     * @SWG\Post(
     *     path="/enterprise",
     *     summary="新增企业",
     *     tags={"内购"},
     *     description="新增企业",
     *     operationId="create",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="企业名称", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_sn", in="formData", description="企业编码", required=true, type="string"),
     *     @SWG\Parameter( name="logo", in="formData", description="企业logo", required=false, type="string"),
     *     @SWG\Parameter( name="auth_type", in="formData", description="登录类型", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=true, type="string"),
     *     @SWG\Parameter( name="relay_host", in="formData", description="服务器主机地址", required=false, type="string"),
     *     @SWG\Parameter( name="smtp_port", in="formData", description="端口号", required=false, type="string"),
     *     @SWG\Parameter( name="email_user", in="formData", description="邮箱用户名", required=false, type="string"),
     *     @SWG\Parameter( name="email_password", in="formData", description="邮箱密码", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="id", type="string", example="1", description="企业IDid"),
     *                  @SWG\Property( property="name", type="string", example="test", description="企业名称"),
     *                  @SWG\Property( property="enterprise_sn", type="string", example="xxx", description="企业编码"),
     *                  @SWG\Property( property="logo", type="string", example="https://logo", description="企业logo"),
     *                  @SWG\Property( property="auth_type", type="string", example="111", description="登录类型"),
     *                  @SWG\Property( property="disabled", type="string", example="0", description="禁用 0 否 1 是"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="1612160658", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612160658", description="修改时间"),
     *                  @SWG\Property( property="relay_host", type="string", description="服务器主机地址"),
     *                  @SWG\Property( property="smtp_port", type="string", description="端口号"),
     *                  @SWG\Property( property="email_user", type="string", description="邮箱用户名"),
     *                  @SWG\Property( property="email_password", type="string", description="邮箱密码"),
     *                  @SWG\Property( property="email_suffix", type="string", description="收件邮箱后缀"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function create(Request $request)
    {
        $params = $request->all('name', 'enterprise_sn', 'logo', 'auth_type', 'sort', 'relay_host', 'smtp_port', 'email_user', 'email_password', 'email_suffix', 'qr_code_bg_image', 'is_employee_check_enabled');
        $authInfo = app('auth')->user()->get();
        $distributor_id = $authInfo['distributor_id'];
        $operator_id = $authInfo['operator_id'];
        $params['distributor_id'] = $distributor_id;
        $params['operator_id'] = $operator_id;
        $params['company_id'] = $authInfo['company_id'];
        $rules = [
            'name' => ['required', '名称不能为空'],
            'enterprise_sn' => ['required', '企业编码不能为空'],
            'auth_type' => ['required', '登录类型不能为空'],
            'sort' => ['required|integer|min:0|max:2147483647', '填写的排序编号超出范围'],
            'relay_host' => ['required_if:auth_type,email', 'SMTP服务器不能为空'],
            'smtp_port' => ['required_if:auth_type,email', '邮箱端口号不能为空'],
            'email_user' => ['required_if:auth_type,email', '请填写正确的发件邮箱'],
            'email_password' => ['required_if:auth_type,email', '邮箱密码不能为空'],
            'email_suffix' => ['required_if:auth_type,email', '收件邮箱后缀不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $enterprisesService = new EnterprisesService();
        // is_employee_check_enabled:是有验证白名单 true:需要 false:不需要-小程序使用此方式登录时，无需提前加白名单，登录成功后，自动添加白名单
        // auth_type=qr_code时，is_employee_check_enabled使用传入的参数
        if (in_array($params['auth_type'], ['mobile', 'account'])) {
            // 验证方式=手机号、账号时，is_employee_check_enabled='true'
            $params['is_employee_check_enabled'] = 'true';
        } else if (in_array($params['auth_type'], ['email'])) {
            // 验证方式=邮箱，is_employee_check_enabled='false'
            $params['is_employee_check_enabled'] = 'false';
        }
        $params['is_employee_check_enabled'] = $params['is_employee_check_enabled'] == 'true' ? true : false;
        $result = $enterprisesService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/enterprise/{enterpriseId}",
     *     summary="更新企业",
     *     tags={"内购"},
     *     description="更新企业",
     *     operationId="update",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="formData", description="企业名称", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_sn", in="formData", description="企业编码", required=true, type="string"),
     *     @SWG\Parameter( name="logo", in="formData", description="企业logo", required=false, type="string"),
     *     @SWG\Parameter( name="auth_type", in="formData", description="登录类型", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=true, type="string"),
     *     @SWG\Parameter( name="relay_host", in="formData", description="服务器主机地址", required=false, type="string"),
     *     @SWG\Parameter( name="smtp_port", in="formData", description="端口号", required=false, type="string"),
     *     @SWG\Parameter( name="email_user", in="formData", description="邮箱用户名", required=false, type="string"),
     *     @SWG\Parameter( name="email_password", in="formData", description="邮箱密码", required=false, type="string"),
     *     @SWG\Parameter( name="email_suffix", in="formData", description="收件邮箱后缀", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="id", type="string", example="1", description="企业IDid"),
     *                  @SWG\Property( property="name", type="string", example="test", description="企业名称"),
     *                  @SWG\Property( property="enterprise_sn", type="string", example="xxx", description="企业编码"),
     *                  @SWG\Property( property="logo", type="string", example="https://logo", description="企业logo"),
     *                  @SWG\Property( property="auth_type", type="string", example="111", description="登录类型"),
     *                  @SWG\Property( property="disabled", type="string", example="0", description="禁用 0 否 1 是"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="1612160658", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612160658", description="修改时间"),
     *                  @SWG\Property( property="relay_host", type="string", description="服务器主机地址"),
     *                  @SWG\Property( property="smtp_port", type="string", description="端口号"),
     *                  @SWG\Property( property="email_user", type="string", description="邮箱用户名"),
     *                  @SWG\Property( property="email_password", type="string", description="邮箱密码"),
     *                  @SWG\Property( property="email_suffix", type="string", description="收件邮箱后缀"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function update($enterpriseId, Request $request)
    {
        $params = $request->all('name', 'enterprise_sn', 'logo', 'auth_type', 'sort', 'relay_host', 'smtp_port', 'email_user', 'email_password', 'email_suffix', 'qr_code_bg_image', 'is_employee_check_enabled');
        $authInfo = app('auth')->user()->get();
        $distributor_id = $authInfo['distributor_id'];
        $operator_id = $authInfo['operator_id'];
        $params['distributor_id'] = $distributor_id;
        $companyId = $authInfo['company_id'];
        $rules = [
            'name' => ['required', '名称不能为空'],
            'enterprise_sn' => ['required', '企业编码不能为空'],
            'auth_type' => ['required', '登录类型不能为空'],
            'sort' => ['required|integer|min:0|max:2147483647', '填写的排序编号超出范围'],
            'relay_host' => ['required_if:auth_type,email', 'SMTP服务器不能为空'],
            'smtp_port' => ['required_if:auth_type,email', '邮箱端口号不能为空'],
            'email_user' => ['required_if:auth_type,email', '请填写正确的发件邮箱'],
            'email_password' => ['required_if:auth_type,email', '邮箱密码不能为空'],
            'email_suffix' => ['required_if:auth_type,email', '收件邮箱后缀不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        // is_employee_check_enabled:是有验证白名单 true:需要 false:不需要-小程序使用此方式登录时，无需提前加白名单，登录成功后，自动添加白名单
        // auth_type=qr_code时，is_employee_check_enabled使用传入的参数
        if (in_array($params['auth_type'], ['mobile', 'account'])) {
            // 验证方式=手机号、账号时，is_employee_check_enabled='true'
            $params['is_employee_check_enabled'] = 'true';
        } else if (in_array($params['auth_type'], ['email'])) {
            // 验证方式=邮箱，is_employee_check_enabled='false'
            $params['is_employee_check_enabled'] = 'false';
        }
        $params['is_employee_check_enabled'] = $params['is_employee_check_enabled'] == 'true' ? true : false;
        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $enterpriseId;
        $filter['company_id'] = $companyId;
        $enterprisesService = new EnterprisesService();
        $result = $enterprisesService->updateEnterprise($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/enterprise",
     *     summary="获取内购企业列表",
     *     tags={"内购"},
     *     description="获取内购企业列表",
     *     operationId="getEnterprisesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer" ),
     *     @SWG\Parameter( name="name", in="query", description="供应商名称", required=false, type="string" ),
     *     @SWG\Parameter( name="disabled", in="query", description="禁用 0 否 1 是", required=false, type="string" ),
     *     @SWG\Parameter( name="distributorId", in="query", description="店铺ID,平台=0", required=false, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="distributor_id", type="string", example="1", description="店铺ID"),
     *                          @SWG\Property( property="id", type="string", example="1", description="企业ID"),
     *                          @SWG\Property( property="name", type="string", example="test", description="企业名称"),
     *                          @SWG\Property( property="enterprise_sn", type="string", example="xxx", description="企业编码"),
     *                  @SWG\Property( property="logo", type="string", example="https://logo", description="企业logo"),
     *                          @SWG\Property( property="auth_type", type="string", example="111", description="登录类型"),
     *                          @SWG\Property( property="disabled", type="string", example="0", description="禁用 0 否 1 是"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="created", type="string", example="1612160658", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612160658", description="修改时间"),
     *                          @SWG\Property( property="relay_host", type="string", description="服务器主机地址"),
     *                          @SWG\Property( property="smtp_port", type="string", description="端口号"),
     *                          @SWG\Property( property="email_user", type="string", description="邮箱用户名"),
     *                          @SWG\Property( property="email_password", type="string", description="邮箱密码"),
     *                          @SWG\Property( property="email_suffix", type="string", description="收件邮箱后缀"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getEnterprisesList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'name', 'disabled', 'enterprise_sn', 'enterprise_id', 'auth_type', 'distributor_id', 'is_employee_check_enabled');
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
        if ($authInfo['operator_type'] == 'distributor') {
            $filter['distributor_id'] = $authInfo['distributor_id'];
        } else {
            if (isset($params['distributor_id']) && $params['distributor_id'] != '') {
                $filter['distributor_id'] = $params['distributor_id'];
            }
        }

        if (isset($params['name']) && $params['name']) {
            $filter['name|contains'] = $params['name'];
        }

        if (isset($params['disabled'])) {
            $filter['disabled'] = (int)$params['disabled'];
        }

        if (isset($params['enterprise_sn']) && $params['enterprise_sn']) {
            $filter['enterprise_sn|contains'] = $params['enterprise_sn'];
        }

        if (isset($params['enterprise_id'])) {
            $filter['id'] = $params['enterprise_id'];
        }

        if (isset($params['auth_type']) && $params['auth_type']) {
            $filter['auth_type'] = $params['auth_type'];
        }

        if (isset($params['is_employee_check_enabled'])) {
            $filter['is_employee_check_enabled'] = $params['is_employee_check_enabled'] == 'true' ? true : false;
        }

        $orderBy = ['sort' => 'ASC', 'created' => 'DESC'];
        $enterprisesService = new EnterprisesService();
        $result = $enterprisesService->getEnterprisesList($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/enterprise/{enterpriseId}",
     *     summary="获取企业详情",
     *     tags={"内购"},
     *     description="获取企业详情",
     *     operationId="getEnterpriseInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="enterpriseId", in="path", description="enterprise_id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="id", type="string", example="1", description="企业IDid"),
     *                  @SWG\Property( property="name", type="string", example="test", description="企业名称"),
     *                  @SWG\Property( property="enterprise_sn", type="string", example="xxx", description="企业编码"),
     *                  @SWG\Property( property="logo", type="string", example="https://logo", description="企业logo"),
     *                  @SWG\Property( property="auth_type", type="string", example="111", description="登录类型"),
     *                  @SWG\Property( property="disabled", type="string", example="0", description="禁用 0 否 1 是"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="1612160658", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612160658", description="修改时间"),
     *                  @SWG\Property( property="relay_host", type="string", description="服务器主机地址"),
     *                  @SWG\Property( property="smtp_port", type="string", description="端口号"),
     *                  @SWG\Property( property="email_user", type="string", description="邮箱用户名"),
     *                  @SWG\Property( property="email_password", type="string", description="邮箱密码"),
     *                  @SWG\Property( property="email_suffix", type="string", description="收件邮箱后缀"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getEnterpriseInfo($enterpriseId, Request $request)
    {
        $filter['id'] = $enterpriseId;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $enterprisesService = new EnterprisesService();
        $result = $enterprisesService->getEnterpriseInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/enterprise/{enterpriseId}",
     *     summary="删除企业",
     *     tags={"内购"},
     *     description="删除企业",
     *     operationId="delete",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="enterpriseId", in="path", description="enterprise_id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function delete($enterpriseId)
    {
        $companyId = app('auth')->user()->get('company_id');

        $employeesService = new EmployeesService();
        $exist = $employeesService->count(['enterprise_id' => $enterpriseId]);
        if ($exist) {
            throw new ResourceException('请先删除该企业的员工');
        }

        $filter = [
            'id' => $enterpriseId,
            'company_id' => $companyId,
        ];
        $enterprisesService = new EnterprisesService();
        $enterprisesService->delete($filter);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/enterprise/status",
     *     summary="更新企业状态",
     *     tags={"内购"},
     *     description="更新企业状态",
     *     operationId="updateStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="ID", required=true, type="string"),
     *     @SWG\Parameter( name="disabled", in="formData", description="是否禁用", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function updateStatus(Request $request)
    {
        $params = $request->all('enterprise_id', 'disabled');
        $rules = [
            'enterprise_id' => ['required', '企业ID不能为空'],
            'disabled' => ['required', '状态不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $params['enterprise_id'];
        $filter['company_id'] = $companyId;

        $enterprisesService = new EnterprisesService();
        $enterprisesService->updateStatus($filter, $params['disabled']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/enterprise/sort",
     *     summary="更新企业排序",
     *     tags={"内购"},
     *     description="更新企业排序",
     *     operationId="setSort",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="ID", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function setSort(Request $request)
    {
        $params = $request->all('enterprise_id', 'sort');
        $rules = [
            'enterprise_id' => ['required', '企业ID不能为空'],
            'sort' => ['required|integer|min:0|max:2147483647', '填写的排序编号超出范围'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $companyId = app('auth')->user()->get('company_id');
        $filter['id'] = $params['enterprise_id'];
        $filter['company_id'] = $companyId;

        $enterprisesService = new EnterprisesService();
        $enterprisesService->setSort($filter, $params['sort']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/enterprise/sendtestemail",
     *     summary="发送测试邮件",
     *     tags={"内购"},
     *     description="发送测试邮件",
     *     operationId="sendTestemail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="enterprise_id", required=true, type="integer" ),
     *     @SWG\Parameter( name="email", in="formData", description="邮箱地址", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function sendTestemail(Request $request)
    {
        $params = $request->all('enterprise_id', 'email');
        $rules = [
            'enterprise_id' => ['required', '企业ID不能为空'],
            'email' => ['required|email', '收件邮箱格式不正确'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $enterprisesService = new EnterprisesService();
        $result = $enterprisesService->sendTestEmail($params);
        return $this->response->array(['status' => $result]);
    }

    public function getEnterpriseQrcode($enterpriseId, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $enterprisesService = new EnterprisesService();
        $enterpriseInfo = $enterprisesService->getInfoById($enterpriseId);
        if (!$enterpriseInfo) {
            throw new ResourceException('未查询到员工企业信息');
        }
        $templateName = 'yykweishop';
        $weappService = new WeappService();
        $wxaAppid = $weappService->getWxappidByTemplateName($companyId, $templateName);
        if (!$wxaAppid) {
            throw new ResourceException('没有绑定小程序');
        }

        $openPlatform = new OpenPlatform();
        $app = $openPlatform->getAuthorizerApplication($wxaAppid);

        $scene = 'eid='.$enterpriseId.'&cid='.$companyId.'&t='.substr($enterpriseInfo['auth_type'], 0, 1).'&c='.$enterpriseInfo['is_employee_check_enabled'];
        $data['page'] = 'pages/purchase/auth';

        try {
            $response = $app->app_code->getUnlimit($scene, $data);
            if (is_array($response) && $response['errcode'] > 0) {
                throw new ResourceException($response['errmsg']);
            }
        } catch (\Exception $e) {
            throw new ResourceException($e->getMessage());
        }
        $base64 = 'data:image/jpg;base64,' . base64_encode($response);
        return $this->response->array(['base64Image' => $base64]);
    }
}
