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

use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Services\AuthService;
use CompanysBundle\Services\OperatorSmsService;
use CompanysBundle\Services\OperatorsService;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use WechatBundle\Services\DistributorWechatService;

class Wxapp extends Controller
{
    /**
     * @SWG\POST(
     *     path="/operator/wechat/oauth/login",
     *     summary="微信公众号回调登录",
     *     tags={"店务"},
     *     description="微信公众号回调登录",
     *     operationId="login",
     *     @SWG\Parameter( name="company_id", in="query", description="company_id", required=true, type="string"),
     *     @SWG\Parameter( name="code", in="query", description="认证code", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="status", type="string", description="登录状态 success|unbound"),
     *                     @SWG\Property(property="token", type="string", description="登录成功获取token"),
     *                     @SWG\Property(property="company_id", type="string", description="未绑定情况下"),
     *                     @SWG\Property(property="app_id", type="string", description="未绑定情况下"),
     *                     @SWG\Property(property="app_type", type="string", description="未绑定情况下"),
     *                     @SWG\Property(property="openid", type="string", description="未绑定情况下"),
     *                     @SWG\Property(property="unionid", type="string", description="未绑定情况下"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function login(Request $request)
    {
        if (empty($request->input('code')) || empty($request->input('company_id'))) {
            throw new BadRequestHttpException('缺少参数，登录失败');
        }

        $params = [
            'code' => $request->input('code'),
            'company_id' => $request->input('company_id'),
            'logintype' => 'oauthwechat',
        ];

        $result = [
            'status' => '',
            'token' => '',
            'company_id' => '',
            'app_id' => '',
            'app_type' => 'wx',
            'openid' => '',
            'unionid' => '',
        ];

        try {
            $token = app('auth')->guard('api')->attempt($params);
        } catch (ResourceException $e) {
            $errors = $e->getErrors();
            if ($errors && $bind_info = $errors->get('bind_info')) {
                $result['status'] = 'unbound';
                $result['company_id'] = $bind_info['company_id'];
                $result['app_id'] = $bind_info['app_id'];
                $result['app_type'] = $bind_info['app_type'];
                $result['openid'] = $bind_info['openid'];
                $result['unionid'] = $bind_info['unionid'];
                return $this->response->array($result);
            }
            throw $e;
        }
        $result['status'] = 'success';
        $result['token'] = $token;
        return $this->response->array($result);
    }

    /**
     * 小程序授权登录--未绑定则通过手机号或账号密码登录绑定
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * @SWG\POST(
     *     path="/operator/wechat/lite/login",
     *     summary="小程序auth登录",
     *     tags={"店务"},
     *     description="小程序auth登录",
     *     operationId="wxLiteLogin",
     *     @SWG\Parameter( name="company_id", in="query", description="company_id", required=true, type="string"),
     *     @SWG\Parameter( name="app_id", in="query", description="app_id", required=true, type="string"),
     *     @SWG\Parameter( name="app_type", in="query", description="app_type(wxa)", required=true, type="string"),
     *     @SWG\Parameter( name="openid", in="query", description="openid", required=true, type="string"),
     *     @SWG\Parameter( name="unionid", in="query", description="unionid", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(property="data",type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="token", type="string", description="登录token"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function wxLiteLogin(Request $request)
    {
        $params = $request->all('company_id', 'app_id', 'app_type', 'openid', 'unionid');
        $rules = [
            'company_id' => ['required', 'company_id必填'],
            'app_id' => ['required', 'app_id必填'],
            'app_type' => ['required', 'app_type必填'],
            'openid' => ['required', 'openid必填'],
            'unionid' => ['required', 'unionid必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['logintype'] = 'wechatbinddistributorbylite';
        $token = app('auth')->guard('api')->attempt($params);

        return response()->json(['data'=>['token'=>$token]]);
    }

    /**
     * @SWG\POST(
     *     path="/operator/wechat/bind_mobile",
     *     summary="手机号验证码绑定",
     *     tags={"店务"},
     *     description="手机号验证码绑定",
     *     operationId="bindMobile",
     *     @SWG\Parameter( name="company_id", in="query", description="企业id", required=true, type="string"),
     *     @SWG\Parameter( name="app_id", in="query", description="app_id", required=true, type="string"),
     *     @SWG\Parameter( name="app_type", in="query", description="app_type", required=true, type="string"),
     *     @SWG\Parameter( name="openid", in="query", description="openid", required=true, type="string"),
     *     @SWG\Parameter( name="unionid", in="query", description="unionid", required=true, type="string"),
     *     @SWG\Parameter( name="check_token", in="query", description="校验码", required=true, type="string"),
     *     @SWG\Parameter( name="mobile", in="query", description="手机号", required=true, type="string"),
     *     @SWG\Parameter( name="vcode", in="query", description="短信验证码", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="token", type="string", example=">_<"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function bindMobile(Request $request)
    {
        $params = $request->all('company_id', 'app_id', 'app_type', 'openid', 'unionid', 'mobile', 'vcode', 'check_token');
        $rules = [
            'company_id' => ['required', 'company_id必填'],
            'mobile' => ['required', '请输入合法手机号码'],
            'vcode' => ['required', '请输入短信验证码'],
            'check_token' => ['required', 'check_token必填'],
            'app_id' => ['required', 'app_id必填'],
            'app_type' => ['required', 'app_type必填'],
            'openid' => ['required', 'openid必填'],
            'unionid' => ['required', 'unionid必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (!ismobile($params['mobile'])) {
            throw new ResourceException('请输入合法手机号码');
        }

        $params['logintype'] = 'wechatbinddistributor';
        $token = app('auth')->guard('api')->attempt($params);

        return $this->response->array(['token' => $token]);
    }

    /**
     * @SWG\POST(
     *     path="/operator/wechat/bind_account",
     *     summary="账号密码绑定登录",
     *     tags={"店务"},
     *     description="账号密码绑定登录",
     *     operationId="bindAccountLogin",
     *     @SWG\Parameter( name="company_id", in="query", description="企业id", required=true, type="string"),
     *     @SWG\Parameter( name="app_id", in="query", description="app_id", required=true, type="string"),
     *     @SWG\Parameter( name="app_type", in="query", description="app_type", required=true, type="string"),
     *     @SWG\Parameter( name="openid", in="query", description="openid", required=true, type="string"),
     *     @SWG\Parameter( name="unionid", in="query", description="unionid", required=true, type="string"),
     *     @SWG\Parameter( name="username", in="query", description="账号", required=true, type="string"),
     *     @SWG\Parameter( name="password", in="query", description="密码", required=true, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="token", type="string", example=">_<"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function bindAccountLogin(Request $request)
    {
        $params = $request->all('company_id', 'app_id', 'app_type', 'openid', 'unionid', 'username', 'password');
        $rules = [
            'company_id' => ['required', 'company_id必填'],
            'username' => ['required', '请输入账号'],
            'password' => ['required', '请输入密码'],
            'app_id' => ['required', 'app_id必填'],
            'app_type' => ['required', 'app_type必填'],
            'openid' => ['required', 'openid必填'],
            'unionid' => ['required', 'unionid必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['logintype'] = 'wechatbinddistributorbyusername';
        $token = app('auth')->guard('api')->attempt($params);

        return response()->json(['data'=>['token'=>$token]]);
    }

    /**
     * @SWG\Get(
     *   path="/operator/wechat/authorizeurl",
     *   tags={"店务"},
     *   summary="获取微信oauth链接",
     *   description="获取微信oauth链接",
     *   operationId="getWechatOuthorizeurl",
     *   produces={"application/json"},
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
    public function getWechatOuthorizeurl(Request $request)
    {
        if (!$request->query('company_id')) {
            throw new BadRequestHttpException('缺少参数');
        }

        $company_id = $request->query('company_id');

        $authService = new AuthService();
        $url = $authService->getWechatAuthorizeUrl($company_id);

        return $this->response->array(['url' => $url]);
    }

    /**
     * @SWG\Post(
     *     path="/operator/wechat/sms/code",
     *     summary="获取手机短信验证码",
     *     tags={"店务"},
     *     description="获取手机短信验证码",
     *     operationId="getSmsCode",
     *     @SWG\Parameter( in="query", type="string", required=true, name="type", description="验证码类型 login 登录验证码" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="error", type="object",
     *                  @SWG\Property( property="message", type="string", example="验证码错误", description="提示信息"),
     *                  @SWG\Property( property="status_code", type="string", example="422", description="错误码"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones")))
     * )
     */
    public function getSmsCode(Request $request)
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
        $operatorsInfo = $operatorsService->getOperatorByMobile($phone, 'distributor');
        if (!$operatorsInfo) {
            throw new ResourceException("该手机号尚未关联云店账号");
        }

