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

use MembersBundle\Services\MemberTagsService;
use OpenapiBundle\Services\MemberTagService as OpenapiMemberTagService;

class MemberTag extends Controller
{
    public $memberTagService;
    public $openapiMemberTagService;

    public function __construct()
    {
        $this->memberTagService = new MemberTagsService();
        $this->openapiMemberTagService = new OpenapiMemberTagService();
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tag.add",
     *     summary="新增标签",
     *     tags={"会员标签"},
     *     description="新增会员相关标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tag.add" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_name", description="标签名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_id", description="标签分类ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="description", description="标签描述" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="tag_color", description="标签颜色 默认：rgba(255, 25, 57, 1)" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="font_color", description="字体颜色 默认：rgba(255, 255, 255, 1)" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="294", description="标签ID"),
     *                  @SWG\Property( property="tag_name", type="string", example="sss", description="标签名称"),
     *                  @SWG\Property( property="category_id", type="string", example="0", description="标签分类ID"),
     *                  @SWG\Property( property="description", type="string", example="null", description="标签描述"),
     *                  @SWG\Property( property="tag_color", type="string", example="rgba(0, 206, 209, 1)", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="rgba(16, 1, 1, 1)", description="字体颜色"),
     *                  @SWG\Property( property="created", type="string", example="2021-06-29 11:41:00", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-06-29 11:41:00", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function createTag(Request $request)
    {
        $companyId = $request->get('auth')['company_id'];
        $params = $request->all('tag_name', 'category_id', 'description', 'tag_color', 'font_color');

        $rules = [
            'tag_name' => ['required', '分类名称必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['company_id'] = $companyId;
        $params['tag_color'] = $params['tag_color'] ?: 'rgba(255, 25, 57, 1)';
        $params['font_color'] = $params['font_color'] ?: 'rgba(255, 255, 255, 1)';

        $return = $this->openapiMemberTagService->createTag($params);
        return $this->response->array($return);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.member.tag.delete",
     *     summary="删除标签",
     *     tags={"会员标签"},
     *     description="根据标签ID，删除已创建的会员标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tag.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_id", description="标签ID" ),
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
    public function deleteTag(Request $request)
    {
        $params = $request->all('tag_id');
        $rules = [
            'tag_id' => ['required', '标签ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];

        $filter = [
            'company_id' => $companyId,
            'tag_id' => $params['tag_id'],
        ];
        $this->openapiMemberTagService->deleteTag($filter);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tag.update",
     *     summary="修改标签",
     *     tags={"会员标签"},
     *     description="根据标签ID,修改已创建的会员相关标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tag.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_id", description="标签ID" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_name", description="标签名称" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="category_id", description="分类ID" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="description", description="标签描述" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_color", description="标签颜色 格式：rgba(255, 255, 255, 1)" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="font_color", description="字体颜色 格式：rgba(255, 25, 57, 1)" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="294", description="标签ID"),
     *                  @SWG\Property( property="tag_name", type="string", example="sss1", description="标签名称"),
     *                  @SWG\Property( property="category_id", type="string", example="0", description="标签分类ID"),
     *                  @SWG\Property( property="description", type="string", example="null", description="标签描述"),
     *                  @SWG\Property( property="tag_color", type="string", example="rgba(0, 206, 209, 1)", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="rgba(16, 1, 1, 1)", description="字体颜色"),
     *                  @SWG\Property( property="created", type="string", example="2021-06-29 11:41:00", description="创建时间"),
     *                  @SWG\Property( property="updated", type="string", example="2021-06-29 11:56:09", description="更新时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function updateTag(Request $request)
    {
        $params = $request->all('tag_id', 'tag_name', 'description', 'tag_color', 'font_color');
        $rules = [
            'tag_id' => ['required', '标签ID必填'],
            'tag_name' => ['required', '标签名称必填'],
            'tag_color' => ['required', '标签颜色必填'],
            'font_color' => ['required', '标签字体颜色必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $params['company_id'] = $request->get('auth')['company_id'];
        if ($request->input('category_id')) {
            $params['category_id'] = $request->get('category_id');
        }
        $return = $this->openapiMemberTagService->updateTag($params);

        $this->api_response('true', '操作成功', $return, 'E0000');
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.tags.get",
     *     summary="查询标签列表",
     *     tags={"会员标签"},
     *     description="查询或筛选（标签名称）会员相关标签列表",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tags.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="tag_name", description="标签名称" ),
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
     *                          @SWG\Property( property="tag_id", type="string", example="292", description="标签ID"),
     *                          @SWG\Property( property="tag_name", type="string", example="标签名称", description="标签名称"),
     *                          @SWG\Property( property="category_id", type="string", example="0", description="标签分类ID"),
     *                          @SWG\Property( property="description", type="string", example="null", description="标签描述"),
     *                          @SWG\Property( property="tag_color", type="string", example="rgba(0, 206, 209, 1)", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="rgba(16, 1, 1, 1)", description="字体颜色"),
     *                          @SWG\Property( property="self_tag_count", type="string", example="1", description="标签会员数"),
     *                          @SWG\Property( property="created", type="string", example="2021-06-29 10:35:13", description="创建时间"),
     *                          @SWG\Property( property="updated", type="string", example="2021-06-29 10:35:13", description="更新时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getTagsList(Request $request)
    {
        $params = $request->all('page', 'page_size', 'tag_name');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量为1-500'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }

        $companyId = $request->get('auth')['company_id'];
        $params['page_size'] = $this->getPageSize();
        $filter = [
            'company_id' => $companyId,
        ];
        if ($params['tag_name']) {
            $filter['tag_name|contains'] = $params['tag_name'];
        }
        $orderBy = ['created' => 'DESC'];
        $result = $this->memberTagService->getListTags($filter, $params['page'], $params['page_size'], $orderBy);
        $return = $this->openapiMemberTagService->formateMemberTagList($result, (int)$params['page'], (int)$params['page_size']);

        return $this->response->array($return);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tagging.batch.cover",
     *     summary="更新/覆盖会员已打标签",
     *     tags={"会员标签"},
     *     description="根据会员信息（手机号）及标签信息（ID），更新/覆盖会员已打标签（注意：会员老标签会被移除）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagging.batch.cover" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobiles", description="会员手机号JSON [手机号,手机号]" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_ids", description="会员标签JSON [标签ID,标签ID]，可以为空" ),
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
    public function batchCoverMemberTags(Request $request)
    {
        $params = $request->all('mobiles', 'tag_ids');

        $rules = [
            'mobiles' => ['required', '会员手机号必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['mobiles'] = json_decode($params['mobiles'], 1);
        $params['tag_ids'] = json_decode($params['tag_ids'], 1);
        if (!is_array($params['mobiles']) || count($params['mobiles']) != count($params['mobiles'], 1)) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_ERROR, '会员手机号格式错误');
        }
        if (!is_array($params['tag_ids']) || count($params['tag_ids']) != count($params['tag_ids'], 1)) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_ERROR, '会员标签ID格式错误');
        }
        $companyId = $request->get('auth')['company_id'];
        $this->openapiMemberTagService->batchCoverMembersTags($params['mobiles'], $params['tag_ids'], $companyId);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Post(
     *     path="/ecx.member.tagging.batch.update",
     *     summary="给会员打标签",
     *     tags={"会员标签"},
     *     description="根据会员信息（手机号）及标签信息（ID），给会员打多个已创建过的标签（不会覆盖会员已打标签）",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagging.batch.update" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobiles", description="会员手机号JSON [手机号,手机号]" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_ids", description="会员标签JSON [标签ID,标签ID]" ),
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
    public function batchUpdateMemberTags(Request $request)
    {
        $params = $request->all('mobiles', 'tag_ids');

        $rules = [
            'mobiles' => ['required', '会员手机号必填'],
            'tag_ids' => ['required', '会员标签ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['mobiles'] = json_decode($params['mobiles'], 1);
        $params['tag_ids'] = json_decode($params['tag_ids'], 1);
        if (!is_array($params['mobiles']) || count($params['mobiles']) != count($params['mobiles'], 1)) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_ERROR, '会员手机号格式错误');
        }
        if (!is_array($params['tag_ids']) || count($params['tag_ids']) != count($params['tag_ids'], 1)) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_ERROR, '会员标签ID格式错误');
        }
        $companyId = $request->get('auth')['company_id'];
        $this->openapiMemberTagService->batchUpdateMembersTags($params['mobiles'], $params['tag_ids'], $companyId);
        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Delete(
     *     path="/ecx.member.tagged.delete",
     *     summary="删除会员已打标签",
     *     tags={"会员标签"},
     *     description="根据会员信息（手机号）和标签信息集合（ID）删除会员已打标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagged.delete" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="会员手机号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="tag_ids", description="会员标签ID JSON [标签ID,标签ID]" ),
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
    public function deleteMemberTagged(Request $request)
    {
        $params = $request->all('mobile', 'tag_ids');

        $rules = [
            'mobile' => ['required', '会员手机号必填'],
            'tag_ids' => ['required', '会员标签ID必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['tag_ids'] = json_decode($params['tag_ids'], 1);
        if (!is_array($params['tag_ids'])) {
            throw new ErrorException(ErrorCode::MEMBER_TAG_ERROR, '会员标签ID格式错误');
        }
        $companyId = $request->get('auth')['company_id'];
        $this->openapiMemberTagService->userRelTagDelete($params['mobile'], $params['tag_ids'], $companyId);

        return $this->response->array(['status' => true]);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.member.tagged.get",
     *     summary="查询会员已打标签",
     *     tags={"会员标签"},
     *     description="根据会员信息（手机号）查询会员已打标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.member.tagged.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="mobile", description="会员手机号" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="mobile", type="string", example="17521302310", description="会员手机号"),
     *                  @SWG\Property( property="username", type="string", example="叶子", description="会员姓名"),
     *                  @SWG\Property( property="tag_id", type="string", example="1", description="标签ID"),
     *                  @SWG\Property( property="tag_name", type="string", example="内部会员", description="标签名称"),
     *                  @SWG\Property( property="category_id", type="string", example="0", description="标签分类ID"),
     *                  @SWG\Property( property="description", type="string", example="内部员工", description="标签描述"),
     *                  @SWG\Property( property="tag_color", type="string", example="rgba(6, 176, 179, 1)", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="rgba(239, 25, 9, 1)", description="字体颜色"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getMemberTagged(Request $request)
    {
        $params = $request->all('mobile');
        $rules = [
            'mobile' => ['required', '会员手机号必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            $this->api_response('fail', $error, null, 'E0001');
        }

        $companyId = $request->get('auth')['company_id'];
        $return = $this->openapiMemberTagService->getUserTaggedList($companyId, $params['mobile']);

        return $this->response->array($return);
    }

    /**
     * @SWG\Get(
     *     path="/ecx.tag.members.get",
     *     summary="查询标签关联会员列表",
     *     tags={"会员标签"},
     *     description="根据标签信息（ID），分页查询已打该标签的所有会员及其会员所有已打标签",
     *     @SWG\Parameter( in="query", type="string", required=true, name="method", description="方法名称 ecx.tag.members.get" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="app_key", description="app_key" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="version", description="版本号" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="timestamp", description="请求时间" ),
     *     @SWG\Parameter( in="query", type="string", required=true, name="sign", description="签名" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page", description="当前页面，从1开始计数（不填默认1）" ),
     *     @SWG\Parameter( in="query", type="string", required=false, name="page_size", description="每页显示数量（不填默认20条）,最大为500" ),
     *     @SWG\Parameter( in="query", type="integer", required=true, name="tag_id", description="标签ID" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="status", type="string", example="succ", description="接口状态"),
     *          @SWG\Property( property="code", type="string", example="E0000", description="错误码"),
     *          @SWG\Property( property="message", type="string", example="", description="提示信息"),
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property(property="total_count", type="integer", default="8", description="列表数据总数量"),
     *                  @SWG\Property(property="is_last_page", type="integer", default="1", description="是否最后一页【0 不是最后一页】【1 是最后一页】"),
     *                  @SWG\Property( property="pager", type="object",
     *                      ref="#definitions/Pager",
     *                  ),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="mobile", type="string", example="15901872216", description="会员手机号"),
     *                          @SWG\Property( property="username", type="string", example="小不点", description="会员姓名"),
     *                          @SWG\Property( property="tag_list", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="tag_id", type="string", example="209", description="标签ID"),
     *                                  @SWG\Property( property="tag_name", type="string", example="会员标签", description="标签名称"),
     *                               ),
     *                          ),
     *                       ),
     *                  ),
     *              ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/OpenapiErrorRespones")))
     * )
     */
    public function getTagMembers(Request $request)
    {
        $params = $request->all('page', 'page_size', 'tag_id');
        $params['page'] = $this->getPage();
        $params['page_size'] = $this->getPageSize();
        $rules = [
            'page' => ['integer|min:1', '当前页面最小值为1'],
            'page_size' => ['integer|min:1|max:500', '每页显示数量1-500'],
            'tag_id' => ['required|integer', '会员标签必填'],
        ];
        $error = validator_params($params, $rules);
        if ($error) {
            throw new ErrorException(ErrorCode::SERVICE_MISSING_PARAMS, $error);
        }
        $params['page_size'] = $this->getPageSize();
        $companyId = $request->get('auth')['company_id'];
        $return = $this->openapiMemberTagService->getTagMembers($companyId, (int)$params['tag_id'], (int)$params['page'], (int)$params['page_size']);

        return $this->response->array($return);
    }
}
