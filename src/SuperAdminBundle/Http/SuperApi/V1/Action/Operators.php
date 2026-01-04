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

namespace SuperAdminBundle\Http\SuperApi\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\OperatorsService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;

class Operators extends BaseController
{
    /** @var OperatorsService */
    private $operatorsService;

    /**
     * Operators constructor.
     * @param OperatorsService  $operatorsService
     */
    public function __construct(OperatorsService $operatorsService)
    {
        $this->operatorsService = new $operatorsService();
    }

    /**
     * @SWG\Post(path="/superadmin/operator/open",
     *   tags={"管理员"},
     *   summary="开通商城和管理员账户",
     *   description="开通账户",
     *   operationId="open",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter( name="login_name", in="query", description="登录账号", required=true, type="string"),
     *   @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *   @SWG\Parameter( name="password", in="query", description="密码", required=true, type="string"),
     *   @SWG\Parameter( name="company_name", in="query", description="公司名称", required=true, type="string"),
     *   @SWG\Parameter( name="expiredAt", in="query", description="过期时间", required=true, type="string"),
     *   @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="operator_id", type="string", example="200", description="操作员id"),
     *                  @SWG\Property( property="company_id", type="string", example="110", description="公司id"),
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="用户手机号"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function open(Request $request)
    {
        $params = $request->input();
        $rules = [
            'login_name'   => ['required', '登录账号必填'],
            'mobile'       => ['required', '手机号必填'],
            'password'     => ['required', '密码必填'],
            'company_name' => ['required', '公司名称必填'],
            'menu_type'    => ['required', '产品类型必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        if (!preg_match("/^[a-zA-Z][a-zA-Z0-9_-]{3,15}$/", $params['login_name'])) {
            return $this->response->error('登录账号必须是字母开头的4-16位字符', 401);
        }
        if (!preg_match("/^1[345678]{1}\d{9}$/", $params['mobile'])) {
            return $this->response->error('请填写正确的手机号', 401);
        }
        $params['is_disabled'] = (isset($params['is_disabled']) && $params['is_disabled'] == 'true') ? 1 : 0 ;
        $params['third_params'] = (isset($params['third_params']) && is_array($params['third_params'])) ? $params['third_params'] : [] ;
        $operatorData = [
            // 'eid' => '',
            // 'passport_uid' => '',
            'login_name' => trim($params['login_name']),
            'mobile' => trim($params['mobile']),
            'password' => trim($params['password']),
            'company_name' => trim($params['company_name']),
            'expiredAt' => $params['expiredAt'],
            'is_disabled' => $params['is_disabled'],
            'third_params' => $params['third_params'],
            'source' => 'pms',
            'menu_type' => $params['menu_type']
        ];
        $result = $this->operatorsService->open($operatorData);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(path="/superadmin/operator",
     *   tags={"管理员"},
     *   summary="修改管理员信息",
     *   description="修改管理员信息",
     *   operationId="updateOperator",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Parameter(
     *     in="query",
     *     name="company_id",
     *     description="公司id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="operator_id",
     *     description="管理员id",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="login_name",
     *     description="login_name",
     *     required=false,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="password",
     *     description="密码",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="operator_id", type="string", example="200", description="账号id"),
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="手机号"),
     *                  @SWG\Property( property="login_name", type="string", example="test2", description="登录账号名"),
     *                  @SWG\Property( property="password", type="string", example="$2y$10$eObr/ckw8tRMv0CK3ZluF.1VZzVDWqRccm3Vgv/75U.F/Q.qlLH.K", description="密码"),
     *                  @SWG\Property( property="eid", type="string", example="null", description="企业id"),
     *                  @SWG\Property( property="passport_uid", type="string", example="null", description="激活码"),
     *                  @SWG\Property( property="operator_type", type="string", example="admin", description="操作员类型类型。admin:超级管理员;staff:员工;distributor:店铺管理员"),
     *                  @SWG\Property( property="shop_ids", type="string", example="null", description="店铺id集合"),
     *                  @SWG\Property( property="distributor_ids", type="string", example="null", description="员工管理的店铺id集合"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="username", type="string", example="null", description="姓名"),
     *                  @SWG\Property( property="head_portrait", type="string", example="null", description="头像"),
     *                  @SWG\Property( property="regionauth_id", type="string", example="0", description="区域id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/SuperAdminErrorResponse") ) )
     * )
     */
    public function updateOperator(Request $request)
    {
        $params = $request->all('company_id', 'operator_id', 'password', 'login_name');
        $rules = [
            'company_id' => ['required', '公司id必填'],
            'operator_id' => ['required', '管理员id必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $operator_id = $params['operator_id'];
        unset($params['operator_id']);
        $result = $this->operatorsService->updateOperator($operator_id, $params);

        return $this->response->array($result);
    }
}
