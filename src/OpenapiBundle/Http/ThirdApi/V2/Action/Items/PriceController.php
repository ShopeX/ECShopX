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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Items;

use Illuminate\Http\Request;
use OpenapiBundle\Constants\ErrorCode;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Filter\Item\PriceFilter;
use OpenapiBundle\Http\Controllers\Controller;
use OpenapiBundle\Services\Distributor\DistributorItemService;
use OpenapiBundle\Services\Distributor\DistributorService;
use Swagger\Annotations as SWG;
use OpenapiBundle\Services\Items\ItemsService;
use CompanysBundle\Ego\CompanysActivationEgo;

class PriceController extends Controller
{
    /**
     * @SWG\Put(
     *     path="/ecx.item.price.sync",
     *     tags={"商品"},
     *     summary="同步/店铺商品价格 - 同步价格",
     *     description="请求参数如果无法正常显示，则进入【编辑】→ 【请求参数设置】→ 【Body】→ 选中【form】即可",
     *     operationId="sync",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *     @SWG\Parameter(name="method", in="query", description="请求的方法名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="item_code", in="formData", description="商品货号", required=true, type="string"),
     *     @SWG\Parameter(name="price", in="formData", description="销售价", required=false, type="string"),
     *     @SWG\Parameter(name="market_price", in="formData", description="市场价", required=false, type="string"),
     *     @SWG\Parameter(name="cost_price", in="formData", description="成本价", required=false, type="string"),
     *     @SWG\Parameter(name="price", in="formData", description="销售价", required=false, type="string"),
     *     @SWG\Parameter(name="distributor_code", in="formData", description="店铺码（不传值同步总部价格，传值同步店铺价格，店铺只能同步销售价）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"status","code","message","data"},
     *             @SWG\Property(property="status", type="string", default="success", description=""),
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="成功", description=""),
     *             @SWG\Property(property="data", type="string", default="", description=""),
     *         ),
     *     ),
     * )
     */
    public function sync(Request $request)
    {
        $requestData = $request->only(["item_code", "price", "market_price", "cost_price", "distributor_code"]);
        if ($messageBag = validation($requestData, [
            "item_code" => ["required"],
            "distributor_code" => ["nullable"],
        ], [
            "item_code.*" => "商品货号参数错误",
            "distributor_code.*" => "店铺ID参数错误",
        ])) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $messageBag->first());
        }

        if (isset($requestData["price"]) && (int)bcmul($requestData["price"], 100, 0) <= 0) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, '销售价参数错误');
        }

        if (isset($requestData["market_price"]) && (int)bcmul($requestData["market_price"], 100, 0) <= 0) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, '市场价参数错误');
        }

        if (isset($requestData["cost_price"]) && (int)bcmul($requestData["cost_price"], 100, 0) <= 0) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, '成本价参数错误');
        }

        // 获取过滤条件
        $filter = (new PriceFilter($requestData))->get();

        $company = (new CompanysActivationEgo())->check($filter['company_id']);

        // 同步总部/店铺商品价格
        if (isset($filter["distributor_code"]) && $company['product_model'] == 'standard') {
            // 更新店铺商品价格
            (new DistributorItemService())->save($filter, $requestData, true);
        } else {
            // 更新总部价格
            (new ItemsService())->updatePrice($filter, $requestData);
        }
        return $this->response->array([]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.item.price.get",
     *     tags={"商品"},
     *     summary="同步/店铺商品价格 - 查询",
     *     description="同步/店铺商品价格 - 查询",
     *     operationId="detail",
     *     @SWG\Parameter(name="app_key", in="query", description="app_key", required=true, type="string"),
     *     @SWG\Parameter(name="version", in="query", description="版本号", required=true, type="string"),
     *     @SWG\Parameter(name="timestamp", in="query", description="请求时间(合法的日期时间)", required=true, type="string"),
     *     @SWG\Parameter(name="sign", in="query", description="签名", required=true, type="string"),
     *
     *     @SWG\Parameter(name="item_code", in="query", description="商品货号", required=true, type="string"),
     *     @SWG\Parameter(name="distributor_code", in="query", description="店铺码（不传值同步总部价格，传值同步店铺价格）", required=false, type="string"),
     *     @SWG\Response(
     *         response="200",
     *         description="响应信息返回",
     *         @SWG\Schema(required={"status","code","message","data"},
     *             @SWG\Property(property="status", type="string", default="success", description=""),
     *             @SWG\Property(property="code", type="string", default="E0000", description=""),
     *             @SWG\Property(property="message", type="string", default="成功", description=""),
     *            @SWG\Property(property="data", type="object", description="",required={"item_code","item_name","price","market_price","cost_price","distributor_code","distributor_name"},
     *               @SWG\Property(property="item_code", type="string", default="S60EBF55D003CD", description=""),
     *               @SWG\Property(property="item_name", type="string", default="跨境多规格", description=""),
     *               @SWG\Property(property="price", type="string", default="1000", description=""),
     *               @SWG\Property(property="market_price", type="string", default="1000", description=""),
     *               @SWG\Property(property="cost_price", type="string", default="1000", description=""),
     *               @SWG\Property(property="distributor_code", type="string", default="aabb01", description=""),
     *               @SWG\Property(property="distributor_name", type="string", default="aabb01", description=""),
     *            ),
     *         ),
     *     ),
     * )
     */
    public function detail(Request $request)
    {
        // 获取过滤条件
        $filter = (new PriceFilter())->get();

        $result = [
            "item_code" => "",
            "item_name" => "",
            "price" => 0,
            "market_price" => 0,
            "cost_price" => 0,
            //"distributor_id"   => null,
            // "distributor_code" => null,
            // "distributor_name" => null
        ];

        // 获取总部商品的信息
        $itemInfo = (new ItemsService())->find($filter);
        if (empty($itemInfo)) {
            throw new ErrorException(ErrorCode::GOODS_NOT_FOUND);
        }

        $result["item_code"] = (string)$itemInfo["item_bn"];
        $result["item_name"] = (string)$itemInfo["item_name"];
        $result["price"] = bcdiv($itemInfo["price"], 100, 2);
        $result["market_price"] = bcdiv($itemInfo["market_price"], 100, 2);
        $result["cost_price"] = bcdiv($itemInfo["cost_price"], 100, 2);

        $company = (new CompanysActivationEgo())->check($filter['company_id']);

        // 查询总部/店铺商品价格
        if (isset($filter["distributor_code"]) && $company['product_model'] == 'standard') {
            $distributorInfo = (new DistributorService())->findByIdOrCode($filter);
            if (empty($distributorInfo)) {
                throw new ErrorException(ErrorCode::DISTRIBUTOR_NOT_FOUND);
            }
            // 更新店铺商品价格
            $distributorItemInfo = (new DistributorItemService())->find([
                "company_id" => $filter["company_id"],
                "distributor_id" => $filter["distributor_id"],
                "item_id" => $itemInfo["item_id"]
            ]);
            if (!empty($distributorItemInfo)) {
                $result["price"] = bcdiv($distributorItemInfo["price"], 100, 2);
            }
            //$result["distributor_id"] = (int)$distributorInfo["distributor_id"];
            $result["distributor_code"] = (string)$distributorInfo["shop_code"];
            $result["distributor_name"] = (string)$distributorInfo["name"];
        }
        return $this->response->array($result);
    }
}
