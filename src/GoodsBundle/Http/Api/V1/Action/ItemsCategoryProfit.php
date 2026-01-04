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

namespace GoodsBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;

use GoodsBundle\Services\ItemsCategoryProfitService;

class ItemsCategoryProfit extends Controller
{
    /**
     * @SWG\post(
     *     path="/goods/category/profit/save",
     *     summary="保存商品分类导购分润价格配置",
     *     tags={"商品"},
     *     description="保存商品分类导购分润价格配置",
     *     operationId="saveItemsCategoryProfit",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="query", description="商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="profit", in="query", description="拉新分润金额", required=true, type="string"),
     *     @SWG\Parameter( name="popularize_profit", in="query", description="推广分润金额", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function saveItemsCategoryProfit(Request $request)
    {
        $params = $request->input();
        $params['company_id'] = app('auth')->user()->get('company_id');
        $itemsCategoryProfitService = new ItemsCategoryProfitService();
        $result = $itemsCategoryProfitService->saveItemsCategoryProfit($params);
        return $this->response->array(['status' => true]);
    }
}
