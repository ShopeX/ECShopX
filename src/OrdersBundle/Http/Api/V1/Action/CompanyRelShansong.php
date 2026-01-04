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

namespace OrdersBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\CompanyRelShansongService;
use ThirdPartyBundle\Services\ShansongCenter\ShopService;

class CompanyRelShansong extends Controller
{
    /**
     * @SWG\Get(
     *     path="/company/shansong/info",
     *     summary="获取商户闪送应用配置信息",
     *     tags={"订单"},
     *     description="获取商户闪送应用配置信息",
     *     operationId="getInfo",
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="3", description="id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="string", example="", description="商户ID"),
     *               @SWG\Property(property="client_id", type="string", example="", description="App-key"),
     *               @SWG\Property(property="app_secret", type="string", example="", description="App-密钥"),
     *               @SWG\Property(property="online", type="boolean", example="1", description="是否上线"),
     *               @SWG\Property(property="is_open", type="string", example="0", description="是否开启"),
     *               ),
     *            ),
     *         ),
     *     ),
     * @SWG\Response(response="default", description="错误返回结构", @SWG\Schema(type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones")))
     * )
     */
    public function getInfo()
    {
        // Ver: 8d1abe8e
        $companyId = app('auth')->user()->get('company_id');
        $companyRelShansongService = new CompanyRelShansongService();
        $filter = ['company_id' => $companyId];
        $result = $companyRelShansongService->getInfo($filter);
        if ($result) {
            $result['online'] = $result['online'] == 'true' ? "1" : "0";
            $result['is_open'] = $result['is_open'] == 'true' ? "1" : "0";
        }
        $shopService = new ShopService();
        $result['business_list'] = $shopService->getBusinessList();
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/company/shansong/info",
     *     summary="保存商户闪送应用配置信息",
     *     tags={"订单"},
     *     description="保存商户闪送应用配置信息",
     *     operationId="saveInfo",
     *     @SWG\Parameter( name="", in="query", description="开通状态", required=true, type="boolean"),
     *     @SWG\Parameter( name="shop_id", in="query", description="商户ID", required=true, type="string"),
     *     @SWG\Parameter( name="client_id", in="query", description="App-key", required=true, type="string"),
     *     @SWG\Parameter( name="app_secret", in="query", description="App-密钥", required=true, type="string"),
     *     @SWG\Parameter( name="online", in="query", description="是否上线", required=true, type="string"),
     *     @SWG\Parameter( name="is_open", in="query", description="是否开启", required=true, type="boolean"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *               @SWG\Property(property="id", type="string", example="3", description="id"),
     *               @SWG\Property(property="company_id", type="string", example="1", description="公司id"),
     *               @SWG\Property(property="shop_id", type="string", example="", description="商户ID"),
     *               @SWG\Property(property="client_id", type="string", example="", description="App-key"),
     *               @SWG\Property(property="app_secret", type="string", example="", description="App-密钥"),
     *               @SWG\Property(property="online", type="boolean", example="1", description="是否上线"),
     *               @SWG\Property(property="is_open", type="string", example="0", description="是否开启"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function saveInfo(Request $request)
    {
        // Ver: 8d1abe8e
        $params = $request->all('shop_id', 'client_id', 'app_secret', 'online', 'freight_type', 'is_open');
        $rules = [
            'shop_id' => 'required',
            'client_id' => 'required',
            'app_secret' => 'required',
            'online' => 'required|boolean',
            'freight_type' => 'required',
            'is_open' => 'required|boolean',
        ];
        $msg = [
            'shop_id.required' => '商户ID必填',
            'client_id.required' => 'App-key必填',
            'app_secret.required' => 'App-密钥必填',
            'online.required' => '是否上线必填',
            'online.boolean' => '是否上线类型错误',
            'freight_type' => '运费承担方必填',
            'is_open.required' => '是否开启必填',
            'is_open.boolean' => '是否开启参数类型错误',
        ];
        $validator = app('validator')->make($params, $rules, $msg);
        if ($validator->fails()) {
            $errorsMsg = $validator->errors()->toArray();
            $errmsg = current($errorsMsg)[0];
            throw new ResourceException($errmsg);
        }

        $companyId = app('auth')->user()->get('company_id');
        $companyRelShansongService = new CompanyRelShansongService();
        $info = $companyRelShansongService->getInfo(['company_id' => $companyId]);
        if (!$info) {
            $params['company_id'] = $companyId;
            $result = $companyRelShansongService->create($params);
        } else {
            $filter = ['company_id' => $companyId];
            $result = $companyRelShansongService->updateOneBy($filter, $params);
        }
        return $this->response->array($result);
    }
}
