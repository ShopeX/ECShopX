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

namespace GoodsBundle\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\ApiServices\ItemsCategoryService;
use Illuminate\Http\Request;

class ItemsCategory extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/service/goods/category",
     *     summary="添加分类",
     *     tags={"category"},
     *     description="添加分类",
     *     operationId="createCategory",
     *     @SWG\Parameter(
     *         name="ServiceSign",
     *         in="query",
     *         description="接口签名",
     *         type="string",
     *     ),
     * @SWG\Parameter(
     *      name="body",
     *      in="body",
     *      required=true,
     *      @SWG\Schema(
     *           @SWG\Property(
     *               property="company_id",
     *               description="公司ID",
     *               type="integer",
     *               example=1
     *           ),
     *           @SWG\Property(
     *               property="distributor_id",
     *               description="店铺 ID",
     *               type="integer",
     *               example=1
     *           ),
     *
     *           @SWG\Property(
     *               property="form",
     *               type="array",
     *               @SWG\Items(
     *                    type="object",
     *                    @SWG\Property(
     *                        property="category_id",
     *                        description="分类ID",
     *                        type="integer",
     *                    ),
     *                    @SWG\Property(
     *                        property="category_name",
     *                        description="分类名称",
     *                        type="string",
     *                    ),
     *                    @SWG\Property(
     *                        property="sort",
     *                        description="排序",
     *                        type="integer",
     *                    ),
     *                    @SWG\Property(
     *                        property="image_url",
     *                        description="图片url",
     *                        type="string"
     *                    ),
     *               )
     *           )
     *      )
     * ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
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
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ServiceErrorRespones") ) )
     * )
     */
    public function createCategory(Request $request, ItemsCategoryService $itemsCategoryService)
    {
        $rules = [
            'company_id' => ['required', '公司 ID 必填'],
            'distributor_id' => ['required', '店铺 ID 必填'],
            'form.*.category_id' => ['sometimes|required', 'category_id必须大于0'],
            'form.*.category_name' => ['required', '分类名称必填'],
            'form.*.sort' => ['sometimes', ''],
            'form.*.image_url' => ['sometimes', ''],
        ];
        $params['form'] = $request->input('form');
        $params['company_id'] = $request->input('company_id');
        $params['distributor_id'] = $request->input('distributor_id');
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $result = $itemsCategoryService->saveItemsCategory($params['form'], $params['company_id'], $params['distributor_id']);
        return $this->response->array($result);
    }
    /**
     * @SWG\Delete(
     *     path="/service/goods/category/{category_id}",
     *     summary="删除分类",
     *     tags={"category"},
     *     description="删除分类",
     *     operationId="deleteCategory",
     *     @SWG\Parameter(
     *         name="ServiceSign",
     *         in="query",
     *         description="接口签名",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="formData",
     *         description="公司ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="path",
     *         description="分类ID",
     *         required=true,
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
     *                     @SWG\Property(property="item_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ServiceErrorRespones") ) )
     * )
     */
    public function deleteCategory(Request $request, $category_id)
    {
        $params['category_id'] = $category_id;
        $params['company_id'] = $request->input('company_id');
        $validator = app('validator')->make($params, [
            'category_id' => 'required|integer|min:1',
            'company_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除分类出错.', $validator->errors());
        }
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->deleteItemsCategory($params);

        return $this->response->noContent();
    }
    /**
     * @SWG\Post(
     *     path="/service/goods/category/list",
     *     summary="获取分类列表",
     *     tags={"category"},
     *     description="获取分类列表",
     *     operationId="getCategory",
     *     @SWG\Parameter(
     *         name="ServiceSign",
     *         in="query",
     *         description="接口签名",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *          name="body",
     *          in="body",
     *          required=true,
     *          @SWG\Schema(
     *              @SWG\Property(
     *                  property="filter",
     *                  type="object",
     *                     @SWG\Property(property="company_id", type="integer",example=1),
     *                     @SWG\Property(property="is_main_category", type="boolean",example=false),
     *              ),
     *              @SWG\Property(
     *                  property="order_by",
     *                  type="object",
     *                     @SWG\Property(property="sort", type="string",example="DESC"),
     *                     @SWG\Property(property="created", type="string",example="ASC"),
     *              ),
     *              @SWG\Property(
     *                  property="is_show",
     *                  type="bool",
     *                  example=true
     *              ),
     *              @SWG\Property(
     *                  property="page",
     *                  type="integer",
     *                  example=1
     *              ),
     *              @SWG\Property(
     *                  property="page_size",
     *                  type="integer",
     *                  example=100
     *              )
     *          )
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
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ServiceErrorRespones") ) )
     * )
     */
    public function getCategory(request $request)
    {
        $filter = $request->input('filter');
        $page = $request->input('page', 1);
        $pageSize = $request->input('page_size', 100);
        $orderBy = $request->input('order_by', ["sort" => "DESC", "created" => "ASC"]);
        $isShow = $request->input('is_show', true);
        $columns  = $request->input('columns', '*');
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->getItemsCategory($filter, $isShow, $page, $pageSize, $orderBy, $columns);
        return $this->response->array($result);
    }
    /**
     * @SWG\Get(
     *     path="/service/goods/category/{company_id}/{category_id}",
     *     summary="获取单条分类数据",
     *     tags={"category"},
     *     description="获取单条分类数据",
     *     operationId="getCategoryInfo",
     *     @SWG\Parameter(
     *         name="ServiceSign",
     *         in="query",
     *         description="接口签名",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="path",
     *         description="公司ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="path",
     *         description="分类ID",
     *         required=true,
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
     *                     @SWG\Property(property="category_id", type="string"),
     *                     @SWG\Property(property="company_id", type="string"),
     *                     @SWG\Property(property="category_name", type="string"),
     *                     @SWG\Property(property="parent_id", type="string"),
     *                     @SWG\Property(property="goods_spec", type="string"),
     *                     @SWG\Property(property="goods_params", type="string"),
     *                     @SWG\Property(property="path", type="string"),
     *                     @SWG\Property(property="image_url", type="string"),
     *                     @SWG\Property(property="sort", type="string"),
     *                     @SWG\Property(property="created", type="string"),
     *                     @SWG\Property(property="updated", type="string"),
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ServiceErrorRespones") ) )
     * )
     */
    public function getCategoryInfo(request $request, $company_id, $category_id)
    {
        $filter['category_id'] = $category_id;
        $filter['company_id'] = $company_id;
        $validator = app('validator')->make($filter, [
            'category_id' => 'required|integer|min:1',
            'company_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除分类出错.', $validator->errors());
        }
        $itemsCategoryService = new ItemsCategoryService();
        $result = $itemsCategoryService->getCategoryInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/service/goods/category/{company_id}/{category_id}",
     *     summary="更新单条分类信息",
     *     tags={"商品分类"},
     *     description="更新单条分类信息",
     *     operationId="updateCategory",
     *     @SWG\Parameter(
     *         name="ServiceSign",
     *         in="query",
     *         description="接口签名",
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="company_id",
     *         in="path",
     *         description="公司ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="category_id",
     *         in="path",
     *         description="分类ID",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(in="formData", description="分类名称", name="category_name", type="string"),
     *     @SWG\Parameter(in="formData", description="上级分类", name="parent_id", type="string"),
     *     @SWG\Parameter(in="formData", description="商品规格", name="goods_spec", type="string"),
     *     @SWG\Parameter(in="formData", description="商品参数", name="goods_params", type="string"),
     *     @SWG\Parameter(in="formData", description="路径", name="path", type="string"),
     *     @SWG\Parameter(in="formData", description="图片地址", name="image_url", type="string"),
     *     @SWG\Parameter(in="formData", description="排序", name="sort", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="string")
     *                 )
     *            )
     *        )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/ServiceErrorRespones") ) )
     * )
     */
    public function updateCategory(Request $request, $company_id, $category_id)
    {
        $itemsCategoryService = new ItemsCategoryService();

        $data = $request->input();

        if (isset($data['goods_spec']) && !is_array($data['goods_spec'])) {
            $data['goods_spec'] = json_decode($request->input('goods_spec'), true);
        }

        if (isset($data['goods_params']) && !is_array($data['goods_params'])) {
            $data['goods_params'] = json_decode($request->input('goods_params'), true);
        }

        $info = $itemsCategoryService->getInfo(['category_id' => $category_id, 'company_id' => $company_id]);
        if (!$info) {
            throw new ResourceException('更新的分类不存在');
        }
        if (isset($data['category_name']) && $data['category_name'] != $info['category_name']) {
            $exist = $itemsCategoryService->getInfo(['category_name' => $data['category_name'], 'company_id' => $company_id, 'parent_id' => $info['parent_id'], 'is_main_category' => $info['is_main_category']]);
            if ($exist && $exist['category_id'] != $info['category_id']) {
                throw new ResourceException('分类名称不能重复');
            }
        }

        $result = $itemsCategoryService->updateOneBy(['category_id' => $category_id, 'company_id' => $company_id], $data);

        return $this->response->array($result);
    }
}
