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
use CompanysBundle\Repositories\CompanysRepository;
use CompanysBundle\Services\AuthService;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\StoreResourceFailedException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Illuminate\Http\Request;

class WorkWechat extends Controller
{
    /**
     * @SWG\POST(
     *     path="/operator/workwechat/oauth/login",
     *     summary="企业微信回调登录",
     *     tags={"workwechat"},
     *     description="企业微信回调登录",
     *     operationId="login",
     *     @SWG\Parameter( name="company_id", in="query", description="企业微信cory_id", required=true, type="string"),
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
     *                     @SWG\Property(property="work_userid", type="string", description="未绑定情况下"),
     *                     @SWG\Property(property="check_token", type="string", description="未绑定情况下"),
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
            'logintype' => 'oauthworkwechat',
        ];

        $result = [
            'status' => '',
            'token' => '',
            'company_id' => '',
            'work_userid' => '',
            'check_token' => '',
        ];

        try {
            $token = app('auth')->guard('api')->attempt($params);
        } catch (ResourceException $e) {
            $errors = $e->getErrors();
            if ($errors && $bind_info = $errors->get('bind_info')) {
                $result['status'] = 'unbound';
                $result['company_id'] = $bind_info['company_id'];
                $result['work_userid'] = $bind_info['work_userid'];
                $result['check_token'] = $bind_info['check_token'];
                return $this->response->array($result);
            }
            throw $e;
        }
        $result['status'] = 'success';
        $result['token'] = $token;
        return $this->response->array($result);
    }

    /**
     * @SWG\POST(
     *     path="/operator/workwechat/bind_mobile",
     *     summary="企业微信绑定本地账号",
     *     tags={"workwechat"},
     *     description="企业微信绑定本地账号",
     *     operationId="login",
     *     @SWG\Parameter( name="company_id", in="query", description="企业id", required=true, type="string"),
     *     @SWG\Parameter( name="work_userid", in="query", description="微信用户id", required=true, type="string"),
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
        $params = $request->all('company_id', 'work_userid', 'check_token', 'mobile', 'vcode');
        $rules = [
            'company_id' => ['required', 'company_id必填'],
            'mobile' => ['required', '请输入合法手机号码'],
            'vcode' => ['required', '请输入短信验证码'],
            'work_userid' => ['required', '企业微信用户ID必填'],
            'check_token' => ['required', '校验码必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        if (!ismobile($params['mobile'])) {
            throw new ResourceException('请输入合法手机号码');
        }

        $params['logintype'] = 'workwechatbind';
        $token = app('auth')->guard('api')->attempt($params);

        return $this->response->array(['token' => $token]);
    }

    /**
     * @SWG\Get(
     *   path="/operator/workwechat/authorizeurl",
     *   tags={"workwechat"},
     *   summary="获取企业微信oauth链接",
     *   description="获取企业微信oauth链接",
     *   operationId="getWorkwechatOuthorizeurl",
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
    public function getWorkwechatOuthorizeurl(Request $request)
    {
        if (!$request->query('company_id')) {
            throw new BadRequestHttpException('缺少参数');
        }

        $company_id = $request->query('company_id');
        $filter = [
            'company_id' => $company_id
        ];
        /** @var CompanysRepository $companysRepository */
        $companysRepository = app('registry')->getManager('default')->getRepository(\CompanysBundle\Entities\Companys::class);
        if (!$companysRepository->getInfo($filter)) {
            throw new ResourceException('company_id');
        }

        $authService = new AuthService();
        $url = $authService->getWorkwechatOuthorizeurl($company_id);

        return $this->response->array(['url' => $url]);
    }
}
