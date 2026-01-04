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

use App\Http\Controllers\Controller as BaseController;
use CompanysBundle\Services\OperatorDataPassService;
use CompanysBundle\Services\OperatorLogs\MysqlService;
use CompanysBundle\Services\OperatorLogsService;
use CompanysBundle\Services\OperatorsService;
use CompanysBundle\Services\AuthService;
use CompanysBundle\Services\OperatorSmsService;
use CompanysBundle\Ego\PrismEgo;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use SupplierBundle\Services\SupplierService;
use WorkWechatBundle\Services\DistributorWorkWechatService;

class Operators extends BaseController
{
    /** @var OperatorsService */
    private $operatorsService;

    /**
     * Operators constructor.
     * @param OperatorsService $operatorsService
     */
    public function __construct(OperatorsService $operatorsService)
    {
        $this->operatorsService = new $operatorsService();
    }

    /**
     * @SWG\Get(path="/token/refresh",
     *   tags={"企业"},
     *   summary="刷新Token",
     *   description="刷新Token",
     *   produces={"application/json"},
     *   @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *   @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *             @SWG\Header(header="authorization", type="string", description="返回刷新后的token"),
     *         @SWG\Schema(
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="result", type="boolean", example="true"),
     *                 )
     *             ),
     *         ),
     *   ),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    /**
     * @SWG\Post(path="/operator/login",
     *   tags={"企业"},
     *   summary="用户登陆",
     *   description="用户登陆获取Token",
     *   operationId="login",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     in="query",
     *     name="username",
     *     description="登陆用户名",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="password",
     *     required=true,
     *     description="登陆密码",
     *     type="string"
     *   ),
     *   @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="token", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    // public function login(Request $request) 路由中直接调用JWT登录

    /**
     * @SWG\Get(
     *   path="/operator/credential",
     *   tags={"企业"},
     *   summary="登陆获取证书",
     *   description="登陆获取证书",
     *   operationId="getCredentials",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     in="query",
     *     name="username",
     *     description="登陆用户名",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Parameter(
     *     in="query",
     *     name="password",
     *     required=true,
     *     description="登陆密码",
     *     type="string"
     *   ),
     *   @SWG\Response(
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
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getCredentials(Request $request)
    {
        $this->authService = new AuthService();
        $result = $this->authService->retrieveByCredentials($request->all());

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *   path="/operator/basic",
     *   tags={"企业"},
     *   summary="获取账号基本信息",
     *   description="获取账号基本信息",
     *   operationId="getBasicUserById",
     *   produces={"application/json"},
     *   @SWG\Parameter(
     *     in="query",
     *     name="id",
     *     description="账号ID",
     *     required=true,
     *     type="string"
     *   ),
     *   @SWG\Response(
     *       response="200",
     *       description="成功返回结构",
     *       @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="id", type="integer", example="1"),
     *                     @SWG\Property(property="operator_id", type="integer", example="1"),
     *                     @SWG\Property(property="distributor_id", type="integer", example="1"),
     *                     @SWG\Property(property="distributor_ids", type="string", example="[]"),
     *                     @SWG\Property(property="shop_ids", type="string", example="[]"),
     *                     @SWG\Property(property="mobile", type="string", example="188XXX99"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="authorizer_appid", type="string", example="1"),
     *                     @SWG\Property(property="operator_type", type="string", example="1"),
     *                     @SWG\Property(property="username", type="string", example="1"),
     *                     @SWG\Property(property="head_portrait", type="string", example="1"),
     *                     @SWG\Property(property="regionauth_id", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *   ),
     *   @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getBasicUserById(Request $request)
    {
        $this->authService = new AuthService();
        $result = $this->authService->getBasicUserById($request->all('id'));

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/operator/image/code",
     *     summary="获取图片验证码",
     *     tags={"企业"},
     *     description="获取图片验证码",
     *     operationId="getImageVcode",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="imageToken", type="string",example="a8c828c293a5e76eb377551a1feee62f"),
     *                 @SWG\Property(property="imageData", type="string", example=""),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getImageVcode(Request $request)
    {
        $type = $request->input('type', 'forget');
        $operatorsService = new OperatorsService();
        list($token, $imgData) = $operatorsService->generateImageVcode($type);
        return $this->response->array([
            'imageToken' => $token,
            "imageData" => $imgData,
        ]);
    }

    /**
     * @SWG\Post(
     *     path="/operator/sms/code",
     *     summary="获取手机短信验证码",
     *     tags={"企业"},
     *     description="获取手机短信验证码",
     *     operationId="getImageVcode",
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="token",
     *         in="query",
     *         description="图片验证码token",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="yzm",
     *         in="query",
     *         description="图片验证码的值",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="验证码类型 sign 注册验证码 forget_password 重置密码验证码",
     *         required=false,
     *         type="string"
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
    public function getSmsCode(Request $request)
    {
        $phone = $request->input('mobile');
        if (!$phone || !preg_match("/^1\d{10}$/", $phone)) {
            throw new ResourceException("手机号码错误");
        }

        $operatorsService = new OperatorsService();
        $type = 'forget';
        $token = $request->input('token');
        $yzmcode = $request->input('yzm');
        if (!$operatorsService->checkImageVcode($token, $yzmcode, $type)) {
            throw new ResourceException("验证码错误");
        }

        // 校验手机号是否注册
        $operatorsInfo = $operatorsService->getOperatorByMobile($phone, 'staff');
        if (!$operatorsInfo) {
            throw new ResourceException("没有找到该手机号码");
        }
        $operatorsService->generateSmsVcode($phone, $type);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/operator/app/image/code",
     *     summary="获取图片验证码",
     *     tags={"企业"},
     *     description="获取图片验证码",
     *     operationId="getImageVcode",
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="验证码类型 login 登录 sign 注册验证码 forget_password 重置密码验证码",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="imageToken", type="string",example="a8c828c293a5e76eb377551a1feee62f"),
     *                 @SWG\Property(property="imageData", type="string", example=""),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getAppImageVcode(Request $request)
    {
        $operatorsService = new OperatorsService();
        $type = $request->input('type', 'login');
        list($token, $imgData) = $operatorsService->generateImageVcode($type);
        return $this->response->array([
            'imageToken' => $token,
            "imageData" => $imgData,
        ]);
    }

    /**
     * @SWG\Post(
     *     path="/operator/app/sms/code",
     *     summary="获取手机短信验证码",
     *     tags={"operator"},
     *     description="获取手机短信验证码",
     *     operationId="getImageVcode",
     *     @SWG\Parameter(
     *         name="mobile",
     *         in="query",
     *         description="手机号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="type",
     *         in="query",
     *         description="验证码类型 login 登录 sign 注册验证码 forget_password 重置密码验证码",
     *         required=false,
     *         type="string"
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
    public function getAppSmsCode(Request $request)
    {
        $phone = $request->input('mobile');
        if (!$phone || !preg_match("/^1\d{10}$/", $phone)) {
            throw new ResourceException("手机号码错误");
        }

        $type = $request->input('type', 'login');

        /*        $operatorsService = new OperatorsService();
                $token = $request->input('token');
                $yzmcode = $request->input('yzm');
                if (!$operatorsService->checkImageVcode($token, $yzmcode, $type)) {
                    throw new ResourceException("图片验证码错误");
                }*/

