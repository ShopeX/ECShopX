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

use App\Http\Controllers\Controller as BaseController;
use GoodsBundle\Services\ItemsAttributesService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use CompanysBundle\Ego\CompanysActivationEgo;

class ItemsAttributes extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/goods/attributes",
     *     summary="新增商品属性",
     *     tags={"商品"},
     *     description="新增商品属性，商品品牌，商品参数，商品单位等",
     *     operationId="addItemsAttributes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="attribute_type", in="query", description="参数类型(unit单位，brand品牌，item_params商品参数, item_spec规格)", required=true, type="string"),
     *     @SWG\Parameter( name="attribute_name", in="query", description="属性名称(单位，品牌，参数名称等)", required=true, type="string"),
     *     @SWG\Parameter( name="image_url", in="query", description="属性图片", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function addItemsAttributes(request $request)
    {
        // ShopEx EcShopX Service Component
        $companyId = app('auth')->user()->get('company_id');

//        $input = $request->all('attribute_type', 'attribute_name', 'attribute_memo', 'attribute_sort', 'is_show', 'image_url', 'is_image');
        $input = $request->all();

        $rules = [
            'attribute_name' => ['required','请填写名称'],
        ];

        $error = validator_params($input, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if ($input['attribute_type'] == 'brand') {
            $input['distributor_id'] = app('auth')->user()->get('distributor_id');
        }

        $input['company_id'] = $companyId;

        if ($request->input('attribute_values')) {
            $input['attribute_values'] = json_decode($request->input('attribute_values'), true);
        }

        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->createAttr($input);

        $multiLang = $input['translation'] ?? [];


        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/goods/attributes/{attribute_id}",
     *     summary="更新商品属性",
     *     tags={"商品"},
     *     description="更新商品属性，商品品牌，商品参数，商品单位等",
     *     operationId="updateItemsAttributes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="attribute_name", in="query", description="属性名称(单位，品牌，参数名称等)", required=true, type="string"),
     *     @SWG\Parameter( name="image_url", in="query", description="属性图片", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function updateItemsAttributes($attribute_id, request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $input = $request->all('attribute_type', 'attribute_name', 'attribute_memo', 'attribute_sort', 'is_show', 'image_url', 'is_image');

        if ($request->input('attribute_values')) {
            $input['attribute_values'] = json_decode($request->input('attribute_values'), true);
        }

        $rules = [
            'attribute_name' => ['required','请填写名称'],
        ];

        $error = validator_params($input, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->updateAttr(['company_id' => $companyId, 'attribute_id' => $attribute_id], $input);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/attributes/{attribute_id}",
     *     summary="删除商品属性",
     *     tags={"商品"},
     *     description="删除商品属性，商品品牌，商品参数，商品单位等",
     *     operationId="deleteItemsAttributes",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteItemsAttributes($attribute_id)
    {
        $companyId = app('auth')->user()->get('company_id');

        $itemsAttributesService = new ItemsAttributesService();
        $itemsAttributesService->deleteAttr(['company_id' => $companyId, 'attribute_id' => $attribute_id]);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/goods/attributes",
     *     summary="获取商品属性列表",
     *     tags={"商品"},
     *     description="获取商品属性列表，商品品牌，商品参数，商品单位等",
     *     operationId="getItemsAttrList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="attribute_type", in="query", description="参数类型(unit单位，brand品牌，item_params商品参数, item_spec规格)", required=true, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页的数量", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="分页页数", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="33", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="attribute_id", type="string", example="1437", description="商品属性id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                          @SWG\Property( property="attribute_type", type="string", example="item_params", description="商品属性类型 unit单位，brand品牌，item_params商品参数, item_spec规格"),
     *                          @SWG\Property( property="attribute_name", type="string", example="11111", description="商品属性名称"),
     *                          @SWG\Property( property="attribute_memo", type="string", example="1111", description="商品属性备注"),
     *                          @SWG\Property( property="attribute_sort", type="string", example="1", description="商品属性排序"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="is_show", type="string", example="true", description="是否显示"),
     *                          @SWG\Property( property="is_image", type="string", example="false", description="属性是否需要配置图片"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="created", type="string", example="1611886972", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611886972", description="修改时间"),
     *                          @SWG\Property( property="attribute_code", type="string", example="null", description="oms 规格编码"),
     *                          @SWG\Property( property="attribute_values", type="object",
     *                                  @SWG\Property( property="total_count", type="string", example="6", description=""),
     *                                  @SWG\Property( property="list", type="array",
     *                                      @SWG\Items( type="object",
     *                                          @SWG\Property( property="attribute_value_id", type="string", example="2393", description="商品属性值id"),
     *                                          @SWG\Property( property="attribute_id", type="string", example="1437", description="商品属性ID"),
     *                                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                                          @SWG\Property( property="attribute_value", type="string", example="1", description=""),
     *                                          @SWG\Property( property="sort", type="string", example="0", description="商品排序"),
     *                                          @SWG\Property( property="image_url", type="string", example="null", description="元素配图"),
     *                                          @SWG\Property( property="created", type="string", example="1611886972", description=""),
     *                                          @SWG\Property( property="updated", type="string", example="1611886972", description="修改时间"),
     *                                          @SWG\Property( property="oms_value_id", type="string", example="null", description="oms商品属性值id"),
     *                                       ),
     *                                  ),
     *                          ),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getItemsAttrList(request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $itemsAttributesService = new ItemsAttributesService();

        $attrType = $request->input('attribute_type');
        $attribute_name = $request->input('attribute_name');

        $params = $request->all('pageSize', 'page');
        $rules = [
            'page' => ['required|integer|min:1','分页参数错误'],
            'pageSize' => ['required|integer|min:1|max:1000','每页最多查询1000条数据'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        if ($attribute_name) {
            $filter['attribute_name|contains'] = $attribute_name;
        }
        $filter['company_id'] = $companyId;
        $filter['attribute_type'] = $attrType;

        if ($request->input('attribute_ids')) {
            $filter['attribute_id'] = $request->input('attribute_ids');
        }

        if ($request->input('attribute_name')) {
            $filter['attribute_name|contains'] = $request->input('attribute_name');
        }

        if ($attrType == 'brand') {
            $company = (new CompanysActivationEgo())->check($filter['company_id']);
            if ($company['product_model'] == 'platform' && $request->input('distributor_id') != 'all') {
                $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
            }
        }

        $lists = $itemsAttributesService->getAttrList($filter, $params['page'], $params['pageSize'], ['created' => 'desc']);

        return $this->response->array($lists);
    }
}
