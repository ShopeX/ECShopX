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

namespace EmployeePurchaseBundle\Http\FrontApi\V1\Action;

use Dingo\Api\Exception\ResourceException;
use EmployeePurchaseBundle\Services\EnterprisesService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Swagger\Annotations as SWG;
use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\RelativesService;
use EmployeePurchaseBundle\Services\ActivitiesService;

class Enterprise extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/enterprises",
     *     summary="获取企业列表",
     *     tags={"内购"},
     *     description="获取企业列表",
     *     operationId="getEnterprisesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Parameter( name="enterprise_sn", in="query", description="企业编号", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="id", type="string", example="1", description="企业IDid"),
     *                          @SWG\Property( property="name", type="string", example="test", description="企业名称"),
     *                          @SWG\Property( property="enterprise_sn", type="string", example="xxx", description="企业编码"),
     *                          @SWG\Property( property="auth_type", type="string", example="111", description="登录类型"),
     *                          @SWG\Property( property="disabled", type="string", example="0", description="禁用 0 否 1 是"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="created", type="string", example="1612160658", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612160658", description="修改时间"),
     *                          @SWG\Property( property="relay_host", type="string", description="服务器主机地址"),
     *                          @SWG\Property( property="smtp_port", type="string", description="端口号"),
     *                          @SWG\Property( property="email_user", type="string", description="邮箱用户名"),
     *                          @SWG\Property( property="email_password", type="string", description="邮箱密码"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getEnterprisesList(Request $request)
    {
        $authInfo = $request->get('auth');

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $filter['company_id'] = $authInfo['company_id'];
        $filter['disabled']= 0;

        if ($request->get('enterprise_sn')) {
            $filter['enterprise_sn'] = $request->get('enterprise_sn');
        }

        $orderBy = ['sort' => 'ASC', 'created' => 'DESC'];
        $enterprisesService = new EnterprisesService();
        $result = $enterprisesService->getEnterprisesList($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/enterprises",
     *     summary="获取用户所在企业列表",
     *     tags={"内购"},
     *     description="获取用户所在企业列表",
     *     operationId="getUserEnterprisesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="disabled", in="query", description="是否有效身份", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *              @SWG\Property( property="name", type="string", example="test", description="企业名称"),
     *              @SWG\Property( property="enterprise_sn", type="string", example="xxx", description="企业编码"),
     *              @SWG\Property( property="login_account", type="string", example="111", description="登录账号"),
     *              @SWG\Property( property="is_employee", type="integer", example="1", description="是否员工"),
     *              @SWG\Property( property="is_relative", type="integer", example="0", description="是否家属"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getUserEnterprisesList(Request $request)
    {
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['user_id'] = $authInfo['user_id'];

        if ($request->get('disabled')) {
            $filter['disabled'] = $request->get('disabled');
        }
        $distributor_id = intval($request->get('distributor_id', 0));
        if ( $distributor_id > 0) {
            $filter['distributor_id'] = $distributor_id;
        }

        $activity_id = intval($request->get('activity_id', 0));
        if ($activity_id > 0) {
            $activitiesService = new ActivitiesService();
            $enterprisesList = $activitiesService->getActivityEnterprises([
                'company_id' => $filter['company_id'],
                'activity_id' => $activity_id,
            ]);
            if (empty($enterprisesList)) {
                return $this->response->array([]);
            }
            $filter['enterprise_id'] = array_column($enterprisesList, 'enterprise_id');
        }

        $employeesService = new EmployeesService();
        $employees = $employeesService->getLists($filter);

        $relativesService = new RelativesService();
        $relatives = $relativesService->getLists($filter);

        $enterpriseIds = array_merge(array_column($employees, 'enterprise_id'), array_column($relatives, 'enterprise_id'));
        if (!$enterpriseIds) {
            return $this->response->array([]);
        }

        $enterprisesService = new EnterprisesService();
        $enterprises = $enterprisesService->getLists(['company_id' => $authInfo['company_id'], 'id' => $enterpriseIds]);
        $enterprises = array_column($enterprises, null, 'id');
        $result = [];
        foreach ($employees as $row) {
            if (!isset($enterprises[$row['enterprise_id']])) {
                continue;
            }
            $authType = $enterprises[$row['enterprise_id']]['auth_type'];
            if ($authType == 'qr_code') {
                $authType = 'mobile';
            }
            $result[] = [
                'company_id' => $row['company_id'],
                'name' => $enterprises[$row['enterprise_id']]['name'],
                'enterprise_id' => $row['enterprise_id'],
                'enterprise_sn' => $enterprises[$row['enterprise_id']]['enterprise_sn'],
                'logo' => $enterprises[$row['enterprise_id']]['logo'],
                'login_account' => $row[$authType],
                'disabled' => $row['disabled'],
                'is_employee' => 1,
                'is_relative' => 0,
            ];
        }

        foreach ($relatives as $row) {
            if (!isset($enterprises[$row['enterprise_id']])) {
                continue;
            }
            $result[] = [
                'company_id' => $row['company_id'],
                'name' => $enterprises[$row['enterprise_id']]['name'],
                'enterprise_id' => $row['enterprise_id'],
                'enterprise_sn' => $enterprises[$row['enterprise_id']]['enterprise_sn'],
                'logo' => $enterprises[$row['enterprise_id']]['logo'],
                'login_account' => $row['member_mobile'],
                'disabled' => $row['disabled'],
                'is_employee' => 0,
                'is_relative' => 1,
            ];
        }
        //去重
        $unique = [];

        foreach ($result as $item) {
            $unique[$item['enterprise_id']] = $item;
        }

// 如果你希望最后的结果是索引数组：
        $result = array_values($unique);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/user/enterprise/distributor",
     *     summary="获取用户所在企业的店铺数据",
     *     tags={"内购"},
     *     description="获取用户所在企业的店铺数据",
     *     operationId="getUserEnterpriseDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="员工企业ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="distributor_id", type="string", example="test", description="店铺ID"),
     *              @SWG\Property( property="distributor_name", type="string", example="xxx", description="店铺名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getUserEnterpriseDistributor(Request $request)
    {
        $params = $request->all('enterprise_id');
        $rules = [
            'enterprise_id' => ['required', '企业ID不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $authInfo = $request->get('auth');

        $filter['company_id'] = $authInfo['company_id'];
        $filter['id'] = $params['enterprise_id'];
        $enterprisesService = new EnterprisesService();
        $result = $enterprisesService->getEnterpriseDistributorInfo($filter);

        return $this->response->array($result);
    }
}
