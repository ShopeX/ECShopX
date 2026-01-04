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

namespace OpenapiBundle\Http\ThirdApi\V2\Action\Member;

use Illuminate\Http\Request;
use OpenapiBundle\Http\Controllers\Controller as Controller;
use OpenapiBundle\Exceptions\ErrorException;
use OpenapiBundle\Constants\ErrorCode;

use OpenapiBundle\Services\MemberTagService as OpenapiMemberTagService;

class MemberTagCategory extends Controller
{
    /**
     * @SWG\Post(
     *     path="/ecx.member.tagcategory.add",
     *     summary="新增标签分类",
     *     tags={"会员标签"},
     *     description="新增会员标签分类",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagcategory.add" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_name", description="分类名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sort", description="分类排序 默认:1" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="5", description="标签分类id"),
     *                  @SWG\Property( property="category_name", type="string", example="促销", description="标签分类名称"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="2021-06-28 15:28:14", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-06-28 15:28:14", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function createTagCategory(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('category_name', 'sort');

        $rules = [
            'category_name' => ['required', '分类名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['sort'] = $params['sort'] ?? '1';
        $params['sort'] = intval($params['sort']);
        $params['company_id'] = $companyId;

        $openapiMemberTagService = new OpenapiMemberTagService();
        $return = $openapiMemberTagService->createCategory($params);

        return $this->response->array($return);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.member.tagcategory.delete",
     *     summary="删除标签分类",
     *     tags={"会员标签"},
     *     description="根据标签分类ID，删除已创建的标签分类",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagcategory.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_id", description="分类id" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="boolean", example=true),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function deleteTagCategory(Request $request)
    {
        $params = $request->all('category_id');

        $rules = [
            'category_id' => ['required', '分类ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        $openapiMemberTagService = new OpenapiMemberTagService();
        $filter = [
            'company_id' => $companyId,
            'category_id' => $params['category_id'],
        ];
        $openapiMemberTagService->deleteCategory($filter);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tagcategory.update",
     *     summary="修改标签分类",
     *     tags={"会员标签"},
     *     description="根据标签分类ID，修改标签分类相关信息",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagcategory.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_id", description="分类id" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="category_name", description="分类名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="sort", description="排序" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="category_id", type="string", example="5", description="标签分类id"),
     *                  @SWG\Property( property="category_name", type="string", example="test啊", description="标签分类名称"),
     *                  @SWG\Property( property="sort", type="string", example="1", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="2021-06-28 15:28:14", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-06-28 15:28:14", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateTagCategory(Request $request)
    {
        $params = $request->all('category_id', 'category_name', 'sort');

        $rules = [
            'category_id' => ['required', '分类ID必填'],
            'category_name' => ['required', '分类名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['company_id'] = $request->get('auth')['company_id'];
        $openapiMemberTagService = new OpenapiMemberTagService();
        $return = $openapiMemberTagService->updateCategory($params);

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.tagcategorys.get",
     *     summary="查询标签分类列表",
     *     tags={"会员标签"},
     *     description="查询或筛选（标签分类名称）标签分类列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagcategorys.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_name", description="分类名称" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="category_id", type="string", example="2", description="标签分类ID"),
     *                          @SWG\Property( property="category_name", type="string", example="2222", description="标签分类名称"),
     *                          @SWG\Property( property="sort", type="string", example="1", description="标签分类排序"),
     *                          @SWG\Property( property="created", type="string", example="2020-05-22 11:19:35", description="标签分类创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2020-05-22 11:19:35", description="标签分类更新时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getTagCategoryList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'category_name');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['company_id'] = $request->get('auth')['company_id'];
        $params['page_size'] = $this->getPageSize();

        $openapiMemberTagService = new OpenapiMemberTagService();
        $return = $openapiMemberTagService->getCategoryList($params);

        return $this->response->array($return);
    }
}
