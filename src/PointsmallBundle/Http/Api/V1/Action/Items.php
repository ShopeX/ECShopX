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

use App\Http\Controllers\Controller as BaseController;
use GoodsBundle\Services\ItemsCategoryService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use PointsmallBundle\Services\ItemsService;
use Illuminate\Validation\Rule;
use PointsmallBundle\Services\ItemStoreService;
use PointsmallBundle\Services\ItemsRelCatsService;
use GoodsBundle\Services\ItemsAttributesService;

class Items extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/items",
     *     summary="添加商品",
     *     tags={"积分商城"},
     *     description="添加积分商城商品",
     *     operationId="createItems",
     *     @SWG\Parameter(ref="#/parameters/Authorization"),
     *     @SWG\Parameter( name="item_type", in="formData", description="商品类型 normal:实体商品 默认:normal", required=true, type="string" ),
     *     @SWG\Parameter( name="item_name", in="formData", description="商品名称", required=true, type="string" ),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=false, type="integer" ),
     *     @SWG\Parameter( name="brand_id", in="formData", description="品牌Id", required=true, type="string"),
     *     @SWG\Parameter( name="templates_id", in="formData", description="运费模板Id", required=true, type="string" ),
     *     @SWG\Parameter( name="pics", in="formData", description="图片地址 数组", required=true, type="string" ),
     *     @SWG\Parameter( name="item_category", in="formData", description="商品分类", required=true, type="string" ),
     *     @SWG\Parameter( name="item_main_cat_id", in="formData", description="主类目ID", required=true, type="string" ),
     *     @SWG\Parameter( name="videos", in="formData", description="视频封面", type="string" ),
     *     @SWG\Parameter( name="videos_url", in="formData", description="视频封面", type="string" ),
     *     @SWG\Parameter( name="intro", in="formData", description="图文详情", required=false, type="string" ),
     *     @SWG\Parameter( name="brief", in="formData", description="简介", required=true, type="string" ),
     *     @SWG\Parameter( name="is_show_specimg", in="formData", description="是否显示规格图片", type="boolean" ),
     *     @SWG\Parameter( name="nospec", in="formData", description="是否没有规格 true:单规格 false:多规格", type="boolean" ),
     *     @SWG\Parameter( name="approve_status", in="formData", description="上架状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="store", in="formData", description="库存 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="market_price", in="formData", description="原价 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="cost_price", in="formData", description="成本价 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="point", in="formData", description="积分价格 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="weight", in="formData", description="重量 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="volume", in="formData", description="体积 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="item_bn", in="formData", description="商品编号 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="barcode", in="formData", description="条形码 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="spec_images", in="formData", description="规格图片 json 字段包含：spec_value_id,item_spec,item_image_url", required=false, type="string"),
     *     @SWG\Parameter( name="spec_items", in="formData", description="规格数据 nospec为false时必填 json 包含字段:item_id,price,store,cost_price,item_bn,barcode,market_price,point,approve_status,is_default,weight,volume,item_spec(包含字段：spec_id,spec_value_id,spec_value_name,spec_custom_value_name)", required=false, type="string"
     *     ),
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
    public function createItems(Request $request)
    {
        // NOTE: important business logic
        $params = $request->input();
        $params['origincountry_id'] = $request->input('origincountry_id', 0);

        $rules = [
            'consume_type' => ['in:every,all,notconsume', '核销类型参数不正确'],
            'item_name' => ['required', '商品名称必填'],
            'pics' => ['required', '请上传商品图片'],
            'sort' => ['required|integer', '排序值必须为整数'],
        ];

        if (isset($params['item_type']) && $params['item_type'] == 'normal') {
            $rules['templates_id'] = ['required', '运费模板必填'];
            $rules['brand_id'] = ['required', '请选择品牌'];
            $rules['item_category'] = ['required', '请选择商品分类'];
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $itemsService = new ItemsService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        if (isset($params['item_id'])) {
            unset($params['item_id']);
        }

        $result = $itemsService->addItems($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Put(
     *     path="/goods/items/{item_id}",
     *     summary="更新商品",
     *     tags={"积分商城"},
     *     description="更新商品",
     *     operationId="updateItems",
     *     @SWG\Parameter(ref="#/parameters/Authorization"),
     *     @SWG\Parameter( name="item_type", in="formData", description="商品类型 normal:实体商品 默认:normal", required=true, type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Parameter( name="item_name", in="formData", description="商品名称", required=true, type="string" ),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=false, type="integer" ),
     *     @SWG\Parameter( name="brand_id", in="formData", description="品牌Id", required=true, type="string"),
     *     @SWG\Parameter( name="templates_id", in="formData", description="运费模板Id", required=true, type="string" ),
     *     @SWG\Parameter( name="pics", in="formData", description="图片地址 数组", required=true, type="string" ),
     *     @SWG\Parameter( name="item_category", in="formData", description="商品分类", required=true, type="string" ),
     *     @SWG\Parameter( name="item_main_cat_id", in="formData", description="主类目ID", required=true, type="string" ),
     *     @SWG\Parameter( name="videos", in="formData", description="视频封面", type="string" ),
     *     @SWG\Parameter( name="videos_url", in="formData", description="视频封面", type="string" ),
     *     @SWG\Parameter( name="intro", in="formData", description="图文详情", required=false, type="string" ),
     *     @SWG\Parameter( name="brief", in="formData", description="简介", required=true, type="string" ),
     *     @SWG\Parameter( name="is_show_specimg", in="formData", description="是否显示规格图片", type="boolean" ),
     *     @SWG\Parameter( name="nospec", in="formData", description="是否没有规格 true:单规格 false:多规格", type="boolean" ),
     *     @SWG\Parameter( name="approve_status", in="formData", description="上架状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="store", in="formData", description="库存 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="market_price", in="formData", description="原价 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="cost_price", in="formData", description="成本价 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="point", in="formData", description="积分价格 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="weight", in="formData", description="重量 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="volume", in="formData", description="体积 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="item_bn", in="formData", description="商品编号 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="barcode", in="formData", description="条形码 nospec为true时必填", required=false, type="string" ),
     *     @SWG\Parameter( name="spec_images", in="formData", description="规格图片 json 字段包含：spec_value_id,item_spec,item_image_url", required=false, type="string"),
     *     @SWG\Parameter( name="spec_items", in="formData", description="规格数据 nospec为false时必填 json 包含字段:item_id,price,store,cost_price,item_bn,barcode,market_price,point,approve_status,is_default,weight,volume,item_spec(包含字段：spec_id,spec_value_id,spec_value_name,spec_custom_value_name)", required=false, type="string"
     *     ),
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
    public function updateItems($item_id, Request $request)
    {
        $params = $request->input();
        $params['item_id'] = $item_id;
        $params['origincountry_id'] = $request->input('origincountry_id', 0);
        $rules = [
            'item_id' => ['required|integer|min:1', '请确认您所编辑的商品是否存在'],
            'consume_type' => ['in:every,all,notconsume', '核销类型参数不正确'],
            'item_name' => ['required', '商品名称必填'],
            'sort' => ['required|integer', '排序值必须为整数'],
            'pics' => ['required', '请上传商品图片'],
        ];

        if (isset($params['item_type']) && $params['item_type'] == 'normal') {
            $rules['templates_id'] = ['required', '运费模板必填'];
            $rules['brand_id'] = ['required', '请选择品牌'];
            $rules['item_category'] = ['required', '请选择商品分类'];
        }

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $itemsService = new ItemsService();
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['authorizer_appid'] = app('auth')->user()->get('authorizer_appid');

        $result = $itemsService->addItems($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/items/{item_id}",
     *     summary="删除商品",
     *     tags={"积分商城"},
     *     description="删除商品",
     *     operationId="deleteItems",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="path",
     *         description="商品id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteItems($item_id, Request $request)
    {
        // NOTE: important business logic
        $params['item_id'] = $item_id;
        $validator = app('validator')->make($params, [
            'item_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除商品出错.', $validator->errors());
        }
        $company_id = app('auth')->user()->get('company_id');
        $itemsService = new ItemsService();
        $params = [
            'item_id' => $item_id,
            'company_id' => $company_id,
        ];
        $result = $itemsService->deleteItems($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/pointsmall/goods/items/{item_id}",
     *     summary="获取商品详情",
     *     tags={"积分商城"},
     *     description="获取商品详情",
     *     operationId="getItemsDetail",
     *     @SWG\Parameter( in="header", type="string", required=true, name="Authorization", description="JWT验证token" ),
     *     @SWG\Parameter( in="path", type="integer", required=true, name="item_id", description="商品id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="item_id", type="string", example="33", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_type", type="string", example="normal", description="自行更改字段描述"),
     *                  @SWG\Property( property="consume_type", type="string", example="every", description="自行更改字段描述"),
     *                  @SWG\Property( property="is_show_specimg", type="string", example="true", description="自行更改字段描述"),
     *                  @SWG\Property( property="store", type="string", example="7", description="自行更改字段描述"),
     *                  @SWG\Property( property="barcode", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="sales", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="approve_status", type="string", example="instock", description="自行更改字段描述"),
     *                  @SWG\Property( property="cost_price", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="point", type="string", example="10", description="自行更改字段描述"),
     *                  @SWG\Property( property="goods_id", type="string", example="33", description="自行更改字段描述"),
     *                  @SWG\Property( property="brand_id", type="string", example="3", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_name", type="string", example="11111", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_unit", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_bn", type="string", example="S600653BC3D31A", description="自行更改字段描述"),
     *                  @SWG\Property( property="brief", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="price", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="market_price", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="special_type", type="string", example="normal", description="自行更改字段描述"),
     *                  @SWG\Property( property="goods_function", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="goods_series", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="volume", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="goods_color", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="goods_brand", type="string", example="test2", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_address_province", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_address_city", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="regions_id", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="brand_logo", type="string",),
     *                  @SWG\Property( property="sort", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="templates_id", type="string", example="101", description="自行更改字段描述"),
     *                  @SWG\Property( property="is_default", type="string", example="true", description="自行更改字段描述"),
     *                  @SWG\Property( property="nospec", type="string", example="false", description="自行更改字段描述"),
     *                  @SWG\Property( property="default_item_id", type="string", example="33", description="自行更改字段描述"),
     *                  @SWG\Property( property="pics", type="string", description="图片url数组"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="enable_agreement", type="string", example="false", description="自行更改字段描述"),
     *                  @SWG\Property( property="date_type", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_category", type="string", description="分类id数组"
     *                  ),
     *                  @SWG\Property( property="weight", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="begin_date", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="end_date", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="fixed_term", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="tax_rate", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="created", type="string", example="1611027388", description="自行更改字段描述"),
     *                  @SWG\Property( property="updated", type="string", example="1611207705", description="自行更改字段描述"),
     *                  @SWG\Property( property="video_type", type="string", example="local", description="自行更改字段描述"),
     *                  @SWG\Property( property="videos", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="video_pic_url", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="purchase_agreement", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="intro", type="string", description="详情描述"),
     *                  @SWG\Property( property="audit_status", type="string", example="approved", description="自行更改字段描述"),
     *                  @SWG\Property( property="audit_reason", type="string", example="null", description="自行更改字段描述"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="origincountry_id", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="type", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="itemId", type="string", example="33", description="自行更改字段描述"),
     *                  @SWG\Property( property="consumeType", type="string", example="every", description="自行更改字段描述"),
     *                  @SWG\Property( property="itemName", type="string", example="11111", description="自行更改字段描述"),
     *                  @SWG\Property( property="itemBn", type="string", example="S600653BC3D31A", description="自行更改字段描述"),
     *                  @SWG\Property( property="companyId", type="string", example="1", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_main_cat_id", type="string", example="9", description="自行更改字段描述"),
     *                  @SWG\Property( property="type_labels", type="string",
     *                  ),
     *                  @SWG\Property( property="spec_pics", type="string", description="规格图片url数组"),
     *                  @SWG\Property( property="item_params", type="string", description="参数数组"),
     *                  @SWG\Property( property="item_spec_desc", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ItemSpecDesc",
     *                       ),
     *                  ),
     *                  @SWG\Property( property="spec_images", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/SpecValues",
     *                       ),
     *                  ),
     *                  @SWG\Property( property="spec_items", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/SpecItems",
     *
     *                       ),
     *                  ),
     *                  @SWG\Property( property="item_total_store", type="string", example="14", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_category_main", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ItemCategory",
     *                       ),
     *                  ),
     *                  @SWG\Property( property="videos_url", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="distributor_sale_status", type="string", example="false", description="自行更改字段描述"),
     *                  @SWG\Property( property="origincountry_name", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="origincountry_img_url", type="string", example="", description="自行更改字段描述"),
     *                  @SWG\Property( property="cross_border_tax", type="string", example="0", description="自行更改字段描述"),
     *                  @SWG\Property( property="item_params_list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ItemParams",
     *                       ),
     *                  ),
     *                  @SWG\Property( property="item_spec_list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ItemSpec",
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones")))
     * )
     */
    public function getItemsDetail($item_id, Request $request)
    {
        $company_id = app('auth')->user()->get('company_id');

        $validator = app('validator')->make(['item_id' => $item_id], [
            'item_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取商品详情出错.', $validator->errors());
        }
        $authorizer_appid = app('auth')->user()->get('authorizer_appid');
        $itemsService = new ItemsService();
        $result = $itemsService->getItemsDetail($item_id, $authorizer_appid, [], $company_id);

        if ($company_id != $result['company_id']) {
            throw new ResourceException('获取商品信息有误，请确认商品ID.');
        }

        $result['item_params_list'] = [];
        if (isset($result['attribute_ids']) && $result['attribute_ids']) {
            $itemsAttributesService = new ItemsAttributesService();
            $attrList = $itemsAttributesService->getAttrList(array('attribute_id' => $result['attribute_ids']), 1, 100);
            foreach ($attrList['list'] as $row) {
                if ($row['attribute_type'] == 'item_params') {
                    $result['item_params_list'][] = $row;
                } else {
                    foreach ($row['attribute_values']['list'] as &$attrVal) {
                        $attrVal['custom_attribute_value'] = $result['attr_values_custom'][$attrVal['attribute_value_id']] ?? null;
                    }
                    $result['item_spec_list'][] = $row;
                }
            }
            unset($result['attribute_ids']);
            unset($result['attr_values_custom']);
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/warning_store",
     *     summary="设置商品预警库存(暂时弃用)",
     *     tags={"积分商城"},
     *     description="设置商品预警库存",
     *     operationId="setItemWarningStore",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="store",
     *         in="query",
     *         description="预警库存数量",
     *         type="integer"
     *     ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function setItemWarningStore(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'store' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('预警库存最少为1');
        }

        $companyId = app('auth')->user()->get('company_id');
        $itemStoreService = new ItemStoreService();
        $itemStoreService->setWarningStore($companyId, $inputData['store']);

        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/setItemsTemplate",
     *     summary="更新商品运费模板",
     *     tags={"积分商城"},
     *     description="更新商品运费模板",
     *     operationId="setItemsTemplate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="templates_id",
     *         in="formData",
     *         description="运费模板id",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="formData",
     *         description="商品id数组",
     *         required=true,
     *         type="string"
     *     ),
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
    public function setItemsTemplate(request $request)
    {
        $inputData = $request->all('templates_id', 'item_id');
        $validator = app('validator')->make($inputData, [
            'templates_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('请选择运费模板');
        }
        $companyId = app('auth')->user()->get('company_id');
        $params['templates_id'] = $inputData['templates_id'];
        $params['company_id'] = $companyId;
        $itemsService = new ItemsService();
        $params['item_id'] = $inputData['item_id'];
        $itemsService->setItemsTemplate($params);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/setItemsSort",
     *     summary="更新商品排序",
     *     tags={"积分商城"},
     *     description="更新商品排序",
     *     operationId="setItemsSort",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="formData",
     *         description="排序编号",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="formData",
     *         description="商品id",
     *         required=true,
     *         type="string"
     *     ),
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
    public function setItemsSort(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'sort' => 'required|integer|min:0',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('请填写排序编号');
        }
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $itemsService = new ItemsService();
        $params['item_id'] = $inputData['item_id'];
        $itemsService->setItemsSort($params, $inputData['sort']);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/goods/itemsupdate",
     *     summary="修改商品价格、库存、上下架状态",
     *     tags={"积分商城"},
     *     description="修改商品价格、库存、上下架状态(暂时废弃)",
     *     operationId="updateItemsPriceStoreStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=true, type="string"),
     *     @SWG\Parameter( name="price", in="query", description="商品价格", required=false, type="string"),
     *     @SWG\Parameter( name="store", in="query", description="商品库存", required=false, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="商品上下架状态", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function updateItemsPriceStoreStatus(Request $request)
    {
        $params = [];
        if ($request->input('price', null) !== null) {
            $params['price'] = $request->input('price');
            if (!is_numeric($params['price'])) {
                throw new ResourceException('商品价格格式有误');
            }
            $params['price'] = 100 * $params['price'];
        }
        if ($request->input('store', null) !== null) {
            $params['store'] = (int)$request->input('store');
        }
        if ($request->input('rebate', null) !== null) {
            $params['rebate'] = (int)$request->input('rebate');
            if ($params['rebate'] === 1 && app('auth')->user()->get('operator_type') == 'distributor') {
                $params['rebate'] = 2;
            }
        }
        if ($request->input('rebate_type', null) !== null) {
            $params['rebate_type'] = $request->input('rebate_type');
        }
        if ($request->input('status', null) !== null) {
            $params['approve_status'] = $request->input('status');
        }
        if (empty($params)) {
            throw new ResourceException('参数有误');
        }

        $filter = [];
        if ($request->input('goods_id', null)) {
            $filter['goods_id'] = $request->input('goods_id', null);
        } elseif ($request->input('item_id')) {
            $filter['item_id'] = $request->input('item_id');
        }

        if (empty($filter)) {
            throw new ResourceException('未指定商品');
        }
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $itemsService = new ItemsService();
        $itemsService->updateBy($filter, $params);

        return $this->response->array(['status' => true]);
    }


    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/setItemsCategory",
     *     summary="更新商品分类",
     *     tags={"积分商城"},
     *     description="更新商品分类",
     *     operationId="setItemsCategory",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="formData",
     *         description="商品分类id数组",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="item_id",
     *         in="formData",
     *         description="商品id数组",
     *         required=true,
     *         type="string"
     *     ),
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
    public function setItemsCategory(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'item_id' => 'required',
            'category_id' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('请选择商品和分类的数据');
        }
        $companyId = app('auth')->user()->get('company_id');
        if (!is_array($inputData['category_id']) || is_numeric($inputData['category_id'])) {
            $inputData['category_id'] = [$inputData['category_id']];
        }
        if (!is_array($inputData['item_id']) || is_numeric($inputData['item_id'])) {
            $inputData['item_id'] = [$inputData['item_id']];
        }
        $itemsService = new ItemsRelCatsService();
        $result = $itemsService->setItemsCategory($companyId, $inputData['item_id'], $inputData['category_id']);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/pointsmall/goods/items",
     *     summary="获取商品列表",
     *     tags={"积分商城"},
     *     description="获取商品列表",
     *     operationId="getItemsList",
     *     @SWG\Parameter(ref="#/parameters/Authorization"),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="page", description="当前页面,获取商品列表的初始偏移位置，从1开始计数" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="pageSize", description="每页数量,最大不能超过50" ),
     *     @SWG\Parameter(name="keywords", in="query", description="商品关键词", required=false, type="string"),
     *     @SWG\Parameter(name="templates_id", in="query", description="运费模板id", required=false, type="integer"),
     *     @SWG\Parameter(name="regions_id", in="query", description="产地省市区id,数组", required=false, type="string"),
     *     @SWG\Parameter(name="nospec", in="query", description="是否为单规格", required=false, type="boolean"),
     *     @SWG\Parameter(name="approve_status", in="query", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show:前台仅展示", required=false, type="string"),
     *     @SWG\Parameter(name="item_id", in="query", description="商品id数组", required=false, type="string"),
     *     @SWG\Parameter(name="main_cat_id", in="query", description="主类目id", required=false, type="integer"),
     *     @SWG\Parameter(name="category", in="query", description="分类id", required=false, type="integer"),
     *     @SWG\Parameter(name="brand_id", in="query", description="品牌id", required=false, type="integer"),
     *     @SWG\Parameter(name="item_bn", in="query", description="商品编码", required=false, type="string"),
     *     @SWG\Parameter(name="item_type", in="query", description="商品类型，services：服务商品，normal: 普通商品;暂时只支持normal", required=false, type="string"),
     *     @SWG\Parameter(name="store_gt", in="query", description="库存大于", required=false, type="integer"),
     *     @SWG\Parameter(name="store_lt", in="query", description="库存小于", required=false, type="integer"),
     *     @SWG\Parameter(name="price_gt", in="query", description="积分价格大于", required=false, type="integer"),
     *     @SWG\Parameter(name="price_lt", in="query", description="积分价格小于", required=false, type="integer"),
     *     @SWG\Parameter(name="is_sku", in="query", description="是否要查询sku", required=false, type="boolean"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="24", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/ItemList"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones")))
     * )
     */
    public function getItemsList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取商品列表出错.', $validator->errors());
        }

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
                return $this->response->array($result);
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

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $page = intval($inputData['page']);
        $pageSize = intval($inputData['pageSize']);
        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $isGetSkuList = true;
        } else {
            $isGetSkuList = false;
            $params['is_default'] = true;
        }

        if ($isGetSkuList) {
            if (isset($params['item_id']) && $params['item_id']) {
                $params['default_item_id'] = $params['item_id'];
                unset($params['item_id']);
                $pageSize = -1;
            }
            $result = $itemsService->getSkuItemsList($params, $page, $pageSize);
        } else {
            $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
            $result = $itemsService->getItemsList($params, $page, $pageSize);
        }

        if ($result['list']) {
            $itemsCategoryService = new ItemsCategoryService();
            foreach ($result['list'] as &$value) {
                $categoryInfo = $itemsCategoryService->getInfoById($value['item_main_cat_id']);
                $value['itemMainCatName'] = $categoryInfo['category_name'] ?? '';

                $cat_arr = [];
                foreach (($value['item_cat_id'] ?? []) as &$v) {
                    $cat_info = $itemsCategoryService->getInfoById($v);
                    if ($cat_info) {
                        $cat_arr[] = '[' . $cat_info['category_name'] . ']';
                    }
                }
                $value['itemCatName'] = $cat_arr;
            }
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/itemstoreupdate",
     *     summary="修改商品库存",
     *     tags={"积分商城"},
     *     description="批量修改商品库存",
     *     operationId="batchUpdateItemStore",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter(
     *         name="items",
     *         in="body",
     *         description="商品id",
     *         required=true,
     *         type="array",
     *         @SWG\schema(
     *             required={"item_id", "store"},
     *             @SWG\Items(
     *                 @SWG\Property(property="item_id", description="商品id", type="integer", example="1"),
     *                 @SWG\Property(property="store", description="库存", type="integer", example=10),
     *                 @SWG\Property(property="is_default", description="是否为默认商品", type="boolean", example=true),
     *             )
     *         )
     *         ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="integer"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function batchUpdateItemStore(Request $request)
    {
        if (!$request->get('items')) {
            throw new ResourceException('未指定商品');
        }
        $companyId = app('auth')->user()->get('company_id');
        $params = $request->get('items');
        if (!is_array($params)) {
            $params = json_decode($params, true);
        }
        $rules = [
            '*.item_id' => ['required', '商品id必填'],
            '*.store' => ['required|integer|min:0|max:999999999', '库存为0-999999999的整数'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $itemsService = new ItemsService();
        $result = $itemsService->updateItemsStore($companyId, $params);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/pointsmall/goods/itemstatusupdate",
     *     summary="修改商品状态",
     *     tags={"积分商城"},
     *     description="批量修改商品状态",
     *     operationId="batchUpdateItemStatus",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="items", in="formData", description="商品id数组，包含字段:goods_id", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="formData", description="状态 onsale:上架 instock:下架", required=true, type="string"),
     *
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="status", type="integer"),
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构")
     * )
     */
    public function batchUpdateItemStatus(Request $request)
    {
        if (!$request->get('items')) {
            throw new ResourceException('未指定商品');
        }
        $companyId = app('auth')->user()->get('company_id');
        $input_data = $request->input();
        $items = $input_data['items'];

        if (!is_array($items)) {
            $items = json_decode($items, true);
        }
        $rules = [
            'items.*.goods_id' => ['required', '商品id必填'],
            'status' => ['required|' . Rule::in(['onsale', 'instock']), '状态必填,且必须是 onsale 或 instock '],
        ];
        $errorMessage = validator_params($input_data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $itemsService = new ItemsService();
        $result = $itemsService->updateItemsStatus($companyId, $items, $input_data['status']);
        return $this->response->array(['status' => true]);
    }
}
