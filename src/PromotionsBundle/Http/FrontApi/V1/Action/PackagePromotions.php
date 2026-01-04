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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use PromotionsBundle\Services\PackageService;

class PackagePromotions extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/promotions/package",
     *     summary="获取商品的组合商品列表",
     *     tags={"营销"},
     *     description="获取商品的组合商品列表",
     *     operationId="lists",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数,默认1", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="分页条数,默认20", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="1", description="自行更改字段描述"),
     *                 @SWG\Property( property="list", type="array",
     *                     @SWG\Items( type="object",
     *                         ref="#definitions/Package"
     *                     ),
     *                 ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function lists(Request $request)
    {
        // ShopEx EcShopX Core Module
        $authUser = $request->get('auth');
        $companyId = $authUser['company_id'];
        $itemId = $request->input('item_id');
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $distributor_id = $request->input('distributor_id', 0);
        $packageService = new PackageService();
        
        // 如果distributor_id大于0，先使用distributor_id查询
        if ($distributor_id > 0) {
            $result = $packageService->getPackageListByItemsId($companyId, $itemId, $page, $pageSize, [], ['distributor_id' => $distributor_id]);
            // 如果查询结果为空，再查询distributor_id=0的数据
            if (empty($result['list']) || $result['total_count'] == 0) {
                $result = $packageService->getPackageListByItemsId($companyId, $itemId, $page, $pageSize, [], ['distributor_id' => 0]);
            }
        } else {
            $result = $packageService->getPackageListByItemsId($companyId, $itemId, $page, $pageSize, [], ['distributor_id' => $distributor_id]);
        }
        
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotions/package/{packageId}",
     *     summary="获取组合商品的基础信息",
     *     tags={"营销"},
     *     description="获取组合商品的基础信息",
     *     operationId="info",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
     *     @SWG\Parameter( name="packageId", in="path", description="组合商品id", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         ref="#definitions/PackageDetail",
     *         ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function info($packageId, Request $request)
    {
        // ShopEx EcShopX Core Module
        $authUser = $request->get('auth');
        $woaAppid = $authUser['woa_appid'];
        $companyId = $authUser['company_id'];
        $packageService = new PackageService();
        $result = $packageService->getPackageInfoFront($companyId, $packageId, $woaAppid);
        return $this->response->array($result);
    }
}