        // 校验手机号是否已经绑定
        $filter = [
            'company_id' => $operatorsInfo['company_id'],
            'operator_id' => $operatorsInfo['operator_id'],
        ];
        $wechatService = new DistributorWechatService();
        $relInfo = $wechatService->getInfo($filter);
        if ($relInfo) {
            throw new ResourceException('该手机号已在店务端绑定');
        }

        (new OperatorSmsService())->sendVerifyCode($operatorsInfo['company_id'], $phone, $type);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/operator/wechat/distributor/js/config",
     *     summary="获取微信店务端js配置",
     *     tags={"店务"},
     *     description="获取微信店务端js配置",
     *     operationId="getDistributorJsConfig",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="formData",
     *         description="company_id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="url",
     *         in="formData",
     *         description="当前页面url",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="beta", type="boolean", description=""),
     *                  @SWG\Property( property="debug", type="boolean", description="开启调试模式"),
     *                  @SWG\Property( property="appId", type="string", description=""),
     *                  @SWG\Property( property="timestamp", type="string", description=""),
     *                  @SWG\Property( property="nonceStr", type="string", description=""),
     *                  @SWG\Property( property="signature", type="string", description=""),
     *                  @SWG\Property( property="url", type="string", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getDistributorJsConfig(Request $request)
    {
        $url = $request->input('url');
        if (!$url) {
            throw new ResourceException('当前页面url必填');
        }
        $companyId = $request->input('company_id');
        if (!$companyId) {
            throw new ResourceException('当前页面companyId必填');
        }
        $workWechatService = new DistributorWechatService();
        $result = $workWechatService->getJsConfig($companyId, $url);
        return $this->response->array($result);
    }
}
