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
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsAttributesService;
use Illuminate\Http\Request;
use WechatBundle\Entities\WeappCustomizePage;
use CompanysBundle\Ego\CompanysActivationEgo;

class ItemsCategory extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/goods/category",
     *     summary="添加分类",
     *     tags={"商品"},
     *     description="添加分类",
     *     operationId="createCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="category_name", in="formData", description="分类名称", required=true, type="string" ),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=false, type="string" ),
     *     @SWG\Parameter( name="image_url", in="formData", description="图片url", required=false, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function createCategory(Request $request)
    {
        // Powered by ShopEx EcShopX
        $params = $request->input();
        $itemsCategoryService = new ItemsCategoryService();
        $companyId = app('auth')->user()->get('company_id');
        $params['form'] = json_decode($params['form'], true);
        $distributorId = app('auth')->user()->get('distributor_id');
        $result = $itemsCategoryService->saveItemsCategory($params['form'], $companyId, $distributorId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/goods/createcategory",
     *     summary="添加分类",
     *     tags={"商品"},
     *     description="添加分类",
     *     operationId="createClassification",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="category_name",
     *         in="formData",
     *         description="分类名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="parent_id",
     *         in="formData",
     *         description="父级id",
     *         required=false,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="sort",
     *         in="formData",
     *         description="排序",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="image_url",
     *         in="formData",
     *         description="图片url",
     *         required=false,
     *         type="string"
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
     *                     @SWG\Property(property="status", type="bool"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function createClassification(Request $request, ItemsCategoryService $itemsCategoryService)
    {
        $rules = [
            'category_name' => ['required', '分类名称必填'],
            'sort' => ['required|numeric|min:0', '排序必须大于等于0'],
            'parent_id' => ['numeric|min:0', '父级ID必须大于等于0'],
            'image_url' => ['sometimes', ''],
        ];
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = app('auth')->user()->get('distributor_id');
        $params = $request->input();
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = $itemsCategoryService->createClassificationService($params, $companyId, $distributorId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/goods/category/{category_id}",
     *     summary="删除分类",
     *     tags={"商品"},
     *     description="删除分类",
     *     operationId="deleteCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="item_id", in="path", description="商品id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function deleteCategory($category_id)
    {
        $params['category_id'] = $category_id;
        $validator = app('validator')->make($params, [
            'category_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException(trans('GoodsBundle/Controllers/Items.delete_category_error'), $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $itemsCategoryService = new ItemsCategoryService();
        $params = [
            'category_id' => $category_id,
            'company_id' => $company_id,
        ];
        $result = $itemsCategoryService->deleteItemsCategory($params);

        return $this->response->array(['status' => true]);
        // return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/goods/category",
     *     summary="获取分类列表",
     *     tags={"商品"},
     *     description="获取分类列表",
     *     operationId="getCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter(
     *         name="is_main_category",
     *         in="query",
     *         description="是否主类目",
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="category_level",
     *         in="query",
     *         description="分类层级",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="parent_id",
     *         in="query",
     *         description="上级ID",
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="is_show",
     *         in="query",
     *         description="是否显示子类目",
     *         type="boolean"
     *     ),
     *     @SWG\Parameter(
     *         name="ignore_none",
     *         in="query",
     *         description="是否屏蔽为空的分类",
     *         type="boolean"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="id", type="string", example="1", description=""),
     *                  @SWG\Property( property="category_id", type="string", example="1", description="分类id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="category_name", type="string", example="测试分类1", description="分类名称"),
     *                  @SWG\Property( property="label", type="string", example="测试分类1", description="地区名称"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父级id, 0为顶级"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="path", type="string", example="1", description="路径"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="is_main_category", type="string", example="false", description="是否为商品主类目"),
     *                  @SWG\Property( property="goods_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="goods_spec", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="category_level", type="string", example="1", description="等级"),
     *                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="created", type="string", example="1560927547", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1599449617", description="修改时间"),
     *                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                  @SWG\Property( property="children", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="2", description=""),
     *                          @SWG\Property( property="category_id", type="string", example="2", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="category_name", type="string", example="分类1-11111111111111", description="分类名称"),
     *                          @SWG\Property( property="label", type="string", example="分类1-11111111111111", description="地区名称"),
     *                          @SWG\Property( property="parent_id", type="string", example="1", description="父级id, 0为顶级"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="path", type="string", example="1,2", description="路径"),
     *                          @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                          @SWG\Property( property="is_main_category", type="string", example="false", description="是否为商品主类目"),
     *                          @SWG\Property( property="goods_params", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="goods_spec", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="category_level", type="string", example="2", description="等级"),
     *                          @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                          @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                          @SWG\Property( property="created", type="string", example="1560927547", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1599449617", description="修改时间"),
     *                          @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                          @SWG\Property( property="children", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="id", type="string", example="605", description=""),
     *                                  @SWG\Property( property="category_id", type="string", example="605", description="分类id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="category_name", type="string", example="睛", description="分类名称"),
     *                                  @SWG\Property( property="label", type="string", example="睛", description="地区名称"),
     *                                  @SWG\Property( property="parent_id", type="string", example="2", description="父级id, 0为顶级 | 父级id | 父分类id,顶级为0"),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                                  @SWG\Property( property="path", type="string", example="1,2,605", description="路径"),
     *                                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                                  @SWG\Property( property="is_main_category", type="string", example="false", description="是否为商品主类目"),
     *                                  @SWG\Property( property="goods_params", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="goods_spec", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="category_level", type="string", example="3", description="等级"),
     *                                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="created", type="string", example="1574938276", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1599449617", description="修改时间"),
     *                                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *                                  @SWG\Property( property="level", type="string", example="2", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="level", type="string", example="1", description=""),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="level", type="string", example="0", description=""),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getCategory(request $request)
    {
        $itemsCategoryService = new ItemsCategoryService();
        $filter['company_id'] = app('auth')->user()->get('company_id');        
        $filter['is_main_category'] = $request->input('is_main_category', 'false') === 'true' ? 1 : 0;

        if (!is_null($request->input('category_level'))) {
            $filter['category_level'] = $request->input('category_level');
        }

        if (!is_null($request->input('parent_id'))) {
            $filter['parent_id'] = $request->input('parent_id');
        }

        $company = (new CompanysActivationEgo())->check($filter['company_id']);
        if ($company['product_model'] == 'standard') {
            //云店版本，店铺端共用平台的商品销售分类
            $filter['distributor_id'] = 0;
        } else {
            //ecx版本，店铺端有自己的商品销售分类
            $filter['distributor_id'] = $request->input('distributor_id', 0);
        }
        
        $isShow = 'false' == $request->input('is_show', 'true') ? false : true;
        $result = $itemsCategoryService->getItemsCategory($filter, $isShow);
        if ($result) {
            $customize_page_ids = array_unique(array_column($result,'customize_page_id'));
            $customizePages = [];
            if($customize_page_ids){
                $customizePageRepository = app('registry')->getManager('default')->getRepository(WeappCustomizePage::class);
                $customizePages = $customizePageRepository->lists(['id'=>$customize_page_ids]);
                $customizePages = array_column($customizePages['list'],null,'id');
            }
            foreach ($result as $key => $val) {
                $result[$key]['customize_page_name'] = '';
                if(isset($customizePages[$val['customize_page_id']])){
                    $result[$key]['customize_page_name'] = $customizePages[$val['customize_page_id']]['page_name'];
                }
                $result[$key]['sort'] = intval($val['sort']);
            }

            // ECX-925 仅一级分类按时间倒序，默认已经按 sort 倒序
            $newCategory = [];
            foreach ($result as $v) {
                $newCategory[$v['sort']][] = $v;
            }
            $result = [];
            foreach ($newCategory as $category) {
                $created = array_column($category, 'created');
                array_multisort($created, SORT_DESC, SORT_NUMERIC, $category);
                $result = array_merge($result, $category);
            }
        }
        if ($request->input('ignore_none', false)) {
            $result = $itemsCategoryService->removeNoneCategories($result);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/goods/category/{category_id}",
     *     summary="获取单条分类数据",
     *     tags={"商品"},
     *     description="获取单条分类数据",
     *     operationId="getCategoryInfo",
     *     @SWG\Parameter( name="Authorization",  in="header", description="JWT验证token",  type="string" ),
     *     @SWG\Parameter( name="category_id", in="path", description="分类ID", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="id", type="string", example="469", description=""),
     *                  @SWG\Property( property="category_id", type="string", example="469", description="分类id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="category_name", type="string", example="服装", description="分类名称"),
     *                  @SWG\Property( property="label", type="string", example="服装", description="地区名称"),
     *                  @SWG\Property( property="parent_id", type="string", example="0", description="父级id, 0为顶级"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                  @SWG\Property( property="path", type="string", example="469", description="路径"),
     *                  @SWG\Property( property="sort", type="string", example="0", description="排序"),
     *                  @SWG\Property( property="is_main_category", type="string", example="false", description="是否为商品主类目"),
     *                  @SWG\Property( property="goods_params", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="goods_spec", type="array",
     *                      @SWG\Items( type="string", example="undefined", description=""),
     *                  ),
     *                  @SWG\Property( property="category_level", type="string", example="1", description="等级"),
     *                  @SWG\Property( property="image_url", type="string", example="", description="元素配图"),
     *                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                  @SWG\Property( property="created", type="string", example="1571207286", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1599449617", description="修改时间"),
     *                  @SWG\Property( property="category_code", type="string", example="null", description="分类编码"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function getCategoryInfo($category_id, request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $itemsCategoryService = new ItemsCategoryService();

        $filter['category_id'] = $category_id;

        $result = $itemsCategoryService->getCategoryInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/goods/category/{category_id}",
     *     summary="更新单条分类信息",
     *     tags={"商品"},
     *     description="更新单条分类信息",
     *     operationId="updateCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="path", description="分类ID", required=true, type="string"),
     *     @SWG\Parameter( name="category_name", in="formData", description="分类名称", required=false, type="string"),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=false, type="string"),
     *     @SWG\Parameter( name="image_url", in="formData", description="图片url", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/GoodsErrorRespones") ) )
     * )
     */
    public function updateCategory($category_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');

        $itemsCategoryService = new ItemsCategoryService();
        $itemsAttributesService = new ItemsAttributesService();

        $data = $request->input();

        $category = $itemsCategoryService->getCategoryInfo(['category_id' => $category_id, 'company_id' => $companyId]);

        if ($request->input('goods_spec')) {
            $data['goods_spec'] = json_decode($request->input('goods_spec'), true);
            $deleteAttributeId = array_diff(array_column($category['goods_spec'], 'attribute_id'), $data['goods_spec']);
            if ($deleteAttributeId) {
                $itemsAttributesService->checkDeleteCategoryAttr($companyId, $category_id, $deleteAttributeId);
            }
        }

        if ($request->input('goods_params')) {
            $data['goods_params'] = json_decode($request->input('goods_params'), true);
            $deleteAttributeId = array_diff(array_column($category['goods_params'], 'attribute_id'), $data['goods_params']);
            if ($deleteAttributeId) {
                $itemsAttributesService->checkDeleteCategoryAttr($companyId, $category_id, $deleteAttributeId);
            }
        }

        $result = $itemsCategoryService->updateOneBy(['category_id' => $category_id, 'company_id' => $companyId], $data);

        return $this->response->array($result);
    }
}
