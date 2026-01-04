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

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use OrdersBundle\Services\CompanyRelDeliveryService;
use Swagger\Annotations as SWG;

class CompanyRelDeliveryController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/company/delivery",
     *     summary="获取商户同城配商家自配信息",
     *     tags={"订单"},
     *     description="获取商户同城配商家自配信息",
     *     operationId="getInfo",
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",required={"id","company_id","type","status","rules","created","updated"},
     *                   @SWG\Property(property="id", type="string", default="1", description="id"),
     *                   @SWG\Property(property="company_id", type="string", default="1", description="公司id"),
     *                   @SWG\Property(property="type", type="string", default="", description="配送类型【1 商家自配-按整单计算】【2 商家自配-按距离计算】"),
     *                   @SWG\Property(property="status", type="string", default="", description="状态【1 启用】【0 禁用】"),
     *                   @SWG\Property(property="rules", type="object", default="", description="规则内容", required={"freight_price"},
     *                       @SWG\Property(property="freight_price", type="string", default="", description="运费，单位为元"),
     *                   ),
     *                   @SWG\Property(property="created", type="integer", default="", description="创建时间"),
     *                   @SWG\Property(property="updated", type="integer", default="", description="更新时间"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function getInfo()
    {
        // Built with ShopEx Framework
        $companyId = app('auth')->user()->get('company_id');
        $result = (new CompanyRelDeliveryService())->find(["company_id" => $companyId]);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/company/delivery",
     *     summary="更新商户同城配商家自配信息",
     *     tags={"订单"},
     *     description="更新商户同城配商家自配信息",
     *     operationId="save",
     *     @SWG\Parameter( name="status", in="query", description="是否开启商家自配【null 不操作】【1 开启】【0 关闭】", required=false, type="integer"),
     *     @SWG\Parameter( name="type", in="query", description="商家自配的类型【null 不操作】【1 按整单计算】【2 按距离计算】", required=false, type="integer"),
     *     @SWG\Parameter( name="freight", in="query", description="商家自配的运费，单位为元，如果为null则默认为0", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(
     *            @SWG\Property(property="data", type="object", description="",
     *                   @SWG\Property(property="id", type="string", default="1", description="id"),
     *                   @SWG\Property(property="company_id", type="string", default="1", description="公司id"),
     *                   @SWG\Property(property="type", type="string", default="", description="配送类型【1 商家自配-按整单计算】【2 商家自配-按距离计算】"),
     *                   @SWG\Property(property="status", type="string", default="", description="状态【1 启用】【0 禁用】"),
     *                   @SWG\Property(property="rules", type="object", default="", description="规则内容", required={"freight_price"},
     *                       @SWG\Property(property="freight_price", type="string", default="", description="运费，单位为元"),
     *                   ),
     *                   @SWG\Property(property="created", type="integer", default="", description="创建时间"),
     *                   @SWG\Property(property="updated", type="integer", default="", description="更新时间"),
     *               ),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function save(Request $request)
    {
        $companyId = (int)app('auth')->user()->get('company_id');

        $deliveryType = $request->input("type");
        if (is_null($deliveryType)) {
            throw new \Exception("类型必填！");
        }
        $deliveryStatus = $request->input("status");
        if (!is_null($deliveryStatus)) {
            $deliveryStatus = (int)$deliveryStatus;
        }
        $deliveryFreight = (string)$request->input("freight");
        if (!is_numeric($deliveryFreight)) {
            $deliveryFreight = "0";
        }
        // 更新商家自配信息
        $result = (new CompanyRelDeliveryService())->save($companyId, (int)$deliveryType, $deliveryStatus, $deliveryFreight);

        return $this->response->array($result);
    }
}
