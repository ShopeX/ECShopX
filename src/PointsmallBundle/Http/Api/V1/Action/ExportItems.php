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

namespace PointsmallBundle\Http\Api\V1\Action;

use EspierBundle\Jobs\ExportFileJob;
use GoodsBundle\Services\ItemsCategoryService;
use PointsmallBundle\Services\ItemsService;
use Illuminate\Http\Request;

class ExportItems
{
    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/export",
     *     summary="导出商品",
     *     tags={"积分商城"},
     *     description="导出商品，进入队列，从导出列表中去下载",
     *     operationId="exportItemsData",
     *     @SWG\Parameter(name="Authorization", in="header", description="JWT验证token", type="string"),
     *     @SWG\Parameter(name="keywords", in="formData", description="商品关键词", required=false, type="string"),
     *     @SWG\Parameter(name="templates_id", in="formData", description="运费模板id", required=false, type="integer"),
     *     @SWG\Parameter(name="regions_id", in="formData", description="产地省市区id,数组", required=false, type="string"),
     *     @SWG\Parameter(name="nospec", in="formData", description="是否为单规格", required=false, type="boolean"),
     *     @SWG\Parameter(name="approve_status", in="formData", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show:前台仅展示", required=false, type="string"),
     *     @SWG\Parameter(name="item_id", in="formData", description="商品id数组", required=false, type="string"),
     *     @SWG\Parameter(name="main_cat_id", in="formData", description="主类目id", required=false, type="integer"),
     *     @SWG\Parameter(name="category", in="formData", description="分类id", required=false, type="integer"),
     *     @SWG\Parameter(name="brand_id", in="formData", description="品牌id", required=false, type="integer"),
     *     @SWG\Parameter(name="item_bn", in="formData", description="商品编码", required=false, type="string"),
     *     @SWG\Parameter(name="item_type", in="formData", description="商品类型，services：服务商品，normal: 普通商品;暂时只支持normal", required=false, type="string"),
     *     @SWG\Parameter(name="store_gt", in="formData", description="库存大于", required=false, type="integer"),
     *     @SWG\Parameter(name="store_lt", in="formData", description="库存小于", required=false, type="integer"),
     *     @SWG\Parameter(name="price_gt", in="formData", description="积分价格大于", required=false, type="integer"),
     *     @SWG\Parameter(name="price_lt", in="formData", description="积分价格小于", required=false, type="integer"),
     *     @SWG\Parameter(name="is_sku", in="formData", description="是否要查询sku", required=false, type="boolean"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="boolean"),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function exportItemsData(Request $request)
    {
        $authdata = app('auth')->user()->get();
        $inputData = $request->input();

        $params['company_id'] = app('auth')->user()->get('company_id');
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($inputData['keywords']);
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($inputData['main_cat_id'], $params['company_id']);
            $itemCategory[] = $inputData['main_cat_id'];
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $params['company_id']);
            if (!$ids) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return response()->json($result);
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["point|gt"] = $inputData['price_gt'];
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["point|lt"] = $inputData['price_lt'];
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return response()->json($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $params['isGetSkuList'] = true;
        } else {
            $params['isGetSkuList'] = false;
            // $params['is_default'] = true;
        }

        //存储导出操作账号者
        $operator_id = app('auth')->user()->get('operator_id');

        $gotoJob = (new ExportFileJob('pointsmallitems', $authdata['company_id'], $params, $operator_id))->onQueue('slow');
        app('Illuminate\Contracts\Bus\Dispatcher')->dispatch($gotoJob);
        $result['status'] = true;
        return response()->json($result);
    }
}
