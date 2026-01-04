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

namespace MembersBundle\Http\AdminApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Dingo\Api\Exception\ResourceException;

use MembersBundle\Services\MemberTagsService;

class UserTagController extends Controller
{
    /**
     * @SWG\Get(
     *     path="/wxapp/member/onlinetag",
     *     summary="获取会员标签列表",
     *     tags={"会员"},
     *     description="获取会员标签列表(平台系统标签,当前操作员添加的标签，包含标签分类)",
     *     operationId="getOnlineUserTags",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object"),
     *                  @SWG\Property( property="category_name", type="string", example="无分类", description="标签分类名称"),
     *                  @SWG\Property( property="taglist", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="1", description="标签id"),
     *                          @SWG\Property( property="tag_name", type="string", example="内部会员", description="标签名称"),
     *                          @SWG\Property( property="category_id", type="string", example="0", description="分类id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_color", type="string", example="rgba(6, 176, 179, 1)", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="rgba(239, 25, 9, 1)", description="字体颜色"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="tag_status", type="string", example="online", description="标签类型，online：线上发布, self: 私有自定义"),
     *                       ),
     *                  ),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getOnlineUserTags(Request $request)
    {
        // Built with ShopEx Framework
        $authInfo = $this->auth->user();
        $filter['company_id'] = $authInfo['company_id'];
        $filter['saleman_id'] = [0, $authInfo['salesperson_id']];
        if ($request->get('tag_name')) {
            $filter['tag_name|contains'] = $request->get('tag_name');
        }
        $memberTagService = new MemberTagsService();
        $result = $memberTagService->getUserTagsList($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/tagadd",
     *     summary="会员标签添加",
     *     tags={"会员"},
     *     description="会员标签添加",
     *     operationId="addUserTag",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_name", in="formData", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="formData", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="formData", description="标签颜色", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="formData", description="字体颜色", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="242", description="标签id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_name", type="string", example="打标签", description="标签名称"),
     *                  @SWG\Property( property="description", type="string", example="20261", description="内容"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="saleman_id", type="string", example="38", description="导购员id"),
     *                  @SWG\Property( property="tag_status", type="string", example="self", description="标签类型，online：线上发布, self: 私有自定义"),
     *                  @SWG\Property( property="category_id", type="string", example="0", description="分类id"),
     *                  @SWG\Property( property="self_tag_count", type="string", example="0", description="自定义标签下会员数量"),
     *                  @SWG\Property( property="tag_color", type="string", example="", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                  @SWG\Property( property="created", type="string", example="1612349559", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612349559", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function addUserTag(Request $request)
    {
        $params = $request->all('tag_name', 'description', 'tag_color', 'font_color');
        $params['tag_status'] = 'self';
        $authInfo = $this->auth->user();
        $params['saleman_id'] = $authInfo['salesperson_id'];
        $params['company_id'] = $authInfo['company_id'];
        $rules = [
            'company_id' => ['required', '企业数据有误'],
            'saleman_id' => ['required', '操作员数据有误'],
            'tag_name' => ['required', '标签名称必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $memberTagService = new MemberTagsService();
        $result = $memberTagService->create($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\post(
     *     path="/wxapp/member/tagupdate",
     *     summary="会员标签编辑",
     *     tags={"会员"},
     *     description="会员标签编辑",
     *     operationId="updateUserTag",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_id", in="formData", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="tag_name", in="formData", description="标签名称", required=true, type="string"),
     *     @SWG\Parameter( name="description", in="formData", description="标签描述", required=false, type="string"),
     *     @SWG\Parameter( name="tag_color", in="formData", description="标签颜色", required=false, type="string"),
     *     @SWG\Parameter( name="font_color", in="formData", description="字体颜色", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="242", description="标签id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_name", type="string", example="打标签", description="标签名称"),
     *                  @SWG\Property( property="description", type="string", example="20261", description="内容"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="saleman_id", type="string", example="38", description="导购员id"),
     *                  @SWG\Property( property="tag_status", type="string", example="self", description="标签类型，online：线上发布, self: 私有自定义"),
     *                  @SWG\Property( property="category_id", type="string", example="0", description="分类id"),
     *                  @SWG\Property( property="self_tag_count", type="string", example="0", description="自定义标签下会员数量"),
     *                  @SWG\Property( property="tag_color", type="string", example="#fff", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                  @SWG\Property( property="created", type="string", example="1612349559", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612350067", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateUserTag(Request $request)
    {
        $params = $request->all('tag_name', 'description', 'tag_color', 'font_color', 'tag_id', 'company_id', 'saleman_id');
        $rules = [
            'tag_name' => ['required', '标签名称必填'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        //$params['tag_status'] = 'self';
        $authInfo = $this->auth->user();
        $filter['saleman_id'] = $authInfo['salesperson_id'];
        $filter['tag_id'] = $request->get('tag_id');
        $filter['company_id'] = $authInfo['company_id'];

        $rules = [
            'tag_id' => ['required', '标签名称必填'],
            'company_id' => ['required', '企业数据有误'],
            'saleman_id' => ['required', '操作员数据有误'],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $memberTagService = new MemberTagsService();
        $info = $memberTagService->getInfo($filter);
        if ($info['tag_status'] != 'self') {
            throw new ResourceException('您无权限修改该标签');
        }
        $result = $memberTagService->updateOneBy($filter, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/selftag",
     *     summary="获取管理员自有会员标签",
     *     tags={"会员"},
     *     description="获取管理员自有会员标签",
     *     operationId="getSelfUserTags",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_name", in="query", description="标签名称", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="tag_id", type="string", example="242", description="标签id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="tag_name", type="string", example="打标签", description="标签名称"),
     *                          @SWG\Property( property="description", type="string", example="20261", description="内容"),
     *                          @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                          @SWG\Property( property="saleman_id", type="string", example="38", description="导购员id"),
     *                          @SWG\Property( property="tag_status", type="string", example="self", description="标签类型，online：线上发布, self: 私有自定义"),
     *                          @SWG\Property( property="category_id", type="string", example="0", description="分类id"),
     *                          @SWG\Property( property="self_tag_count", type="string", example="0", description="自定义标签下会员数量"),
     *                          @SWG\Property( property="tag_color", type="string", example="#fff", description="标签颜色"),
     *                          @SWG\Property( property="font_color", type="string", example="#ffffff", description="字体颜色"),
     *                          @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                          @SWG\Property( property="created", type="string", example="1612349559", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1612350067", description="修改时间"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getSelfUserTags(Request $request)
    {
        $authInfo = $this->auth->user();
        $memberTagService = new MemberTagsService();
        $filter = [
            'tag_status' => 'self',
            'saleman_id' => $authInfo['salesperson_id'],
            'company_id' => $authInfo['company_id'],
        ];
        if ($request->get('tag_name')) {
            $filter['tag_name'] = $request->get('tag_name');
        }
        $page = $request->get('page', 1);
        $pageSize = $request->get('pageSize', 20);
        $result = $memberTagService->lists($filter, null, $pageSize, $page);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/delselftag",
     *     summary="删除管理员自有会员标签",
     *     tags={"会员"},
     *     description="删除管理员自有会员标签",
     *     operationId="delSelfUserTags",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="tag_id", in="query", description="标签名称", required=true, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function delSelfUserTags(Request $request)
    {
        $authInfo = $this->auth->user();
        $memberTagService = new MemberTagsService();
        $filter = [
            'tag_status' => 'self',
            'saleman_id' => $authInfo['salesperson_id'],
            'company_id' => $authInfo['company_id'],
            'tag_id' => (array)$request->get('tag_id'),
        ];
        $result = $memberTagService->deleteBy($filter);
        return $this->response->array(['status' => $result]);
    }
}
