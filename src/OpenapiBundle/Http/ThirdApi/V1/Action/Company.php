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

namespace OpenapiBundle\Http\ThirdApi\V1\Action;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;

use CompanysBundle\Services\ShopsService;
use CompanysBundle\Services\Shops\WxShopsService;

class Company extends Controller
{
    /**
     * @SWG\Get(
     *     path="/ecx.company.info",
     *     summary="获取云店基本信息",
     *     tags={"企业"},
     *     description="获取云店基本信息",
     *     operationId="getInfo",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.company.info" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *              @SWG\Property( property="brand_name", type="string", description="商城名称"),
     *              @SWG\Property( property="logo", type="string", description="商城logo"),
     *              @SWG\Property( property="company_id", type="string", description="COMPANY_ID"),
     *          )
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getInfo(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $shopsService = new ShopsService(new WxShopsService());
        $data = $shopsService->getWxShopsSetting($companyId);
        if (!$data) {
            $data = [];
        }
        $data['company_id'] = $companyId;
        $this->api_response('true', 'success', $data);
    }
}
