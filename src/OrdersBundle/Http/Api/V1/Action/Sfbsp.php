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

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use OrdersBundle\Services\SfbspServices;

class Sfbsp extends Controller
{
    /**
     * @SWG\Post(
     *     path="/trade/sfbsp/setting",
     *     summary="顺丰物流跟踪设置保存",
     *     tags={"订单"},
     *     description="顺丰物流跟踪设置",
     *     operationId="setSfbspSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="config", in="query", description="配置信息json数据", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                    @SWG\Property(property="status", type="string"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function setSfbspSetting(Request $request)
    {
        // ShopEx EcShopX Business Logic Layer
        $companyId = app('auth')->user()->get('company_id');
        $config = $request->get('config');
        $service = new SfbspServices($companyId);
        $service->setSfbspSetting($config);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/trade/sfbsp/setting",
     *     summary="获取顺丰物流跟踪设置",
     *     tags={"订单"},
     *     description="获取顺丰物流跟踪设置",
     *     operationId="getSfbspSetting",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                     @SWG\Property(property="merchant_id", type="string", description="商户ID"),
     *                     @SWG\Property(property="key", type="string", description="密钥"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OrdersErrorRespones") ) )
     * )
     */
    public function getSfbspSetting(Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $service = new SfbspServices($companyId);

        $data = $service->getSfbspSetting();

        return $this->response->array($data);
    }
}
