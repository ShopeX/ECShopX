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

use GoodsBundle\Services\ItemsCommissionService;

use Dingo\Api\Exception\ResourceException;

/**
 * 商品佣金配置，用于斗拱平台结算佣金
 */
class ItemsCommission extends Controller
{
    /**
     * @SWG\post(
     *     path="/goods/commission/save",
     *     summary="保存商品佣金配置",
     *     tags={"商品"},
     *     description="保存商品佣金配置",
     *     operationId="saveGoodsCommission",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品ID", required=true, type="string"),
     *     @SWG\Parameter( name="goods_id", in="query", description="产品ID", required=true, type="string"),
     *     @SWG\Parameter( name="commission_type", in="query", description="佣金计算方式 1:按照比例,2:按照填写金额", required=true, type="string"),
     *     @SWG\Parameter( name="commission", in="query", description="产品佣金", required=true, type="string"),
     *     @SWG\Parameter( name="sku_commission", in="query", description="sku佣金json", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function saveGoodsCommission(Request $request)
    {
        // EcShopX core
        $params = $request->input();
        $rule = [
            'item_id' => ['required', '商品不存在'],
            'goods_id' => ['required', '产品不存在'],
            'commission_type' => ['required|in:1,2', '佣金类型错误'],
            'commission' => ['required', 'SPU结算佣金不能为空'],
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $itemsCommissionService = new ItemsCommissionService();
        $result = $itemsCommissionService->saveItemsCommission($params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\get(
     *     path="/goods/commission/{item_id}",
     *     summary="获取商品佣金配置",
     *     tags={"商品"},
     *     description="获取商品佣金配置",
     *     operationId="getItemsCommission",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="path", description="商品ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="item_id", type="string", example="5030", description="商品id"),
     *                          @SWG\Property( property="goods_id", type="string", example="normal", description="货品id"),
     *                          @SWG\Property( property="commission_type", type="string", example="every", description="佣金计算方式 1:按照比例,2:按照填写金额"),
     *                          @SWG\Property( property="commission", type="string", example="", description="佣金"),
     *                          @SWG\Property( property="sku_commission", type="array", example="", description="sku佣金"),
     *                          
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsCommission($item_id)
    {
        // EcShopX core
        $params['item_id'] = $item_id;
        $rule = [
            'item_id' => ['required', '商品不存在'],
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');
        $itemsCommissionService = new ItemsCommissionService();
        $result = $itemsCommissionService->getItemsCommission($params);

        return $this->response->array($result);
    }
}
