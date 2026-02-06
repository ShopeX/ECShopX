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
use Dingo\Api\Exception\StoreResourceFailedException;
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\MemberTagsService;

class MemberTags extends Controller
{
    public $memberTagService;
    public $limit;

    public function __construct()
    {
        $this->memberTagService = new MemberTagsService();
        $this->limit = 20;
    }

    /**
     * @SWG\Post(
     *     path="/member/tag",
     *     summary="新增会员标签",
     *     tags={"会员"},
     *     description="新增会员标签",
     *     operationId="createTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="category_id", in="query", description="标签分类id", required=false, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="query", description="标签颜色", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="query", description="标签文字颜色", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/MemberTags"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */

    public function createTags(Request $request)
    {
        $params = $request->all('category_id', 'tag_name', 'description', 'tag_color', 'font_color', 'group_id');

        $rules = [
            'tag_name' => ['required', '标签名称不能为空'],
            // 'tag_color' => ['required', '标签颜色'],
            // 'font_color' => ['required', '标签字体颜色'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['distributor_id'] = app('auth')->user()->get('distributor_id');

        $filter = [
            'company_id' => $companyId,
            'distributor_id' => $params['distributor_id'],
            'tag_name' => $params['tag_name'],
        ];
        $tag = $this->memberTagService->getInfo($filter);
        if ($tag) {
            throw new StoreResourceFailedException('标签名称不能重复');
        }

        try {
            $result = $this->memberTagService->createTagWithGroup($params);
        } catch (\Exception $e) {
            throw new StoreResourceFailedException($e->getMessage());
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/member/tag-group",
     *     summary="新增标签组并关联标签",
     *     tags={"会员"},
     *     description="新增标签组，首次创建会自动生成默认组并关联现有标签",
     *     operationId="createTagGroup",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="group_name", in="query", description="标签组名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签组描述", required=false, type="string"),
     *     @SWG\Parameter( name="tag_ids", in="query", description="要关联的标签ID数组", required=false, type="array", @SWG\Items(type="integer")),
     *     @SWG\Parameter( name="tags", in="query", description="需要创建并关联的标签列表", required=false, type="array", @SWG\Items(
     *          @SWG\Property(property="tag_name", type="string"),
     *          @SWG\Property(property="tag_color", type="string"),
     *          @SWG\Property(property="font_color", type="string"),
     *          @SWG\Property(property="description", type="string"),
     *          @SWG\Property(property="category_id", type="integer")
     *     )),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              type="object"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function createTagGroup(Request $request)
    {
        $params = $request->all('group_name', 'description', 'tag_ids', 'tags');
        $rules = [
            'group_name' => ['required', '标签组名称不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['distributor_id'] = app('auth')->user()->get('distributor_id');

        // 支持逗号字符串或数组格式的标签ID
        if (isset($params['tag_ids']) && $params['tag_ids'] && !is_array($params['tag_ids'])) {
            $params['tag_ids'] = array_filter(explode(',', $params['tag_ids']));
        }

        $result = $this->memberTagService->createTagGroup($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/tag-group",
     *     summary="获取标签组列表（含组内标签）",
     *     tags={"会员"},
     *     description="按标签组维度分页返回，并附带组内标签列表",
     *     operationId="getTagGroupList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="每页数量", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              type="object"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTagGroupList(Request $request)
    {
        $page = (int)$request->get('page', 1);
        $pageSize = (int)$request->get('pageSize', 20);
        $groupName = $request->get('group_name', '');
        $user = app('auth')->user()->get();
        $filter = [
            'company_id' => $user['company_id'],
            // 'distributor_id' => $user['distributor_id'] ?? 0,
        ];
        // 如果传了group_name，添加到过滤条件中
        if (!empty($groupName)) {
            $filter['group_name'] = $groupName;
        }
        $result = $this->memberTagService->getTagGroupList($filter, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/member/tag-group/{group_id}",
     *     summary="编辑标签组及标签",
     *     tags={"会员"},
     *     description="可修改标签组名称/描述，新增或更新标签，删除指定标签并清理会员关联",
     *     operationId="updateTagGroup",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="group_id", in="path", description="标签组ID", required=true, type="integer"),
     *     @SWG\Parameter( name="group_name", in="query", description="标签组名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签组描述", required=false, type="string"),
     *     @SWG\Parameter( name="deleteids", in="query", description="需要删除的标签ID数组", required=false, type="array", @SWG\Items(type="integer")),
     *     @SWG\Parameter( name="tags", in="query", description="需创建/更新的标签列表", required=false, type="array", @SWG\Items(
     *          @SWG\Property(property="tag_id", type="integer", description="0表示新增"),
     *          @SWG\Property(property="tag_name", type="string"),
     *          @SWG\Property(property="tag_color", type="string"),
     *          @SWG\Property(property="font_color", type="string"),
     *          @SWG\Property(property="description", type="string"),
     *          @SWG\Property(property="category_id", type="integer")
     *     )),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              type="object"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateTagGroup(Request $request, $group_id)
    {
        $params = $request->all('group_name', 'description', 'deleteids', 'tags');
        $params['group_id'] = (int)$group_id;

        $rules = [
            'group_name' => ['required', '标签组名称不能为空'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['distributor_id'] = app('auth')->user()->get('distributor_id');

        // deleteids 支持逗号分隔
        if (isset($params['deleteids']) && $params['deleteids'] && !is_array($params['deleteids'])) {
            $params['deleteids'] = array_filter(explode(',', $params['deleteids']));
        }

        try {
            $result = $this->memberTagService->updateTagGroup($params);
        } catch (\Exception $e) {
            throw new StoreResourceFailedException($e->getMessage().'---'.$e->getFile().'---'.$e->getLine());
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/member/tag-group/{group_id}",
     *     summary="删除标签组（仅支持无标签关联的组）",
     *     tags={"会员"},
     *     description="删除标签组，若已关联标签则不允许删除",
     *     operationId="deleteTagGroup",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="group_id", in="path", description="标签组ID", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              type="object"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteTagGroup($group_id)
    {
        $companyId = app('auth')->user()->get('company_id');
        $distributorId = app('auth')->user()->get('distributor_id');
        try {
            $result = $this->memberTagService->deleteTagGroup((int)$group_id, $companyId, $distributorId);
        } catch (\Exception $e) {
            throw new StoreResourceFailedException($e->getMessage());
        }
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Put(
     *     path="/member/tag",
     *     summary="更新会员标签",
     *     tags={"会员"},
     *     description="更新会员标签",
     *     operationId="updateTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="tag_id", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="query", description="标签文字颜色", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="query", description="标签颜色", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/MemberTags"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateTags(Request $request)
    {
        $params = $request->all('tag_id', 'category_id', 'tag_name', 'description', 'tag_color', 'font_color');

        $rules = [
            'tag_id' => ['required', 'tagId不能为空'],
            'tag_name' => ['required', '标签名称不能为空'],
            'tag_color' => ['required', '标签颜色'],
            'font_color' => ['required', '标签字体颜色'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new StoreResourceFailedException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['tag_id'] = $params['tag_id'];
        $filter['company_id'] = $companyId;
        $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        $result = $this->memberTagService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/tag",
     *     summary="获取会员标签列表",
     *     tags={"会员"},
     *     description="获取会员标签列表",
     *     operationId="getTagsList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="page",
     *         in="query",
     *         description="页码",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter(
     *         name="page_size",
     *         in="query",
     *         description="每页长度",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=false, type="string"),
     *     @SWG\Parameter( name="category_id", in="query", description="标签分类id", required=false, type="string"),
     *     @SWG\Parameter( name="tag_status", in="query", description="标签类型，online：线上发布, self: 私有自定义", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="19", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          ref="#/definitions/MemberTags"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTagsList(Request $request)
    {
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', -1);
        if ($request->get('tag_name')) {
            $filter['tag_name|contains'] = $request->get('tag_name');
        }
        if ($request->get('category_id')) {
            $filter['category_id'] = $request->get('category_id');
        }
        if ($tagStatus = $request->get('tag_status')) {
            if ($tagStatus == 'self') {
                $filter['tag_status'] = $request->get('tag_status');
            } else {
                $filter['tag_status|neq'] = 'self';
            }
        }
        $userauth = app('auth')->user()->get();
        $filter['company_id'] = $userauth['company_id'];
        $filter['distributor_id'] = $userauth['distributor_id'] ?? 0;

        $orderBy = ['created' => 'DESC'];
        $result = $this->memberTagService->getListTags($filter, $page, $pageSize, $orderBy);
        // 实时查询标签人数
        $tagIds = array_column($result['list'], 'tag_id');
        if ($tagIds = array_unique(array_filter($tagIds))) {
            $filter = [
                'company_id' => $filter['company_id'],
                'tag_id' => $tagIds,
            ];
            $countList = $this->memberTagService->getCountList($filter);
            $countList = array_column($countList, 'num', 'tag_id');

            foreach ($result['list'] as &$value) {
                $value['self_tag_count'] = $countList[$value['tag_id']] ?? 0;
            }
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/member/tag/{tag_id}",
     *     summary="获取会员标签详情(废弃不可用)",
     *     tags={"会员"},
     *     description="获取会员标签详情(废弃不可用)",
     *     operationId="getTagsInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="path",
     *         description="标签id",
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
     *                     @SWG\Property(property="tag_id", type="integer"),
     *                     @SWG\Property(property="tag_name", type="string"),
     *                     @SWG\Property(property="description", type="string"),
     *                     @SWG\Property(property="tag_color", type="string"),
     *                     @SWG\Property(property="font_color", type="string"),
     *                     @SWG\Property(property="company_id", type="integer"),
     *                     @SWG\Property(property="created", type="integer"),
     *                     @SWG\Property(property="updated", type="integer"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getTagsInfo($tag_id)
    {
        $result = $this->memberTagService->getTagsInfo($tag_id);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/member/tag/{tag_id}",
     *     summary="删除会员标签详情",
     *     tags={"会员"},
     *     description="删除会员标签详情",
     *     operationId="deleteTag",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="path",
     *         description="标签id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteTag($tag_id)
    {
        $filter['tag_id'] = $tag_id;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $filter['distributor_id'] = app('auth')->user()->get('distributor_id');
        $result = $this->memberTagService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/member/reltag",
     *     summary="关联会员标签",
     *     tags={"会员"},
     *     description="关联会员标签",
     *     operationId="tagsRelUser",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_ids", in="query", description="tagId", required=true, type="string"),
     *     @SWG\Parameter( name="user_ids", in="query", description="userId", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function tagsRelUser(Request $request)
    {
        $params = $request->all('tag_ids', 'user_ids');
        $companyId = app('auth')->user()->get('company_id');
        if (!$params['user_ids']) {
            throw new StoreResourceFailedException('请选择会员');
        }
        if (!$params['tag_ids']) {
            throw new StoreResourceFailedException('请选择标签');
        }

        if (is_array($params['user_ids']) && is_array($params['tag_ids'])) {
            $result = $this->memberTagService->createRelTags($params['user_ids'], $params['tag_ids'], $companyId);
        } elseif (!is_array($params['user_ids'])) {
            $result = $this->memberTagService->createRelTagsByUserId($params['user_ids'], $params['tag_ids'], $companyId);
        } elseif (is_array($params['user_ids']) && !is_array($params['tag_ids'])) {
            $result = $this->memberTagService->createRelTagsByTagId($params['user_ids'], $params['tag_ids'], $companyId);
        }
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/member/tagsearch",
     *     summary="根据tagid筛选会员",
     *     tags={"会员"},
     *     description="根据tagid筛选会员",
     *     operationId="getUserIdsByTagids",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="path",
     *         description="标签id",
     *         required=true,
     *         type="integer"
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="string", example="1", description="user_id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getUserIdsByTagids(Request $request)
    {
        $result = [];
        if ($params['tag_id'] = $request->input('tagid')) {
            $params['company_id'] = app('auth')->user()->get('company_id');
            $result = $this->memberTagService->getUserIdsByTagids($params);
            return $this->response->array($result);
        }
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/member/reltagdel",
     *     summary="关联会员标签删除",
     *     tags={"会员"},
     *     description="关联会员标签删除",
     *     operationId="tagsRelUserDel",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_id", in="query", description="tagId", required=true, type="string"),
     *     @SWG\Parameter( name="user_id", in="query", description="userId", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function tagsRelUserDel(Request $request)
    {
        $params = $request->all('tag_id', 'user_id');
        $companyId = app('auth')->user()->get('company_id');
        if (!$params['user_id']) {
            throw new StoreResourceFailedException('请选择会员');
        }
        if (!$params['tag_id']) {
            throw new StoreResourceFailedException('请选择标签');
        }
        if ($params['tag_id'] == 'crm') {
            unset($params['tag_id']);
            throw new StoreResourceFailedException('标签不能关闭');
        }
        $result = $this->memberTagService->delRelMemberTag($companyId, $params['user_id'], $params['tag_id']);
        return $this->response->array(['status' => $result]);
    }
}
