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

namespace MembersBundle\Http\Api\V1\Action;

use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\TagsCategoryService;

class TagsCategoryController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/member/tagcategory",
     *     summary="新增会员标签分类",
     *     tags={"会员"},
     *     description="新增会员标签分类",
     *     operationId="createTagsCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_name", in="query", description="标签分类名称", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="relTagIds", in="query", description="标签id集合", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="4", description="标签分类id"),
     *                  @SWG\Property( property="category_name", type="string", example="老客", description="标签分类名称"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="created", type="string", example="1612162331", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612162331", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function createTagsCategory(Request $request)
    {
        $params = $request->all('category_name', 'sort');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $rules = [
            'company_id' => ['required|min:1', '缺少企业id'],
            'category_name' => ['required', '分类名称必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $relLavelIds = $request->get('relTagIds', null);
        $tagsCategoryService = new TagsCategoryService();
        $result = $tagsCategoryService->saveCategory($params, $relLavelIds);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/member/tagcategory/{category_id}",
     *     summary="编辑会员标签分类",
     *     tags={"会员"},
     *     description="编辑会员标签分类",
     *     operationId="updateTagsCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="path", description="分类id", required=true, type="string"),
     *     @SWG\Parameter( name="category_name", in="query", description="标签分类名称", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="relTagIds", in="query", description="标签id集合", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="4", description="标签分类id"),
     *                  @SWG\Property( property="category_name", type="string", example="老客", description="标签分类名称"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="created", type="string", example="1612162331", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612162331", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */

    public function updateTagsCategory($category_id, Request $request)
    {
        $params = $request->all('category_name', 'sort');
        $rules = [
            'category_name' => ['required', '分类名称必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['category_id'] = $category_id;
        $rules = [
            'company_id' => ['required', '缺少企业id'],
            'category_id' => ['required', '分类Id必填'],
        ];
        $errorMessage = validator_params($filter, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $relLavelIds = $request->get('relTagIds', null);
        $tagsCategoryService = new TagsCategoryService();
        $result = $tagsCategoryService->saveCategory($params, $relLavelIds, $filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/tagcategory",
     *     summary="会员标签分类列表",
     *     tags={"会员"},
     *     description="会员标签分类列表",
     *     operationId="getTagsCategoryList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_name", in="query", description="标签分类名称", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="category_name", type="string", example="2222", description="标签分类名称"),
     *                          @SWG\Property( property="category_id", type="string", example="2", description="标签分类id"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTagsCategoryList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', -1);
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $tagsCategoryService = new TagsCategoryService();
        $result = $tagsCategoryService->lists($filter, 'category_name,category_id,sort', $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/tagcategory/{category_id}",
     *     summary="会员标签分类详情",
     *     tags={"会员"},
     *     description="会员标签分类详情",
     *     operationId="getTagsCategoryInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="path", description="分类id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="4", description="标签分类id"),
     *                  @SWG\Property( property="category_name", type="string", example="老客", description="标签分类名称"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="created", type="string", example="1612162331", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612162331", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTagsCategoryInfo($category_id, Request $request)
    {
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['category_id'] = $category_id;
        $tagsCategoryService = new TagsCategoryService();
        $result = $tagsCategoryService->getInfo($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/member/tagcategory/{category_id}",
     *     summary="删除会员标签分类",
     *     tags={"会员"},
     *     description="删除会员标签分类",
     *     operationId="deleteTagsCategory",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="path", description="分类id", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteTagsCategory($category_id)
    {
        $tagsCategoryService = new TagsCategoryService();
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['category_id'] = $category_id;
        $result = $tagsCategoryService->deleteCategory($filter);
        return $this->response->array(['status' => $result]);
    }
}
