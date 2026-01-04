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

namespace DataCubeBundle\Http\Api\V1\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Response;
use DataCubeBundle\Services\SourcesService;
use MembersBundle\Services\MemberTagsService;

class Sources extends BaseController
{
    /**
     * @SWG\Post(
     *     path="/datacube/sources",
     *     summary="添加来源",
     *     tags={"统计"},
     *     description="添加来源",
     *     operationId="createSources",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="source_name", in="query", description="来源名称", required=true, type="string" ),
     *     @SWG\Parameter( name="tags_id", in="query", description="来源名称", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items( type="object",
     *                     @SWG\Property(property="source_id", type="integer"),
     *                     @SWG\Property(property="source_name", type="string"),
     *                     @SWG\Property(property="company_id", type="integer")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function createSources(Request $request)
    {
        $params = $request->input();
        $validator = app('validator')->make($params, [
            'source_name' => 'required',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('添加来源出错.', $validator->errors());
        }

        $sourcesService = new SourcesService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'source_name' => $params['source_name'],
        ];
        $data['tags_id'] = $request->get('tags_id', '');

        $result = $sourcesService->addSources($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/datacube/sources/{source_id}",
     *     summary="更新来源",
     *     tags={"统计"},
     *     description="更新来源",
     *     operationId="updateSources",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="source_id", in="path", description="来源id", required=true, type="string" ),
     *     @SWG\Parameter( name="tags_id", in="query", description="来源名称", required=true, type="string" ),
     *     @SWG\Parameter( name="source_name", in="query", description="来源名称", required=true, type="string" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="source_id", type="string", example="14", description="来源id"),
     *                  @SWG\Property( property="source_name", type="string", example="千人千码是啥", description="来源名称"),
     *                  @SWG\Property( property="tags_id", type="array",
     *                      @SWG\Items( type="string", example="210", description=""),
     *                  ),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="created", type="string", example="1604542280", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1604542280", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function updateSources($source_id, Request $request)
    {
        $params = $request->input();
        $params['source_id'] = $source_id;
        $validator = app('validator')->make($params, [
            'source_name' => 'required',
            'source_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('更新来源出错.', $validator->errors());
        }

        $sourcesService = new SourcesService();
        $authInfo = app('auth')->user()->get();
        $data = [
            'company_id' => $authInfo['company_id'],
            'source_id' => $source_id,
            'source_name' => $params['source_name'],
        ];
        $data['tags_id'] = $request->get('tags_id', '');
        $result = $sourcesService->updateSources($data);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/datacube/sources/{source_id}",
     *     summary="删除来源",
     *     tags={"统计"},
     *     description="删除来源",
     *     operationId="deleteSources",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="source_id", in="path", description="来源id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构",  @SWG\Schema(
     *             @SWG\Property( property="data", type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="source_id", type="string")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function deleteSources($source_id)
    {
        $params['source_id'] = $source_id;
        $validator = app('validator')->make($params, [
            'source_id' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            throw new ResourceException('删除来源出错.', $validator->errors());
        }

        $company_id = app('auth')->user()->get('company_id');
        $sourcesService = new SourcesService();
        $params = [
            'source_id' => $source_id,
            'company_id' => $company_id,
        ];
        $result = $sourcesService->deleteSources($params);

        return $this->response->noContent();
    }

    /**
     * @SWG\Get(
     *     path="/datacube/sources/{source_id}",
     *     summary="获取来源详情",
     *     tags={"统计"},
     *     description="获取来源详情",
     *     operationId="getSourcesDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="source_id", in="path", description="来源id", required=true, type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="source_id", type="string", example="13", description="来源id"),
     *                  @SWG\Property( property="source_name", type="string", example="小苹果活动", description="来源名称"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tags_id", type="array",
     *                      @SWG\Items( type="string", example="199", description=""),
     *                  ),
     *                  @SWG\Property( property="created", type="string", example="1596522083", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1596522083", description="修改时间"),
     *                  @SWG\Property( property="checkTags", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="199", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_name", type="string", example="白富美", description="标签名称"),
     *                          @SWG\Property( property="description", type="string", example="", description="描述"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="saleman_id", type="string", example="81", description=""),
     *                          @SWG\Property( property="tag_status", type="string", example="self", description="标签类型，online：线上发布, self: 私有自定义"),
     *                          @SWG\Property( property="category_id", type="string", example="0", description=""),
     *                          @SWG\Property( property="self_tag_count", type="string", example="97", description="自定义标签下会员数量"),
     *                          @SWG\Property( property="tag_color", type="string", example="", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="", description="字体颜色"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="分销商id"),
     *                          @SWG\Property( property="created", type="string", example="1595753455", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1595753455", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getSourcesDetail($source_id)
    {
        $validator = app('validator')->make(['source_id' => $source_id], [
            'source_id' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取来源详情出错.', $validator->errors());
        }
        $sourcesService = new SourcesService();
        $result = $sourcesService->getSourcesDetail($source_id);
        $company_id = app('auth')->user()->get('company_id');
        if ($company_id != $result['company_id']) {
            throw new ResourceException('获取门店信息有误，请确认您的门店的ID.');
        }
        if ($result['tags_id'] ?? null) {
            $filter = [
                'tag_id' => $result['tags_id'],
                'company_id' => $company_id,
            ];
            $memberTagsService = new MemberTagsService();
            $taglist = $memberTagsService->getListTags($filter);
            $result['checkTags'] = $taglist['list'];
        }

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/datacube/sources",
     *     summary="获取来源列表",
     *     tags={"统计"},
     *     description="获取来源列表",
     *     operationId="getSourcesList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", type="string" ),
     *     @SWG\Parameter( name="page", in="query", description="当前页面,获取门店列表的初始偏移位置，从1开始计数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="12", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="sourceId", type="string", example="13", description=""),
     *                          @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                          @SWG\Property( property="sourceName", type="string", example="小苹果活动", description=""),
     *                          @SWG\Property( property="created", type="string", example="1596522083", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1596522083", description=" | 修改时间"),
     *                          @SWG\Property( property="tagsId", type="array",
     *                              @SWG\Items( type="string", example="199", description="自行更改字段描述"),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="checkTags", type="object",
     *                          @SWG\Property( property="141", type="string", example="已购物", description=""),
     *                          @SWG\Property( property="198", type="string", example="高富帅", description=""),
     *                          @SWG\Property( property="199", type="string", example="白富美", description=""),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function getSourcesList(request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer|min:1|max:1000',
        ]);

        if ($validator->fails()) {
            throw new ResourceException('获取来源列表出错.', $validator->errors());
        }

        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('source_name')) {
            $params['source_name'] = $request->input('source_name');
        }
        $page = $inputData['page'];
        $pageSize = $inputData['pageSize'];
        $sourcesService = new SourcesService();
        $result = $sourcesService->getSourcesList($params, $page, $pageSize);
        $tagIds = [];
        foreach ($result['list'] as &$value) {
            $value['tagsId'] = $tagsId = json_decode($value['tagsId'], true);
            if ($tagsId) {
                $tagIds = array_merge($tagIds, $tagsId);
            }
        }
        $filter = [
            'tag_id' => $tagIds,
            'company_id' => $params['company_id']
        ];

        $memberTagsService = new MemberTagsService();
        $taglist = $memberTagsService->getListTags($filter, 1, -1);
        $result['checkTags'] = array_column($taglist['list'], 'tag_name', 'tag_id');
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/datacube/savetags",
     *     summary="为来源绑定标签",
     *     tags={"统计"},
     *     description="为来源绑定标签",
     *     operationId="saveSourceTags",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="tags_id",
     *         in="query",
     *         description="来源名称",
     *         required=true,
     *         type="string"
     *     ),
     *     @SWG\Parameter(
     *         name="source_ids",
     *         in="query",
     *         description="来源名称",
     *         required=true,
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
     *                     @SWG\Property(property="source_id", type="integer"),
     *                     @SWG\Property(property="source_name", type="string"),
     *                     @SWG\Property(property="company_id", type="integer")
     *                 )
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/DatasourcesErrorRespones") ) )
     * )
     */
    public function saveSourceTags(Request $request)
    {
        $tags_id = $request->get('tags_id');
        $params['tags_id'] = is_array($tags_id) ? $tags_id : json_decode($tags_id, true);

        $sourceIds = $request->get('source_ids');
        $filter['source_id'] = is_array($sourceIds) ? $sourceIds : json_decode($sourceIds, true);

        $filter['company_id'] = app('auth')->user()->get('company_id');

        $sourcesService = new SourcesService();
        $result = $sourcesService->patchSaveTags($filter, $params);
        return $this->response->noContent();
    }
}
