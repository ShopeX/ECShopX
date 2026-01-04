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

namespace EspierBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;
use EspierBundle\Services\LoginService;
use Illuminate\Http\Request;
use WechatBundle\Http\FrontApi\V1\Action\Wxapp;

class LoginController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/new_login",
     *     tags={"系统"},
     *     summary="登录",
     *     description="登录",
     *     operationId="login",
     *     @SWG\Parameter(name="encryptedData", in="query", description="需要被解密的加密串", required=true, type="string", default=""),
     *     @SWG\Parameter(name="iv", in="query", description="加解密的初始向量", required=true, type="string", default=""),
     *     @SWG\Parameter(name="code", in="query", description="前端从小程序那边获取的随机code码", required=true, type="string", default=""),
     *     @SWG\Parameter(name="auth_type", in="query", description="请求认证的类型，【wxapp 微信小程序授权登录】【wx_offiaccount 微信公众号授权登录】【local 手机号登录】", required=true, type="string", default=""),
     *     @SWG\Parameter(name="appid", in="query", description="小程序的id", required=true, type="string", default=""),
     *     @SWG\Parameter(name="company_id", in="query", description="企业id", required=true, type="integer", default=""),
     *     @SWG\Parameter(name="salesperson_id", in="query", description="导购id, 如果存在导购id且大于0的情况则是需要让用户与导购员做绑定，否则不绑定", required=false, type="integer", default="0"),
     *     @SWG\Parameter(name="cloudID", in="query", description="云id", required=false, type="string", default=""),
     *     @SWG\Parameter(name="trustlogin_tag", in="query", description="信任登录标签 【weixin 微信】", required=false, type="string", default=""),
     *     @SWG\Parameter(name="version_tag", in="query", description="信任登录类型 【standard pc端】 【touch h5端】", required=false, type="string", default=""),
     *     @SWG\Parameter(name="username", in="query", description="用户手机号", required=false, type="string", default=""),
     *     @SWG\Parameter(name="vcode", in="query", description="手机短信验证码", required=false, type="string", default=""),
     *     @SWG\Parameter(name="check_type", in="query", description="手机登录的验证类型 【mobile 手机短信验证】【password 手机密码验证】", required=false, type="string", default=""),
     *     @SWG\Parameter(name="password", in="query", description="手机登录密码", required=false, type="string", default=""),
     *     @SWG\Parameter(name="auto_register", in="query", description="自动注册【0 不自动注册】【1 自动注册】", required=false, type="string", default=""),
     *     @SWG\Parameter(name="silent", in="query", description="接口不报错正常返回【0 遇到异常抛出异常】【1 遇到异常以error_message的信息返回】", required=false, type="string", default=""),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            required={"data"},
     *            @SWG\Property(property="data", type="object", description="", required={"token", "is_new"},
     *               @SWG\Property(property="token", type="string", default="true", description="登录的token"),
     *               @SWG\Property(property="is_new", type="integer", default="1", description="是否是新用户，1为新用户，0为老用户"),
     *               @SWG\Property(property="pre_login_data", type="object", default="1", description="登录时携带的用户信息"),
     *               @SWG\Property(property="error_message", type="string", default="1", description="错误信息"),
     *            ),
     *         ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/EspierErrorRespones")))
     * )
     */
    public function login(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];

        // 获取处理异常时的状态
        $silent = (int)$request->input('silent', 0);
        // 获取登录服务
        $loginService = new LoginService();
        // 获取请求认证的类型
        $authType = $request->input('auth_type');
        switch ($authType) {
            case 'local': // 账号密码登录
                // 新用户就注册
                // auto_register=1 用户不存在，直接注册
                // auto_register=0 & silent = 1 用户不存在，正常返回token，但token内无用户数据
                // auto_register=0 & silent = 0 用户不存在，直接报错提示"用户未注册"
                $credentials = [
                    'username' => $request->input('username'),
                    'vcode' => $request->input('vcode'),
                    'check_type' => $request->input('check_type'),
                    'password' => $request->input('password'),
                    'company_id' => $companyId,
                    'auth_type' => 'local',
                    'auto_register' => (int)$request->input('auto_register', 0),
                    'silent' => $silent
                ];

                $preLoginInfo = [
                    'mobile' => $credentials['username']
                ];
                break;
            case 'wx_offiaccount': // 微信公众号授权登录
                $credentials = array_merge($request->input(), [
                    'company_id' => $companyId,
                    'auth_type' => 'wx_offiaccount',
                ]);
                break;
            case 'wxapp': // 微信小程序的授权登录
                $params = $request->all('auth_type', 'appid', 'code', 'iv', 'encryptedData', 'signature', 'rawData', 'uid', 'source_id', 'monitor_id', 'inviter_id', 'source_from', 'distributor_id', 'salesperson_id', 'invite_code', 'employee_auth', 'purchanse_share_code', 'puid');
                $params['company_id'] = $companyId;
                $params['wxa_appid'] = $authInfo['wxapp_appid'];
                $params['authorizer_appid'] = $authInfo['woa_appid'];
                $params['api_from'] = $authInfo['api_from'];
                $preLoginInfo = $loginService->wxappPreLogin($params);
                $credentials = [
                    'code' => $request->input('code'),
                    'appid' => $request->input('appid'),
                    'auth_type' => 'wxapp',
                    'company_id' => $companyId,
                    'origin' => app('request')->header('origin'),
                    'openid' => $preLoginInfo['open_id'], // 传入openid，避免下文又去微信那边获取openid
                    'unionid' => $preLoginInfo['unionid'], // 传入openid，避免下文又去微信那边获取openid
                ];
                break;
            case 'aliapp': // 支付宝小程序的授权登录
                $params = $request->all('auth_type', 'code', 'encryptedData', 'uid', 'source_id', 'monitor_id', 'inviter_id', 'source_from', 'distributor_id', 'salesperson_id', 'invite_code', 'employee_auth');
                $params['company_id'] = $companyId;
                $params['api_from'] = $authInfo['api_from'];
                $preLoginInfo = $loginService->aliappPreLogin($params);
                $credentials = [
                    'auth_type' => 'aliapp',
                    'company_id' => $companyId,
                    'origin' => app('request')->header('origin'),
                    'alipay_user_id' => $preLoginInfo['alipay_user_id'],
                ];
                break;
            default:
                throw new ResourceException('缺少参数，登录失败！');
        }

        $result = [
            'token' => null, // 登录的token
            'pre_login_data' => $preLoginInfo ?? null, // 预登录的数据
            'status' => 1, // 状态码【1 成功】【0 异常】
            'error_message' => null // 错误信息
        ];

        try {
            $result['token'] = app('auth')->guard('h5api')->attempt($credentials);
        } catch (ResourceException $resourceException) {
            if (!$silent) {
                throw $resourceException;
            }
            $result['error_message'] = $resourceException->getMessage();
            $result['status'] = 0;
        } catch (\Throwable $throwable) {
            if (!$silent) {
                throw $throwable;
            }
            $result['error_message'] = 'error!';
            $result['status'] = 0;
        }

        // 返回响应
        return $this->response->array($result);
    }
}
