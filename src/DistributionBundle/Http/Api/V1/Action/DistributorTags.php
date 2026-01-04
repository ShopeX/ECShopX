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

namespace DistributionBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use DistributionBundle\Services\DistributorTagsService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as Controller;
use Swagger\Annotations as SWG;

class DistributorTags extends Controller
{
    /**
     * @SWG\Post(
     *     path="/distributor/tag",
     *     summary="新增店铺标签",
     *     tags={"店铺"},
     *     description="新增店铺标签",
     *     operationId="createTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="query", description="标签颜色", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="query", description="标签文字颜色", required=false, type="string"),
     *     @SWG\Parameter( name="front_show", in="query", description="前台是否显示 0 不显示 1 显示", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_name", type="string", example="test", description="标签名称"),
     *                  @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                  @SWG\Property( property="description", type="string", example="xx", description="申请描述"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="front_show", type="string", example="0", description="前台是否显示 0 否 1 是"),
     *                  @SWG\Property( property="created", type="string", example="1612160658", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612160658", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function createTags(Request $request)
    {
        $params = $request->all('tag_id', 'tag_name', 'description', 'tag_color', 'font_color', 'front_show');
        $rules = [
            'tag_name' => ['required', trans('DistributionBundle/Controllers/DistributorTags.tag_name_required')],
            'tag_color' => ['required', trans('DistributionBundle/Controllers/DistributorTags.tag_color_required')],
            'font_color' => ['required', trans('DistributionBundle/Controllers/DistributorTags.font_color_required')],
            'front_show' => ['in:0,1', trans('DistributionBundle/Controllers/DistributorTags.front_show_invalid')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $distributorTagsService = new DistributorTagsService();
        $result = $distributorTagsService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/distributor/tag/{tagId}",
     *     summary="更新店铺标签",
     *     tags={"店铺"},
     *     description="更新店铺标签",
     *     operationId="updateTags",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tagId", in="path", description="tag_id", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="query", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="query", description="标签文字颜色", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="query", description="标签颜色", required=false, type="string"),
     *     @SWG\Parameter( name="front_show", in="query", description="前台是否显示 0 不显示 1 显示", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_name", type="string", example="测试标签1", description="标签名称"),
     *                  @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="rgba(255, 255, 255, 1)", description="字体颜色"),
     *                  @SWG\Property( property="description", type="string", example="测试标签说明", description="描述"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="front_show", type="string", example="1", description="前台是否显示 0 否 1 是"),
     *                  @SWG\Property( property="created", type="string", example="1571129662", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612160434", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function updateTags($tagId, Request $request)
    {
        $params = $request->all('tag_name', 'description', 'tag_color', 'font_color', 'front_show');

        $rules = [
            'tag_name' => ['required', trans('DistributionBundle/Controllers/DistributorTags.tag_name_required')],
            'tag_color' => ['required', trans('DistributionBundle/Controllers/DistributorTags.tag_color_required')],
            'font_color' => ['required', trans('DistributionBundle/Controllers/DistributorTags.font_color_required')],
            'front_show' => ['in:0,1', trans('DistributionBundle/Controllers/DistributorTags.front_show_invalid')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $companyId = app('auth')->user()->get('company_id');
        $filter['tag_id'] = $tagId;
        $filter['company_id'] = $companyId;
        $distributorTagsService = new DistributorTagsService();
        $result = $distributorTagsService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/tag",
     *     summary="获取店铺标签列表",
     *     tags={"店铺"},
     *     description="获取店铺标签列表",
     *     operationId="getTagsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="页码", required=true, type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页长度", required=true, type="integer" ),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名字", required=false, type="string" ),
     *     @SWG\Parameter( name="front_show", in="query", description="是否前台展示【null 不作为筛选条件】【0 前台不显示】【1 前台显示】", required=false, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="2", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_name", type="string", example="店铺标签", description="标签名称"),
     *                          @SWG\Property( property="tag_color", type="string", example="rgba(199, 21, 133, 1)", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="rgba(144, 238, 144, 1)", description="字体颜色"),
     *                          @SWG\Property( property="description", type="string", example="店铺标签说明", description="描述"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="front_show", type="string", example="1", description="前台是否显示 0 否 1 是"),
     *                          @SWG\Property( property="created", type="string", example="1571130376", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1586317327", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getTagsList(Request $request)
    {
        $params = $request->all('page', 'pageSize', 'tag_name', 'front_show');
        $rules = [
            'page' => ['required', trans('DistributionBundle/Controllers/DistributorTags.page_required')],
            'pageSize' => ['required', trans('DistributionBundle/Controllers/DistributorTags.pagesize_required')],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ResourceException($error);
        }

        $page = $params['page'] ? intval($params['page']) : 1;
        $pageSize = $params['pageSize'] ? intval($params['pageSize']) : 20;

        $companyId = app('auth')->user()->get('company_id');

        $filter['company_id'] = $companyId;

        if (isset($params['tag_name']) && $params['tag_name']) {
            $filter['tag_name|contains'] = $params['tag_name'];
        }

        // 过滤是否前台显示的标签
        if (isset($params['front_show'])) {
            $filter['front_show'] = (int)$params['front_show'];
        }

        $orderBy = ['created' => 'DESC'];

        $distributorTagsService = new DistributorTagsService();
        $result = $distributorTagsService->getListTags($filter, $page, $pageSize, $orderBy);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/distributor/tag/{tagId}",
     *     summary="获取店铺标签详情",
     *     tags={"店铺"},
     *     description="获取店铺标签详情",
     *     operationId="getTagsInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="tag_id", in="path", description="标签id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_name", type="string", example="店铺标签", description="标签名称"),
     *                  @SWG\Property( property="tag_color", type="string", example="rgba(199, 21, 133, 1)", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="rgba(144, 238, 144, 1)", description="字体颜色"),
     *                  @SWG\Property( property="description", type="string", example="店铺标签说明", description="描述"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="front_show", type="string", example="1", description="前台是否显示 0 否 1 是"),
     *                  @SWG\Property( property="created", type="string", example="1571130376", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1586317327", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function getTagsInfo($tagId)
    {
        $filter['tag_id'] = $tagId;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $distributorTagsService = new DistributorTagsService();
        $result = $distributorTagsService->getInfo($filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/distributor/tag/{tagId}",
     *     summary="删除店铺标签详情",
     *     tags={"店铺"},
     *     description="删除店铺标签详情",
     *     operationId="deleteTag",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string" ),
     *     @SWG\Parameter( name="tagId", in="path", description="标签id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description="自行更改字段描述"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function deleteTag($tagId)
    {
        $filter['tag_id'] = $tagId;
        $filter['company_id'] = app('auth')->user()->get('company_id');
        $distributorTagsService = new DistributorTagsService();
        $result = $distributorTagsService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/distributor/reltag",
     *     summary="关联店铺标签",
     *     tags={"店铺"},
     *     description="关联店铺标签",
     *     operationId="tagsRelDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_ids", in="query", description="tagId", required=true, type="string"),
     *     @SWG\Parameter( name="item_ids", in="query", description="itemId", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="integer"),
     *                )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function tagsRelDistributor(Request $request)
    {
        $params = $request->all('tag_ids', 'distributor_id');
        $companyId = app('auth')->user()->get('company_id');

        if (!$params['distributor_id']) {
            throw new ResourceException(trans('DistributionBundle/Controllers/DistributorTags.select_distributor_first'));
        }

        if (!$params['tag_ids']) {
            throw new ResourceException(trans('DistributionBundle/Controllers/DistributorTags.select_tag_first'));
        }

        $distributorTagsService = new DistributorTagsService();
        if (is_array($params['distributor_id']) && is_array($params['tag_ids'])) {
            $result = $distributorTagsService->createRelTags($params['distributor_id'], $params['tag_ids'], $companyId);
        } elseif (!is_array($params['distributor_id'])) {
            $result = $distributorTagsService->createRelTagsByDistributorId($params['distributor_id'], $params['tag_ids'], $companyId);
        } elseif (is_array($params['distributor_id']) && !is_array($params['tag_ids'])) {
            $result = $distributorTagsService->createRelTagsByTagId($params['distributor_id'], $params['tag_ids'], $companyId);
        }

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Post(
     *     path="/distributor/deltag",
     *     summary="店铺与店铺标签做解绑",
     *     tags={"店铺"},
     *     description="店铺与店铺标签做解绑",
     *     operationId="tagsRemoveDistributor",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="tag_ids", in="query", description="多个标签id, 需要是数组", required=true, type="string"),
     *     @SWG\Parameter( name="distributor_ids", in="query", description="多个店铺id, 需要是数组", required=true, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="status", type="integer"),
     *                )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DistributionErrorRespones") ) )
     * )
     */
    public function tagsRemoveDistributor(Request $request)
    {
        // 获取公司id
        $companyId = (int)app('auth')->user()->get('company_id');
        // 获取店铺id
        $distributorIds = $request->input("distributor_ids");
        if (!is_array($distributorIds)) {
            throw new \Exception("店铺参数有误！");
        }
        if (count($distributorIds) > 50) {
            throw new \Exception("店铺最多只能选择50个！");
        }
        // 获取店铺标签id
        $tagIds = $request->input("tag_ids");
        if (!is_array($tagIds)) {
            throw new \Exception("店铺标签参数有误！");
        }
        if (count($tagIds) > 50) {
            throw new \Exception("店铺标签最多只能选择50个！");
        }

        // 硬删除
        (new DistributorTagsService())->deleteRelTags($companyId, $distributorIds, $tagIds);

        return $this->response->array(['status' => 1]);
    }
}
