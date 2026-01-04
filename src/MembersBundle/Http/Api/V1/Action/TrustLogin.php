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

namespace MembersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\TrustLoginService;

class TrustLogin extends Controller
{
    public $memberService;

    public function __construct()
    {
        $this->trustLoginService = new TrustLoginService();
    }

    /**
     * @SWG\POST(
     *     path="/members/trustlogin/list",
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
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="standard", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="type", type="string", example="weixin", description="适用类型standard 标准版"),
     *                          @SWG\Property( property="app_id", type="string", example="wxd580d54b0167fa28", description="app_id"),
     *                          @SWG\Property( property="secret", type="string", example="99744748ed13a94766d172d832cb6670", description="secret"),
     *                          @SWG\Property( property="name", type="string", example="微信", description="配置名称"),
     *                          @SWG\Property( property="status", type="string", example="true", description="开启状态"),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="touch", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="type", type="string", example="weixin", description="适用类型touch触屏版"),
     *                          @SWG\Property( property="app_id", type="string", example="", description="app_id"),
     *                          @SWG\Property( property="secret", type="string", example="", description="secret"),
     *                          @SWG\Property( property="name", type="string", example="微信", description="配置名称"),
     *                          @SWG\Property( property="status", type="string", example="false", description="开启状态"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTrustLoginList(Request $request)
    {
        $params = $request->input();
        $companyId = app('auth')->user()->get('company_id');

        $result = [];
        $result = $this->trustLoginService->getTrustLoginList($companyId);
        // $result = array_values($result);

        return $this->response->array($result);
    }

    /**
     * @SWG\PUT(
     *     path="/members/trustlogin/setting",
     *     summary="修改信任登录状态",
     *     tags={"会员"},
     *     description="修改信任登录状态",
     *     operationId="saveStatusSetting",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter( name="type", in="query", description="配置类型：weixin", required=true, type="string"),
     *     @SWG\Parameter( name="name", in="query", description="配置名称", required=true, type="string"),
     *     @SWG\Parameter( name="app_id", in="query", description="app_id", required=true, type="string"),
     *     @SWG\Parameter( name="secret", in="query", description="secret", required=true, type="string"),
     *     @SWG\Parameter( name="loginversion", in="query", description="配置版本：标准版 standard 触屏版 touch", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="启用状态：启用 true  关闭 false", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="string", example="true", description=""),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function saveStatusSetting(Request $request)
    {
        $params = $request->input();
        $companyId = app('auth')->user()->get('company_id');
        $params['status'] = ($params['status'] ?? false) === true;
        $result = $this->trustLoginService->saveStatusSetting($params, $companyId);

        return $this->response->array(['data' => $result]);
    }
}
