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

namespace CompanysBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use CompanysBundle\Services\ArticleCategoryService;
use Dingo\Api\Exception\ResourceException;

class ArticleCategory extends BaseController
{
    private $articleCategoryService;

    public function __construct(ArticleCategoryService $ArticleCategoryService)
    {
        $this->articleCategoryService = new $ArticleCategoryService();
    }

    /**
     * @SWG\Post(
     *     path="/article/category",
     *     summary="创建文章栏目",
     *     tags={"企业"},
     *     description="创建文章栏目",
     *     operationId="createData",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
    *     @SWG\Parameter( name="form", in="body", description="文章栏目", required=false, type="array",
    *         @SWG\Schema(
    *             @SWG\Items(
    *                 @SWG\Property(
    *                    property="category_id",
    *                    description="栏目ID",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="category_name",
    *                     description="名称",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="parent_id",
    *                     description="父级id",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="category_level",
    *                     description="商品分类等级",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="path",
    *                     description="路径",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="sort",
    *                     description="排序",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="category_type",
    *                     description="栏目类型",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="level",
    *                     description="分类级别",
    *                    type="string"
    *                 ),
    *                 @SWG\Property(
    *                     property="children",
    *                     description="子栏目",
    *                    type="array",
    *                    items="array"
    *                 ),
    *                 @SWG\Property(
    *                     property="link",
    *                     description="栏目链接",
    *                    type="string"
    *                 )
    *             )
    *         )
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
     *                     @SWG\Property(property="category_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="category_name", type="integer", example="1"),
     *                     @SWG\Property(property="parent_id", type="integer", example="1"),
     *                     @SWG\Property(property="category_level", type="string", example="1"),
     *                     @SWG\Property(property="path", type="string", example="1"),
     *                     @SWG\Property(property="sort", type="integer", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function createData(Request $request)
    {
        $params = $request->input();
        $companyId = app('auth')->user()->get('company_id');
        $result = $this->articleCategoryService->saveArticleCategory($params['form'], $companyId);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/article/category/{category_id}",
     *     summary="删除文章栏目",
     *     tags={"企业"},
     *     description="删除文章栏目",
     *     operationId="deleteCategory",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function deleteCategory($category_id)
    {
        $params['category_id'] = $category_id;
        $validator = app('validator')->make($params, [
            'category_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除分类出错.', $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $params = [
            'category_id' => $category_id,
            'company_id' => $company_id,
        ];
        $result = $this->articleCategoryService->deleteArticleCategory($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/article/category",
     *     summary="获取文章栏目列表",
     *     tags={"企业"},
     *     description="获取文章栏目列表",
     *     operationId="getCategory",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter(
     *         name="category_type",
     *         in="query",
     *         description="文章栏目类型",
     *         type="string",
     *         required=true,
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
     *                     @SWG\Property(property="category_id", type="integer", description="栏目ID"),
     *                     @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                     @SWG\Property(property="category_name", type="integer", example="1", description="栏目名"),
     *                     @SWG\Property(property="parent_id", type="integer", example="1", description="父级ID"),
     *                     @SWG\Property(property="category_level", type="string", example="1", description="商品分类等级"),
     *                     @SWG\Property(property="path", type="string", example="15,16,17", description="路径"),
     *                     @SWG\Property(property="sort", type="integer", example="1", description="排序"),
     *                     @SWG\Property(property="created", type="string", example="1", description="创建时间"),
     *                     @SWG\Property(property="updated", type="string", example="1", description="更新时间"),
     *                     @SWG\Property(property="children", type="array", description="更新时间", items="array"),
     *                     @SWG\Property(property="level", type="integer", description="分类级别"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getCategory(request $request)
    {
        $filter['category_type'] = $request->get('category_type', 'bring');
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $result = $this->articleCategoryService->getArticleCategory($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/article/category/{category_id}",
     *     summary="获取单条文章栏目",
     *     tags={"企业"},
     *     description="获取单条文章栏目",
     *     operationId="getCategoryInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string",
     *         required=true,
     *     ),
     *     @SWG\Parameter( name="category_id", in="path", description="分类ID", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="category_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="category_name", type="integer", example="1"),
     *                     @SWG\Property(property="parent_id", type="integer", example="1"),
     *                     @SWG\Property(property="category_level", type="string", example="1"),
     *                     @SWG\Property(property="path", type="string", example="1"),
     *                     @SWG\Property(property="sort", type="integer", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function getCategoryInfo($category_id, request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['category_id'] = $category_id;
        $result = $this->articleCategoryService->getCategoryInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/article/category/{category_id}",
     *     summary="更新单条文章栏目",
     *     tags={"企业"},
     *     description="更新单条文章栏目",
     *     operationId="updateCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="path", description="分类ID", required=true, type="string"),
     *     @SWG\Parameter(in="query", name="category_id",description="栏目ID",type="string"),
    *     @SWG\Parameter(in="query", name="category_name", description="名称",type="string"),
    *     @SWG\Parameter(in="query", name="parent_id", description="父级id",type="string"),
    *     @SWG\Parameter(in="query", name="category_level", description="商品分类等级",type="string"),
    *     @SWG\Parameter(in="query", name="path", description="路径",type="string"),
    *     @SWG\Parameter(in="query", name="sort", description="排序",type="string"),
    *     @SWG\Parameter(in="query", name="category_type", description="栏目类型",type="string"),
    *     @SWG\Parameter(in="query", name="level", description="分类级别",type="string"),
    *     @SWG\Parameter(in="query", name="children", description="子栏目",type="array",items="array"),
    *     @SWG\Parameter(in="query", name="link", description="栏目链接",type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property(property="category_id", type="integer", description="栏目ID"),
     *                 @SWG\Property(property="company_id", type="integer", description="公司ID"),
     *                 @SWG\Property(property="category_name", type="integer", example="1", description="栏目名"),
     *                 @SWG\Property(property="parent_id", type="integer", example="1", description="父级ID"),
     *                 @SWG\Property(property="category_level", type="string", example="1", description="商品分类等级"),
     *                 @SWG\Property(property="path", type="string", example="15,16,17", description="路径"),
     *                 @SWG\Property(property="sort", type="integer", example="1", description="排序"),
     *                 @SWG\Property(property="created", type="string", example="1", description="创建时间"),
     *                 @SWG\Property(property="updated", type="string", example="1", description="更新时间"),
     *                 @SWG\Property(property="children", type="array", description="更新时间", items="array"),
     *                 @SWG\Property(property="level", type="integer", description="分类级别"),
     *            ),
     *        ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CompanysErrorRespones") ) )
     * )
     */
    public function updateCategory($category_id, Request $request)
    {
        $companyId = app('auth')->user()->get('company_id');
        $data = $request->input();
        $result = $this->articleCategoryService->updateOneBy(['category_id' => $category_id, 'company_id' => $companyId], $data);
        return $this->response->array($result);
    }
}
