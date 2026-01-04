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
        $params = $request->all('category_id', 'tag_name', 'description', 'tag_color', 'font_color');

        $rules = [
            'tag_name' => ['required', '标签名称不能为空'],
            'tag_color' => ['required', '标签颜色'],
            'font_color' => ['required', '标签字体颜色'],
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

        $result = $this->memberTagService->create($params);
        return $this->response->array($result);
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
