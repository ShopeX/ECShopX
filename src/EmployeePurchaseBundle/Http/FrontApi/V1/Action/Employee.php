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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;

use EmployeePurchaseBundle\Services\EmployeesService;
use EmployeePurchaseBundle\Services\MemberActivityAggregateService;
use EmployeePurchaseBundle\Services\ActivitiesService;
use EmployeePurchaseBundle\Services\RelativesService;
use MembersBundle\Services\MemberService;

class Employee extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/employee/email/vcode",
     *     summary="获取邮箱验证码",
     *     tags={"内购"},
     *     description="获取邮箱验证码",
     *     operationId="sendEmailVcode",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="enterprise_id", required=true, type="integer"),
     *     @SWG\Parameter( name="email", in="query", description="邮箱地址", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function sendEmailVcode(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('email', 'distributor_id', 'enterprise_id');
        $rules = [
            // 'enterprise_id' => ['required', '企业ID不能为空'],
            'email' => ['required|email', '收件邮箱格式不正确'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['company_id'] = $authInfo['company_id'];
        $employeesService = new EmployeesService();
        $result = $employeesService->sendEmailVcode($params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/employee/auth",
     *     summary="员工身份验证bak",
     *     tags={"内购"},
     *     description="员工身份验证bak",
     *     operationId="authentication_bak",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="enterprise_id", required=true, type="integer"),
     *     @SWG\Parameter( name="email", in="formData", description="邮箱地址", required=false, type="string"),
     *     @SWG\Parameter( name="vcode", in="formData", description="邮箱验证码", required=false, type="string"),
     *     @SWG\Parameter( name="account", in="formData", description="账号", required=false, type="string"),
     *     @SWG\Parameter( name="auth_code", in="formData", description="账号密码", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="formData", description="员工手机号", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function authentication_bak(Request $request)
    {
    	$authInfo = $request->get('auth');

        $params = $request->all('enterprise_id', 'email', 'vcode', 'account', 'auth_code', 'mobile', 'auth_type');
        $rules = [
            'enterprise_id' => ['required', '企业ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        if (isset($params['mobile']) && $params['mobile'] == 'member_mobile') {
            $params['mobile'] = $authInfo['mobile'];
        }
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['member_mobile'] = $authInfo['mobile'];

        $employeesService = new EmployeesService();
        $result = $employeesService->authentication($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/employee/auth",
     *     summary="员工身份验证",
     *     tags={"内购"},
     *     description="员工身份验证",
     *     operationId="authentication",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="enterprise_id", in="formData", description="enterprise_id", required=true, type="integer"),
     *     @SWG\Parameter( name="employee_id", in="formData", description="员工ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function authentication(Request $request)
    {
        // ID: 53686f704578
        $authInfo = $request->get('auth');

        $params = $request->all('enterprise_id', 'employee_id', 'auth_type', 'email', 'mobile');
        $rules = [
            'enterprise_id' => ['required', '企业ID必填'],
            'employee_id' => ['required_if:auth_type,mobile,account', '员工ID必填'],
            'auth_type' => ['required', '认证方式必填'],
            'email' => ['required_if:auth_type,email', '邮箱必填'],
            'mobile' => ['required_if:auth_type,qr_code', '手机号必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        if (isset($params['mobile']) && $params['mobile'] == 'member_mobile') {
            $params['mobile'] = $authInfo['mobile'];
        }
        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['member_mobile'] = $authInfo['mobile'];

        $employeesService = new EmployeesService();
        $result = $employeesService->authentication($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employee/activitydata",
     *     summary="获取员工活动数据",
     *     tags={"内购"},
     *     description="获取员工活动数据",
     *     operationId="getActivityData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="activity_id", type="integer", example="1"),
     *               @SWG\Property( property="enterprise_id", type="integer", example="1"),
     *               @SWG\Property( property="if_relative_join", type="integer", example="1"),
     *               @SWG\Property( property="invite_limit", type="integer", example="10"),
     *               @SWG\Property( property="invited_num", type="integer", example="1"),
     *               @SWG\Property( property="is_employee", type="integer", example="1"),
     *               @SWG\Property( property="is_relative", type="integer", example="0"),
     *               @SWG\Property( property="limit_fee", type="integer", example="100"),
     *               @SWG\Property( property="aggregate_fee", type="integer", example="10"),
     *               @SWG\Property( property="left_fee", type="integer", example="90"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getActivityData(Request $request)
    {
        $authInfo = $request->get('auth');

    	$params = $request->all('activity_id', 'enterprise_id');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'enterprise_id' => ['required', '企业ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];

        $activityService = new ActivitiesService();
        $activity = $activityService->getInfo(['company_id' => $companyId, 'id' => $params['activity_id']]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if (!in_array($params['enterprise_id'], $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $memberActivityAggregateService = new MemberActivityAggregateService();
        $result = $memberActivityAggregateService->getAggregateFee($companyId, $params['enterprise_id'], $params['activity_id'], $userId);

        $result['activity_id'] = $activity['id'];
        $result['name'] = $activity['name'];
        $result['title'] = $activity['title'];
        $result['share_pic'] = $activity['share_pic'];
        $result['if_relative_join'] = $activity['if_relative_join'];
        $result['invite_limit'] = $activity['invite_limit'];
        $result['relative_begin_time'] = $activity['relative_begin_time'];
        $result['relative_end_time'] = $activity['relative_end_time'];

        $employeesService = new EmployeesService();
        $employee = $employeesService->check($companyId, $params['enterprise_id'], $userId);
        $result['is_employee'] = 0;
        $result['is_relative'] = 0;
        if ($employee) {
            $result['is_employee'] = 1;
            $result['enterprise_id'] = $employee['enterprise_id'];
//            $result['employee_created'] = $employee['created'];
            // 已邀请用户列表
            $result['invited_num'] = $employeesService->getInviteNum($companyId, $params['enterprise_id'], $params['activity_id'], $userId);
        } else {
            $relativesService = new RelativesService();
            $relative = $relativesService->check($companyId, $params['enterprise_id'], $params['activity_id'], $userId);
            if ($relative) {
                $result['is_relative'] = 1;
                $result['enterprise_id'] = $relative['enterprise_id'];
//                $result['relative_created'] = $relative['created'];
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employee/invitelist",
     *     summary="获取员工邀请亲友列表",
     *     tags={"内购"},
     *     description="获取员工邀请亲友列表",
     *     operationId="getInviteList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="integer", example="1"),
     *                          @SWG\Property( property="company_id", type="integer", example="34"),
     *                          @SWG\Property( property="enterprise_id", type="integer", example="1"),
     *                          @SWG\Property( property="user_id", type="integer", example="1"),
     *                          @SWG\Property( property="member_mobile", type="string", example="13333333333"),
     *                          @SWG\Property( property="activity_id", type="integer", example="1"),
     *                          @SWG\Property( property="employee_id", type="integer", example="1"),
     *                          @SWG\Property( property="employee_user_id", type="integer", example="1"),
     *                          @SWG\Property( property="created", type="integer", example="1672897143"),
     *                          @SWG\Property( property="disabled", type="integer", example="0"),
     *                          @SWG\Property( property="username", type="string", example="test1"),
     *                          @SWG\Property( property="avatar", type="string", example="https://ecshopx1.yuanyuanke.cn/image/34/2022/12/30/a5833cc402552407d7afaf1acd686281tL4dcPwPp47hOIz0lH9UEDk7CQX4PoPg"),
     *                          @SWG\Property( property="used_limitfee", type="integer", example=1000),
     *                      ),
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getInviteList(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('activity_id', 'enterprise_id');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'enterprise_id' => ['required', '企业ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);

        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];

        $activityService = new ActivitiesService();
        $activity = $activityService->getInfo(['company_id' => $companyId, 'id' => $params['activity_id']]);
        if (!$activity) {
            throw new ResourceException('活动不存在');
        }

        if (!in_array($params['enterprise_id'], $activity['enterprise_id'])) {
            throw new ResourceException('企业不参与该活动');
        }

        $relativesService = new RelativesService();
        $memberService = new MemberService();
        $memberActivityAggregateService = new MemberActivityAggregateService();
        // 包含已失效的用户
        $result = $relativesService->lists(['company_id' => $companyId, 'enterprise_id' => $params['enterprise_id'], 'employee_user_id' => $userId, 'activity_id' => $params['activity_id']], '*', $page, $pageSize, ['created' => 'DESC']);
        if ($result['list']) {
            $memberList = $memberService->getMemberInfoList(['company_id' => $companyId, 'user_id' => array_column($result['list'], 'user_id')], 1, -1);
            $memberList = array_column($memberList['list'], null, 'user_id');
            foreach ($result['list'] as $key => $row) {
                if (isset($memberList[$row['user_id']])) {
                    $result['list'][$key]['username'] = $memberList[$row['user_id']]['username'] ?? '';
                    $result['list'][$key]['avatar'] = $memberList[$row['user_id']]['avatar'] ?? '';
                }
                try {
                    $rowAggregate = $memberActivityAggregateService->getAggregateFee($row['company_id'], $row['enterprise_id'], $row['activity_id'], $row['user_id']);
                    $result['list'][$key]['limit_fee'] = $rowAggregate['limit_fee'] ?? 0;
                    $result['list'][$key]['used_limitfee'] = $rowAggregate['aggregate_fee'] ?? 0;
                    $result['list'][$key]['left_fee'] = $rowAggregate['left_fee'] ?? 0;
                }catch (\Exception $e) {
                    $result['list'][$key]['limit_fee'] = 0;
                    $result['list'][$key]['used_limitfee'] = 0;
                    $result['list'][$key]['left_fee'] = 0;
                }
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/employee/invitecode",
     *     summary="获取员工邀请码",
     *     tags={"内购"},
     *     description="获取员工邀请码",
     *     operationId="getInviteCode",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="activity_id", in="query", description="活动ID", required=true, type="integer"),
     *     @SWG\Parameter( name="enterprise_id", in="query", description="企业ID", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="invite_code", type="string", example="3221937", description=""),
     *            ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function getInviteCode(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('activity_id', 'enterprise_id');
        $rules = [
            'activity_id' => ['required', '活动ID必填'],
            'enterprise_id' => ['required', '企业ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = $authInfo['company_id'];
        $userId = $authInfo['user_id'];

        $employeesService = new EmployeesService();
        $code = $employeesService->getInviteCode($companyId, $params['enterprise_id'], $params['activity_id'], $userId);

        return $this->response->array(['invite_code' => $code]);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/employee/relative/bind",
     *     summary="绑定成为亲友",
     *     tags={"内购"},
     *     description="绑定成为亲友",
     *     operationId="bindRelative",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="invite_code", in="formData", description="邀请码", type="string", required=true),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="status", type="boolean", example="true", description="状态"),
     *            ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function bindRelative(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('invite_code');
        $rules = [
            'invite_code' => ['required', '邀请码必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $params['company_id'] = $authInfo['company_id'];
        $params['user_id'] = $authInfo['user_id'];
        $params['member_mobile'] = $authInfo['mobile'];

        $employeesService = new EmployeesService();
        $relativesService = new RelativesService();
        $employeesService->lockInviteCode($params['company_id'], $params['invite_code']);

        try {
            $result = $relativesService->bindRelative($params);
            $employeesService->delInviteCode($params['company_id'], $params['invite_code']);
            return $this->response->array(['status' => $result]);
        } catch (\Exception $e) {
            $employeesService->unlockInviteCode($params['company_id'], $params['invite_code']);
            throw new ResourceException($e->getMessage());
        }
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/employee/check",
     *     summary="员工验证",
     *     tags={"内购"},
     *     description="员工验证",
     *     operationId="employeeCheck",
     *     @SWG\Parameter( name="auth_type", in="formData", description="验证方式", required=false, type="string"),
     *     @SWG\Parameter( name="distributor_id", in="formData", description="店铺ID", required=false, type="string"),
     *     @SWG\Parameter( name="email", in="formData", description="邮箱地址", required=false, type="string"),
     *     @SWG\Parameter( name="vcode", in="formData", description="邮箱验证码", required=false, type="string"),
     *     @SWG\Parameter( name="account", in="formData", description="账号", required=false, type="string"),
     *     @SWG\Parameter( name="auth_code", in="formData", description="账号密码", required=false, type="string"),
     *     @SWG\Parameter( name="mobile", in="formData", description="员工手机号", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *               @SWG\Property( property="status", type="string", example="true"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EmployeePurchaseErrorRespones") ) )
     * )
     */
    public function employeeCheck(Request $request)
    {
        $authInfo = $request->get('auth');

        $params = $request->all('email', 'vcode', 'account', 'auth_code', 'mobile', 'auth_type', 'distributor_id', 'enterprise_id', 'activity_id');
        $rules = [
            'auth_type' => ['required', '验证方式必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['company_id'] = $authInfo['company_id'];
        if (isset($params['mobile']) && $params['mobile'] == 'member_mobile') {
            $params['mobile'] = $authInfo['mobile'];
        }
        $employeesService = new EmployeesService();
        $result = $employeesService->doEmployeeCheck($params);

        return $this->response->array($result);
    }
}
