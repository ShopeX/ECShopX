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

namespace MembersBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;

use Illuminate\Http\Request;
use Illuminate\Http\Response;


use MembersBundle\Services\MemberService;
use MembersBundle\Services\TrustLoginService;

class TrustLogin extends Controller
{
    public $memberService;
    public $trustLoginService;

    public function __construct()
    {
        $this->memberService = new MemberService();
        $this->trustLoginService = new TrustLoginService();
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trustlogin/params",
     *     summary="获取信任登录传参信息",
     *     tags={"会员"},
     *     description="获取信任登录传参信息",
     *     operationId="getTrustLoginParams",
     *     @SWG\Parameter(
     *         name="trustlogin_tag",
     *         in="query",
     *         description="信任登录标签",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="version_tag",
     *         in="query",
     *         description="信任登录类型 standard pc端 touch h5端",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="redirect",
     *         in="query",
     *         description="上一次的访问路径",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="config_info", type="object",
     *                          @SWG\Property( property="type", type="string", example="weixin", description="类型"),
     *                          @SWG\Property( property="app_id", type="string", example="wxd580d54b0167fa28", description="app_id"),
     *                          @SWG\Property( property="secret", type="string", example="99744748ed13a94766d172d832cb6670", description="secret"),
     *                          @SWG\Property( property="name", type="string", example="微信", description="名称"),
     *                          @SWG\Property( property="status", type="string", example="true", description="状态"),
     *                  ),
     *                  @SWG\Property( property="redirect_url", type="string", example="https://open.weixin.qq.com/connect/qrconnect?appid=wxd580d54b0167fa28&redirect_uri=https%3A%2F%2Fecshopx.shopex123.com%2F&response_type=code&scope=snsapi_login&state=1d2f6a1720833fee7283182f97a7738f#wechat_redirect", description="跳转地址"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTrustLoginParams(Request $request)
    {
        $authInfo = $request->get('auth');
        $company_id = $authInfo['company_id'];
        $version_tag = $request->get('version_tag', 'standard');
        $trustlogin_tag = $request->get('trustlogin_tag', 'weixin');
        $data['h5_host'] = $request->server('HTTP_ORIGIN') ? $request->server('HTTP_ORIGIN') : ''; // 获取原始请求域名
        $data['redirect_url'] = $request->get('redirect_url', '');
        $result = $this->trustLoginService->trustLoginParams($company_id, $trustlogin_tag, $version_tag, $data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/trustlogin/list",
     *     summary="获取第三方登录配置列表",
     *     tags={"会员"},
     *     description="获取第三方登录配置列表",
     *     operationId="getTrustLoginList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="version_tag",
     *         in="query",
     *         description="信任登录类型 standard pc端 touch h5端",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="type", type="string", example="weixin", description="类型"),
     *                  @SWG\Property( property="app_id", type="string", example="wxd580d54b0167fa28", description="app_id"),
     *                  @SWG\Property( property="secret", type="string", example="99744748ed13a94766d172d832cb6670", description="secret"),
     *                  @SWG\Property( property="name", type="string", example="微信", description="名称"),
     *                  @SWG\Property( property="status", type="string", example="true", description="状态"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTrustLoginList(Request $request)
    {
        $authInfo = $request->get('auth');
        $companyId = $authInfo['company_id'];
        $version_tag = $request->get('version_tag', 'standard');
        $result = [];
        $data = $this->trustLoginService->getTrustLoginList($companyId);
        $result = !empty($data) ? $data[$version_tag] : [];
        return $this->response->array($result);
    }
}
