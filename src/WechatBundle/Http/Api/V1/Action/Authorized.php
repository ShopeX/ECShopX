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

namespace WechatBundle\Http\Api\V1\Action;

use EasyWeChat\Kernel\Exceptions\Exception as EasyWeChatException;
use SuperAdminBundle\Services\ShopMenuService;
use WechatBundle\Services\OpenPlatform;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Dingo\Api\Exception\ResourceException;

class Authorized extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wechat/pre_auth_url",
     *     summary="获取微信公众号预授权URL地址",
     *     tags={"微信"},
     *     description="商家将公众号，小程序等微信账号预授权给第三方开发者，第一步：URL跳转地址",
     *     operationId="getPreAuthUrl",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="callback_url",
     *         in="query",
     *         description="授权成功后回调地址",
     *         required=true,
     *         type="string",
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
     *                     @SWG\Property(property="url", type="stirng"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getPreAuthUrl(Request $request)
    {
        $callbackUrl = $request->input('callback_url');
        //验证URL

        $openPlatform = new OpenPlatform();

        try {
            $url = $openPlatform->getPreAuthUrl($callbackUrl);
        } catch (EasyWeChatException $e) {
            return $this->response->array(['url' => '']);
        }
        return $this->response->array(['url' => $url]);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/directbind",
     *     summary="直连小程序绑定",
     *     tags={"微信"},
     *     description="客户直接填写小程序appid,secret直连方式接入",
     *     operationId="directBind",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="template_name",
     *         in="query",
     *         description="小程序模板",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="nick_name",
     *         in="query",
     *         description="小程序名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="authorizer_appid",
     *         in="query",
     *         description="小程序appid",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="authorizer_appsecret",
     *         in="query",
     *         description="小程序appsecret",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="signature",
     *         in="query",
     *         description="小程序描述",
     *         type="string",
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
     *                     @SWG\Property(property="status", type="stirng", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function directBind(Request $request)
    {
        $params = $request->all('template_name', 'nick_name', 'authorizer_appid', 'authorizer_appsecret', 'signature');
        $bind_type = $request->input('bind_type');
        if (!in_array($bind_type, ['miniprogram', 'offiaccount'])) {
            throw new ResourceException('您传入的绑定小程序或者公众号类型有误！');
        }
        if ($bind_type == 'offiaccount') {
            $rules = [
                'nick_name' => ['required', '请输入公众号名称'],
                'authorizer_appid' => ['required', '请输入公众号appid'],
                'authorizer_appsecret' => ['required', '请输入公众号appsecret'],
            ];
        } elseif ($bind_type == 'miniprogram') {
            $rules = [
                'template_name' => ['required', '请选择小程序模板'],
                'nick_name' => ['required', '请输入小程序名称'],
                'authorizer_appid' => ['required', '请输入小程序appid'],
                'authorizer_appsecret' => ['required', '请输入小程序appsecret'],
            ];
        }
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['operator_id'] = app('auth')->user()->get('operator_id');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $openPlatform = new OpenPlatform();
        $data = $openPlatform->directAuthorizedBind($params, $bind_type);
        return $this->response->array($data);
    }

    /**
     * @SWG\Post(
     *     path="/wechat/bind",
     *     summary="绑定微信公众号，或小程序预授权信息和账号信息",
     *     tags={"微信"},
     *     description="商家将公众号，小程序等微信账号预授权给第三方开发者，第二步：通过微信回调绑定当前授权信息",
     *     operationId="authorizedBind",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="auth_code",
     *         in="query",
     *         description="授权成功后微信返回的auth_code",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="auth_type",
     *         in="query",
     *         description="授权类型，公众号woa 或者小程序 wxa",
     *         required=true,
     *         type="string",
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
     *                     @SWG\Property(property="status", type="stirng", example="true"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function authorizedBind(Request $request)
    {
        $authorizationCode = $request->input('auth_code');
        $authorizationType = $request->input('auth_type');

        $openPlatform = new OpenPlatform();
        $data = $openPlatform->authorizedBind($authorizationCode, $authorizationType);
        return $this->response->array($data);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/authorizerinfo",
     *     summary="获取公众帐号基础信息",
     *     tags={"微信"},
     *     description="获取公众帐号基础信息",
     *     operationId="getAuthorizerInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09"),
     *                  @SWG\Property( property="nick_name", type="string", example="oneX云店测试"),
     *                  @SWG\Property( property="head_img", type="string", example="http://wx.qlogo.cn/mmopen/JtEZhskrzibNG0mE54HV6tppmEDC4FLTgg1VicWWSrcn6AFiaebFcE4ymu5j4eibWBb6YzEs8FWhQEeCzedbY4m4E12dz5IGbDou/0"),
     *                  @SWG\Property( property="service_type_info", type="string", example="2"),
     *                  @SWG\Property( property="verify_type_info", type="string", example="0"),
     *                  @SWG\Property( property="user_name", type="string", example="gh_a8e449df7988"),
     *                  @SWG\Property( property="signature", type="string", example="源源客产品官方demo服务号"),
     *                  @SWG\Property( property="principal_name", type="string", example="商派软件有限公司"),
     *                  @SWG\Property( property="alias", type="string", example="yykdemo"),
     *                  @SWG\Property( property="business_info", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="qrcode_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkredmkQ5xOdRJTqlIdn1YajQ8IRyGDsiaician053WD5tiaxR9B07Qoxk7Dek1ItibXgWLiaLzjmLTRjic93g/0"),
     *                  @SWG\Property( property="miniprograminfo", type="array",
     *                      @SWG\Items( type="string", example="undefined"),
     *                  ),
     *                  @SWG\Property( property="func_info", type="string", example="1,15,4,7,2,11,6,9,24,26,34"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/WechatErrorRespones") ) )
     * )
     */
    public function getAuthorizerInfo()
    {
        $authUser = app('auth')->user();
        $authorizerAppId = $authUser->get('authorizer_appid');
        $companyId = $authUser->get('company_id');

        $openPlatform = new OpenPlatform();
        $result = $openPlatform->getAuthorizerInfo($authorizerAppId);
        $menuType = (new ShopMenuService())->getMenuTypeByCompanyId($companyId);
        $result['menu_type'] = $menuType['menu_type_str'];

        return $this->response->array($result);
    }
}