        $operatorsService = new OperatorsService();
        // 校验手机号是否注册
        $operatorsInfo = $operatorsService->getOperatorByMobile($phone, 'staff');
        if (!$operatorsInfo) {
            throw new ResourceException("该手机号尚未关联云店账号");
        }

        // 校验手机号是否已经绑定
        $filter = [
            'company_id' => $operatorsInfo['company_id'],
            'operator_id' => $operatorsInfo['operator_id'],
        ];
        $workWechatService = new DistributorWorkWechatService();
        $relInfo = $workWechatService->getInfo($filter);
        if ($relInfo) {
            throw new ResourceException('该手机号已在店务端绑定');
        }

        (new OperatorSmsService())->sendVerifyCode($operatorsInfo['company_id'], $phone, $type);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/operator/resetpassword",
     *     summary="重置密码",
     *     tags={"企业"},
     *     description="重置密码",
     *     operationId="resetPassword",
     *     @SWG\Parameter(
     *         name="account",
     *         in="query",
     *         description="手机号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="code",
     *         in="query",
     *         description="手机验证码",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="newpassword",
     *         in="query",
     *         description="新密码",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="message", type="string", example="密码修改成功，请重新登录"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function resetPassword(Request $request)
    {
        $phone = $request->input('account');
        if (!$phone || !preg_match("/^1\d{10}$/", $phone)) {
            throw new ResourceException("手机号码错误");
        }
        $type = 'forget';
        $smscode = $request->input('code');
        $operatorsService = new OperatorsService();

        if (!$operatorsService->checkSmsVcode($phone, $smscode, $type)) {
            throw new ResourceException("验证码错误");
        }
        $newpassword = $request->input('newpassword');
        if (!$newpassword || !preg_match('/^(?!^[0-9]+$)(?!^[a-z]+$)(?!^[A-Z]+$)(?!^[^A-z0-9]+$)^[^\s\x{4e00}-\x{9fa5}]{8,}$/u', $newpassword)) {
            throw new ResourceException('密码至少8位以上，至少由数字、字母或特殊字符中两种及以上方式组成');
        }
        $operatorsService->updatePasswordByMobile($phone, $newpassword);
        return $this->response->array(['message' => "密码修改成功，请重新登录"]);
    }

    /**
     * @SWG\Put(
     *     path="/operator/updatedata",
     *     summary="更改用户名和头像",
     *     tags={"企业"},
     *     description="更改用户名和头像",
     *     operationId="updateUserData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="operator_id", in="query", description="唯一标示", required=false, type="string"),
     *     @SWG\Parameter( name="username", in="query", description="用户名", required=false, type="string"),
     *     @SWG\Parameter( name="head_portrait", in="query", description="头像地址", required=false, type="string"),
     *     @SWG\Parameter( name="pwd", in="query", description="密码", required=false, type="string"),
     *     @SWG\Parameter( name="repwd", in="query", description="重复密码", required=false, type="string"),
     *     @SWG\Parameter( name="logintype", in="query", description="登录类型", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                         property="status", type="object", description="返回数据",
     *                         @SWG\Property(property="operator_id", type="string", description="操作员id", example="1"),
     *                         @SWG\Property(property="mobile", type="string", description="手机号", example=""),
     *                         @SWG\Property(property="login_name", type="string", description="登录名", example=""),
     *                         @SWG\Property(property="eid", type="string", description="企业id", example="661309471969"),
     *                         @SWG\Property(property="passport_uid", type="string", description="激活码", example="8813091119380"),
     *                         @SWG\Property(property="operator_type", type="string", description="类型", example="admin"),
     *                         @SWG\Property(property="shop_ids", type="string", description="门店id集合", example=""),
     *                         @SWG\Property(property="distributor_ids", type="string", description="distributor_ids", example=""),
     *                         @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                         @SWG\Property(property="username", type="string", description="用户名", example=""),
     *                         @SWG\Property(property="head_portrait", type="string", description="头像", example=""),
     *                         @SWG\Property(property="regionauth_id", type="string", description="区域id", example="0"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateUserData(Request $request)
    {
        $filter = $request->all('username', 'head_portrait', 'pwd', 'repwd', 'logintype');

        if ($filter['pwd'] && $filter['logintype'] != 'admin') {
            if (!preg_match('/^(?!^[0-9]+$)(?!^[a-z]+$)(?!^[A-Z]+$)(?!^[^A-z0-9]+$)^[^\s\x{4e00}-\x{9fa5}]{8,}$/u', $filter['pwd'])) {
                throw new ResourceException('密码至少8位以上，至少由数字、字母或特殊字符中两种及以上方式组成');
            }

            if ($filter['pwd'] === $filter['repwd']) {
                $params['password'] = $filter['pwd'];
            } else {
                throw new ResourceException("密码不一致");
            }
        }
        $params['username'] = $filter['username'];
        $params['head_portrait'] = $filter['head_portrait'];
        $operatorId = app('auth')->user()->get('operator_id');
        $result = $this->operatorsService->updateOperator($operatorId, $params);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/operator/changestatus",
     *     summary="禁用启用账号",
     *     tags={"企业"},
     *     description="禁用启用账号",
     *     operationId="changeOperatorStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="operator_id", in="query", description="唯一标示", required=true, type="string"),
     *     @SWG\Parameter( name="disabled", in="query", description="disabled", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(
     *                         property="status", type="object", description="返回数据",
     *                         @SWG\Property(property="operator_id", type="string", description="操作员id", example="1"),
     *                         @SWG\Property(property="mobile", type="string", description="手机号", example=""),
     *                         @SWG\Property(property="login_name", type="string", description="登录名", example=""),
     *                         @SWG\Property(property="eid", type="string", description="企业id", example="661309471969"),
     *                         @SWG\Property(property="passport_uid", type="string", description="激活码", example="8813091119380"),
     *                         @SWG\Property(property="operator_type", type="string", description="类型", example="admin"),
     *                         @SWG\Property(property="shop_ids", type="string", description="门店id集合", example=""),
     *                         @SWG\Property(property="distributor_ids", type="string", description="distributor_ids", example=""),
     *                         @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                         @SWG\Property(property="username", type="string", description="用户名", example=""),
     *                         @SWG\Property(property="head_portrait", type="string", description="头像", example=""),
     *                         @SWG\Property(property="regionauth_id", type="string", description="区域id", example="0"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function changeOperatorStatus(Request $request)
    {
        $params = $request->all('is_disable', 'operator_id');
        // 验证数据
        $rules = [
            'is_disable' => ['required|in:0,1', '状态不能为空'],
            'operator_id' => ['required|integer|min:1', '账号id不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $operatorId = app('auth')->user()->get('operator_id');
        if ($operatorId == $params['operator_id']) {
            throw new ResourceException('不能操作本人禁用状态');
        }
        $companyId = app('auth')->user()->get('company_id');
        $authFilter = [
            'company_id' => $companyId,
            'operator_id' => $params['operator_id'],
        ];
        $result = $this->operatorsService->changeOperatorStatus($authFilter, $params['is_disable']);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/operator/getinfo",
     *     summary="获取管理员详细信息",
     *     tags={"企业"},
     *     description="获取管理员详细信息",
     *     operationId="getUserData",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="operator_id", in="query", description="唯一标示", required=false, type="string"),
     *     @SWG\Parameter( name="is_app", in="query", description="是否店务app请求", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="operator_id", type="string", description="操作员id", example="1"),
     *                 @SWG\Property(property="mobile", type="string", description="手机号", example=""),
     *                 @SWG\Property(property="login_name", type="string", description="登录名", example=""),
     *                 @SWG\Property(property="eid", type="string", description="企业id", example="661309471969"),
     *                 @SWG\Property(property="passport_uid", type="string", description="激活码", example="8813091119380"),
     *                 @SWG\Property(property="operator_type", type="string", description="类型", example="admin"),
     *                 @SWG\Property(property="shop_ids", type="string", description="门店id集合", example=""),
     *                 @SWG\Property(property="distributor_ids", type="string", description="distributor_ids", example=""),
     *                 @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                 @SWG\Property(property="username", type="string", description="用户名", example=""),
     *                 @SWG\Property(property="head_portrait", type="string", description="头像", example=""),
     *                 @SWG\Property(property="regionauth_id", type="string", description="区域id", example="0"),
     *                 @SWG\Property(property="logintype", type="string", description="登录类型", example="admin"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getUserData(Request $request)
    {
        $logintype = app('auth')->parseToken()->getClaim('logintype');
        $filter['company_id'] = app('auth')->user()->get('company_id');
        if (app('auth')->user()->get('source') == 'salesperson_workwechat') {
            $salespersonService = new \ThirdPartyBundle\Services\MarketingCenter\SalespersonService();
            $userInfo = $salespersonService->getSalespersonInfoByWorkUserid($filter['company_id'], app('auth')->user()->get('work_userid'), $request->input('is_app'));
            isset($userInfo['mobile']) ? $userInfo['mobile'] = data_masking('mobile', (string) $userInfo['mobile']) : "";
            $userInfo['logintype'] = 'salesperson_workwechat';
            return $this->response->array($userInfo);
        }

        $filter['operator_id'] = app('auth')->user()->get('operator_id');

        $result = $this->operatorsService->getInfo($filter, $request->input('is_app'));

        if ($result['password'] ?? 0) {
            unset($result['password']);
        }
        $result['logintype'] = $logintype ?? "admin";
        
        $supplierService = new SupplierService();
        $supplierService->getCheckStatus($result);
        
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/operator/select/distributor",
     *     summary="店铺端选择店铺",
     *     tags={"企业"},
     *     description="店铺端选择店铺",
     *     operationId="shopLoginSelectShopId",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="set_distributor_id", in="query", description="店铺id", required=false, type="string"),
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
    public function shopLoginSelectShopId(Request $request)
    {
        $authInfo = app('auth')->user()->get();

        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-data:". json_encode($authInfo));

        $distributorId = $request->input('set_distributor_id', 0);
        $sid = 'select_distributor'.$authInfo['operator_id'].'-'.$authInfo['company_id'];

        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-data:". json_encode($sid));
        app('log')->debug("\n".__FUNCTION__."-".__LINE__.":in-data:". json_encode($distributorId));

        app('redis')->connection('companys')->set($sid, $distributorId);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/company/operatorlogs",
     *     summary="获取商家日志列表",
     *     tags={"企业"},
     *     description="获取商家日志列表",
     *     operationId="getCompanysLogs",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer", example="1"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="log_id", type="string", description="日志id", example="120901"),
     *                         @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                         @SWG\Property(property="operator_id", type="string", description="操作者id", example="1"),
     *                         @SWG\Property(property="ip", type="string", description="ip地址", example="172.18.0.1"),
     *                         @SWG\Property(property="operator_name", type="string", description="操作者", example="云店留资创建"),
     *                         @SWG\Property(property="created", type="string", description="创建时间", example="1611558114"),
     *                         @SWG\Property(property="log_type", type="string", description="日志类型", example="operator"),
     *                         @SWG\Property(property="username", type="string", description="姓名", example="超级管理员"),
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getCompanysLogs(Request $request)
    {
        $inputData = $request->input();
        $company_id = app('auth')->user()->get('company_id');
        $filter = ['company_id' => $company_id];
        $merchantId = app('auth')->user()->get('merchant_id');
        $operatorType = app('auth')->user()->get('operator_type');
        if ($operatorType == 'merchant') {
            $filter['merchant_id'] = $merchantId;
        }
        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? 20;
        $operatorLogsService = new OperatorLogsService(new MysqlService());
        $result = $operatorLogsService->getLogsList($filter, $page, $pageSize, ['created' => 'DESC']);
        foreach ($result['list'] as &$v) {
            unset($v['params'],$v['request_uri']);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/ydleads/create",
     *     summary="云店留资创建",
     *     tags={"企业"},
     *     description="云店留资创建",
     *     operationId="createYdleads",
     *     @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *     @SWG\parameter( name="goods_name", in="query" ,description="订购套餐", type="string", required=true),
     *     @SWG\parameter( name="call_name", in="query", description="称呼", type="string", required=true),
     *     @SWG\parameter( name="sex", in="query", description="性别", type="string", required=true),
     *     @SWG\parameter( name="mobile", in="query",description="手机号码", type="string", required=true),
     *     @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\items(
     *                     @SWG\Property(property="status", type="boolean"),
     *                 ),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */

    public function createYdleads(Request $request)
    {
        $params = $request->all('goods_name', 'call_name', 'sex', 'mobile');
        $rules = [
            'goods_name' => ['required', '订购套餐必填'],
            'call_name' => ['required', '称呼必填'],
            'sex' => ['required', '性别必填'],
            'mobile' => ['required', '手机号码必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $company_id = app('auth')->user()->get('company_id');
        $operatorsService = new OperatorsService();
        $operatorsService->createYdleadsData($company_id, $params);
        $result = ['status' => true];
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *   path="/operator/authorizeurl",
     *   tags={"企业"},
     *   summary="登陆获取oauth链接",
     *   description="登陆获取oauth链接",
     *   operationId="getOuthorizeurl",
     *   produces={"application/json"},
     *   @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *   @SWG\response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\schema(
     *             @SWG\property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="url", type="string", description="链接地址", example="https://openapi.shopex.cn/oauth/authorize?response_type=code&client_id=a4dyatls&redirect_uri=iframeLogin&view=ydsaas_iframe_login&reg=ydsaas_login&direct_reg_uri="),
     *             ),
     *          ),
     *     ),
     *     @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getOuthorizeurl(Request $request)
    {
        $this->authService = new AuthService();
        $url = $this->authService->getOuthorizeurl();

        return $this->response->array(['url' => $url]);
    }

    /**
     * @SWG\Get(
     *   path="/operator/oauth/logout",
     *   tags={"企业"},
     *   summary="获取oauth登出链接",
     *   description="获取oauth登出链接",
     *   operationId="getOauthLogouturl",
     *   produces={"application/json"},
     *   @SWG\response(
     *      response=200,
     *      description="成功返回结构",
     *      @SWG\schema(
     *          @SWG\property(
     *              property="data",
     *              type="object",
     *              @SWG\Property(property="url", type="string", description="登出链接", example="https://openapi.shopex.cn/oauth/logout?redirect_uri=login"),
     *          ),
     *       ),
     *   ),
     *  @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getOauthLogouturl(Request $request)
    {
        $this->authService = new AuthService();
        $callback = config('common.shop_admin_url') . 'login';
        $url = $this->authService->getOauthLogoutUrl($callback);

        return $this->response->array(['url' => $url]);
    }

    /**
     * @SWG\Get(
     *   path="/operator/protocol",
     *   tags={"operator"},
     *   summary="获取许可协议信息",
     *   description="获取用户许可协议",
     *   operationId="getLicense",
     *   produces={"application/json"},
     *   @SWG\response(
     *      response=200,
     *      description="成功返回结构",
     *      @SWG\schema(
     *          @SWG\property(
     *              property="data",
     *              type="object",
     *              @SWG\Property(property="title", type="string", description="协议标题", example="欢迎使用商派云店"),
     *              @SWG\Property(property="content", type="string", description="协议内容", example="本《最终用户使用许可协议》（以下称《协议》）是您（个人或单一实体）与..."),
     *          ),
     *       ),
     *   ),
     *  @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getAppOperatorLicense()
    {
        $opService = new OperatorsService();
        $licenseInfo = $opService->getLicenseInfo();
        $data = [
            'title' => $licenseInfo->getTitle() ?: '',
            'content' => $licenseInfo->getContent() ?: '',
        ];
        return $this->response->array($data);
    }

    /**
     * @SWG\POST (
     *   path="/datapass",
     *   tags={"脱敏"},
     *   summary="申请查看数据敏感信息权限",
     *   description="申请查看数据敏感信息权限",
     *   operationId="applyDataPass",
     *   produces={"application/json"},
     *   @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *   @SWG\parameter( name="start_time", in="query" ,description="开始时间", type="integer", required=true),
     *   @SWG\parameter( name="end_time", in="query", description="结束时间", type="integer", required=true),
     *   @SWG\parameter( name="date_type", in="query", description="日期类型 0:每天 1:周一到周五", type="integer", required=true),
     *   @SWG\parameter( name="range", in="query", description="时间范围 '8:00-18:00'、为空表示全天", type="string", required=true),
     *   @SWG\parameter( name="reason", in="query", description="申请原因", type="string", required=true),
     *   @SWG\response(
     *       response=200,
     *       description="成功返回结构",
     *       @SWG\schema(
     *           @SWG\property(
     *               property="data",
     *               type="array",
     *               @SWG\items(
     *                   @SWG\Property(property="status", type="boolean"),
     *                   @SWG\Property(property="message", type="string"),
     *               ),
     *           ),
     *        ),
     *   ),
     *  @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function applyDataPass(Request $request)
    {
        $params = $request->all('start_time', 'end_time', 'date_type', 'range', 'reason');
        $rules = [
            'start_time' => ['required|date_format:Y-m-d', '请输入日期'],
            'end_time' => ['required|date_format:Y-m-d', '请输入日期'],
            'date_type' => ['required|integer|min:0|max:1', '日期类型必填'],
            'range' => ['sometimes|regex:/^\d{1,2}:\d{2}\-\d{1,2}:\d{2}$/', '时间范围错误'] // 8:00-18:00
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $op = app('auth')->user()->get('operator_type');
        if ($op == 'admin') {
            throw new ResourceException('账号错误');
        }

        $params['reason'] = (string)$params['reason'];

        $params['start_time'] = strtotime($params['start_time']);
        $params['end_time'] = strtotime($params['end_time']);

        if ($params['range']) {
            $rs = explode('-', $params['range']);
            foreach ($rs as $k => $r) {
                list($h, $i) = explode(':', $r);
                $h = intval($h);
                $is = intval($i);
                if ($h < 0 || $h > 24 || $is < 0 || $is >= 60) {
                    throw new ResourceException('时间范围格式错误');
                }
                $rs[$k] = sprintf("%02d", $h).':'.$i;
            }
            $params['range'] = implode('-', $rs);
        }

        $companyId = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');

        $re = ['status' => true, 'message' => ''];

        $passService = new OperatorDataPassService();
        $errMsg = $passService->apply($companyId, $operatorId, $params);
        if ($errMsg) {
            $re = [
                'status' => false,
                'message' => $errMsg,
            ];
        }
        return $this->response->array($re);
    }

    /**
     * @SWG\Get(
     *     path="/datapass",
     *     summary="获取申请查看敏感权限列表",
     *     tags={"脱敏"},
     *     description="获取申请查看敏感权限列表",
     *     operationId="getDataPassList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=true, type="integer"),
     *     @SWG\Parameter( name="login_name", in="query", description="操作员名称", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="审批状态 0:未审批 1:同意 2:驳回 3:关闭", required=true, type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="申请日期开始时间", required=true, type="integer"),
     *     @SWG\Parameter( name="end_time", in="query", description="申请日期结束时间", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                     type="object",
     *                     @SWG\Property(property="count", type="integer", example="1"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="pass_id", type="string", description="申请id", example="120901"),
     *                         @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                         @SWG\Property(property="operator_id", type="string", description="操作者id", example="1"),
     *                         @SWG\Property(property="status", type="string", description="审批状态 0:未审批 1:同意 2:驳回", example="1"),
     *                         @SWG\Property(property="start_time", type="string", description="生效开始时间"),
     *                         @SWG\Property(property="end_time", type="string", description="生效结束时间"),
     *                         @SWG\Property(property="range", type="string", description="时间范围 '8:00-18:00'、为空表示全天"),
     *                         @SWG\Property(property="date_type", type="string", description="日期类型 0:每天 1:周一到周五"),
     *                         @SWG\Property(property="reason", type="string", description="申请理由"),
     *                         @SWG\Property(property="remarks", type="string", description="审批备注"),
     *                         @SWG\Property(property="create_time", type="string", description="申请时间"),
     *                         @SWG\Property(property="login_name", type="string", description="操作员名称"),
     *                         @SWG\Property(property="head_portrait", type="string", description="操作员头像"),
     *                         @SWG\Property(property="approve_time", type="string", description="审批时间"),
     *                         @SWG\Property(property="operator_type", type="string", description="staff:平台管理员;distributor:店铺管理员"),
     *                         @SWG\Property(property="is_closed", type="string", description="是否关闭"),
     *                     )),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function listDataPass(Request $request)
    {
        $inputData = $request->input();
        $companyId = app('auth')->user()->get('company_id');
        $operatorId = app('auth')->user()->get('operator_id');
        $merchantId = app('auth')->user()->get('merchant_id');
        $op = app('auth')->user()->get('operator_type');
        $page = $inputData['page'] ?? 1;
        $pageSize = $inputData['pageSize'] ?? 10;
        // 前端用新组件分页
        $params = $inputData['params'] ?? '';
        if ($params) {
            $params = json_decode($params, 1);
            $page = $params['page'] ?? 1;
            $pageSize = $params['page_size'] ?? 10;
        }
        $passService = new OperatorDataPassService();
        $filter = [
            'p.company_id' => $companyId,
            'p.merchant_id' => $merchantId ?? 0
        ];
        if ($op != 'admin' && $op != 'merchant') {
            $filter['p.operator_id'] = $operatorId;
        }
        if (isset($inputData['login_name']) && $inputData['login_name']) {
            $filter['o.login_name'] = strval($inputData['login_name']);
        }
        if (isset($inputData['status']) && $inputData['status'] !== '') {
            $filter['p.status'] = strval($inputData['status']);
        }
        if (isset($inputData['start_time'], $inputData['end_time']) && $inputData['start_time'] && $inputData['end_time']) {
            $filter['p.create_time|gte'] = intval($inputData['start_time']);
            $filter['p.create_time|lte'] = intval($inputData['end_time']);
        }
        $result = $passService->getList($filter, $page, $pageSize);
        return $this->response->array([
            'total_count' => $passService->count($filter),
            'list' => $result
        ]);
    }

    /**
     * @SWG\PUT (
     *   path="/datapass/{id}",
     *   tags={"脱敏"},
     *   summary="申请查看数据敏感信息权限",
     *   description="申请查看数据敏感信息权限",
     *   operationId="applyDataPass",
     *   produces={"application/json"},
     *   @SWG\parameter( name="authorization", in="header", description="jwt验证token", type="string", required=true),
     *   @SWG\Parameter( name="status", in="query", description="审批状态 0:未审批 1:同意 2:驳回 3:关闭", required=true, type="integer"),
     *   @SWG\Parameter( name="remarks", in="query", description="审批备注", required=true, type="string"),
     *   @SWG\Parameter( name="is_closed", in="query", description="是否关闭", required=true, type="integer"),
     *   @SWG\response(
     *       response=200,
     *       description="成功返回结构",
     *       @SWG\schema(
     *           @SWG\property(
     *               property="data",
     *               type="array",
     *               @SWG\items(
     *                   @SWG\Property(property="status", type="boolean"),
     *               ),
     *           ),
     *        ),
     *   ),
     *  @SWG\response( response="default", description="错误返回结构", @SWG\schema( type="array", @SWG\items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function approveDataPass($id, Request $request)
    {
        $params = $request->all('status', 'remarks', 'is_closed');
        $passService = new OperatorDataPassService();
        $passService->approve($id, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/datapass/{id}",
     *     summary="获取申请查看敏感权限详情",
     *     tags={"脱敏"},
     *     description="获取申请查看敏感权限详情",
     *     operationId="getDataPassDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer", example="1"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="pass_id", type="string", description="申请id", example="120901"),
     *                         @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                         @SWG\Property(property="operator_id", type="string", description="操作者id", example="1"),
     *                         @SWG\Property(property="status", type="string", description="审批状态 0:未审批 1:同意 2:驳回 3:关闭", example="1"),
     *                         @SWG\Property(property="start_time", type="string", description="生效开始时间"),
     *                         @SWG\Property(property="end_time", type="string", description="生效结束时间"),
     *                         @SWG\Property(property="range", type="string", description="时间范围 '8:00-18:00'、为空表示全天"),
     *                         @SWG\Property(property="date_type", type="string", description="日期类型 0:每天 1:周一到周五"),
     *                         @SWG\Property(property="reason", type="string", description="申请理由"),
     *                         @SWG\Property(property="remarks", type="string", description="审批备注"),
     *                         @SWG\Property(property="create_time", type="string", description="申请时间"),
     *                         @SWG\Property(property="operator_info", type="object", description="申请者信息",
     *                             @SWG\Property(property="login_name", type="string", description="名称"),
     *                             @SWG\Property(property="head_portrait", type="string", description="头像"),
     *                             @SWG\Property(property="mobile", type="string", description="手机号"),
     *                             @SWG\Property(property="distributor_ids", type="array", description="店铺列表",
     *                                 @SWG\Items(
     *                                     type="object",
     *                                     @SWG\Property(property="name", type="string", description="店铺名"),
     *                                 ),
     *                             ),
     *                             @SWG\Property(property="role_data", type="array", description="角色列表",
     *                                 @SWG\Items(
     *                                     type="object",
     *                                     @SWG\Property(property="role_name", type="string", description="角色名"),
     *                                 ),
     *                             ),
     *                         ),
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function fetchDataPassDetail($id, Request $request)
    {
        $passService = new OperatorDataPassService();
        $result = $passService->detail($id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datapasslog",
     *     summary="获取申请查看敏感信息日志",
     *     tags={"脱敏"},
     *     description="获取申请查看敏感信息日志",
     *     operationId="getDataPassLogList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="operator_id", in="query", description="操作员id", required=true, type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="分页页码", required=true, type="integer"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页数量", required=true, type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="total_count", type="integer", example="1"),
     *                     @SWG\Property(property="list", type="array", @SWG\Items(
     *                         @SWG\Property(property="pass_id", type="string", description="申请id", example="120901"),
     *                         @SWG\Property(property="company_id", type="string", description="公司id", example="1"),
     *                         @SWG\Property(property="operator_id", type="string", description="操作者id", example="1"),
     *                         @SWG\Property(property="create_time", type="string", description="申请时间"),
     *                         @SWG\Property(property="content", type="string", description="操作内容"),
     *                     )),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function listDataPassLog(Request $request)
    {
        $params = $request->all('operator_id');
        $rules = [
            'operator_id' => ['required|integer', '操作员id必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }
        $passService = new OperatorDataPassService();
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 1000);
        $companyId = app('auth')->user()->get('company_id');

        $result = $passService->logs($companyId, $params['operator_id'], $page, $pageSize);
        return $this->response->array($result);
    }
}
