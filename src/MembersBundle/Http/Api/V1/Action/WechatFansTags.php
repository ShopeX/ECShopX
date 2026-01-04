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
use Illuminate\Http\Response;
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\WechatFansService;
use MembersBundle\Events\SyncWechatTagsEvent;

class WechatFansTags extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wechat/tag",
     *     summary="添加微信标签",
     *     tags={"会员"},
     *     description="添加微信标签",
     *     operationId="wxtagCreate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_name",
     *         in="query",
     *         description="标签名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="102", description="标签id"),
     *                  @SWG\Property( property="tag_name", type="string", example="微信粉丝团长", description="标签名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function wxtagCreate(Request $request)
    {
        $params = $request->all("tag_name");

        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        if (!$params['tag_name']) {
            return $this->response->error("用户标签必填", 411);
        }
        if (strlen($params['tag_name']) > 30) {
            return $this->response->error("标签名长度必须小于30个字符!", 411);
        }
        if (!$companyId) {
            return $this->response->error("company_id不能为空！", 411);
        }
        $params['company_id'] = $companyId;

        $userService = new WechatFansService();
        $result = $userService->createTag($authorizerAppId, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wechat/tag",
     *     summary="微信标签更新",
     *     tags={"会员"},
     *     description="更新微信标签",
     *     operationId="wxtagUpdate",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="query",
     *         description="标签Id",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_name",
     *         in="query",
     *         description="标签名称",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_id", type="string", example="102", description="标签id"),
     *                  @SWG\Property( property="tag_name", type="string", example="团长", description="标签名称"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function wxtagUpdate(Request $request)
    {
        $params = $request->all('tag_id', 'tag_name');
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;

        if (!$params['tag_id']) {
            return $this->response->error("标签Id必填", 411);
        }
        if (!$params['tag_name']) {
            return $this->response->error("用户标签名必填", 411);
        }
        if (strlen($params['tag_name']) > 30) {
            return $this->response->error("标签名长度必须小于30个字符!", 411);
        }

        $userService = new WechatFansService();
        $result = $userService->updateTag($authorizerAppId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wechat/tag",
     *     summary="删除微信标签",
     *     tags={"会员"},
     *     description="删除微信标签",
     *     operationId="wxtagDelete",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="query",
     *         description="标签ID",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="102", description="标签id"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function wxtagDelete(Request $request)
    {
        $params = $request->all("tag_id");
        if ($params['tag_id'] == 0 || $params['tag_id'] == 1 || $params['tag_id'] == 2) {
            return $this->response->error("不能修改0/1/2这三个系统默认保留的标签", 411);
        }
        $userService = new WechatFansService();
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $params['company_id'] = app('auth')->user()->get('company_id');
        $result = $userService->delTag($authorizerAppId, $params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/tags",
     *     summary="获取微信标签列表",
     *     tags={"会员"},
     *     description="获取微信标签列表",
     *     operationId="getWxtagList",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="tag_id", type="string", example="102", description="标签id"),
     *                  @SWG\Property( property="tag_name", type="string", example="团长", description="标签名称"),
     *                  @SWG\Property( property="total", type="string", example="0", description=""),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getWxtagList()
    {
        $userService = new WechatFansService();
        $authInfo = app('auth')->user()->get();
        $filter = [
            'authorizer_appid' => $authInfo['authorizer_appid'],
            'company_id' => $authInfo['company_id'],
        ];
        $tags = $userService->getTagList($filter);

        return $this->response->array($tags);
    }

    /**
     * @SWG\Patch(
     *     path="/wechat/tag/batchSet",
     *     summary="微信用户批量打标签",
     *     tags={"会员"},
     *     description="微信用户批量打标签",
     *     operationId="batchSetUserTags",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="openIds",
     *         in="query",
     *         description="用户标识",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tagIds",
     *         in="query",
     *         description="标签列表",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function batchSetUserTags(Request $request)
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        $userService = new WechatFansService();
        $params = $request->all("openIds", "tagIds");
        if (!$params['openIds']) {
            return $this->response->error("用户标识必填", 411);
        }
        $openIds = explode(',', $params['openIds']);
        if ($params['tagIds']) {
            $tagIds = explode(',', $params['tagIds']);
        } else {
            $tagIds = [];
        }
        if (count($openIds) > 1) {
            //多个用户打多个标签
            $result = $userService->batchTagUsers($companyId, $authorizerAppId, $openIds, $tagIds);
        } elseif (count($openIds) == 1) {
            //单个用户打多个标签
            $openId = $openIds[0];
            $result = $userService->batchSetUserTags($companyId, $authorizerAppId, $openId, $tagIds);
        }

        return $this->response->array(['status' => $result]);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/tag/sync",
     *     summary="同步微信用户标签列表",
     *     tags={"会员"},
     *     description="同步微信用户标签列表",
     *     operationId="syncWechatTags",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="status", type="string", example="true", description=""),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function syncWechatTags()
    {
        $authorizerAppId = app('auth')->user()->get('authorizer_appid');
        $companyId = app('auth')->user()->get('company_id');
        $eventData = [
            'company_id' => $companyId,
            'authorizer_appid' => $authorizerAppId
        ];
        event(new SyncWechatTagsEvent($eventData));
        return $this->response->array(['status' => true]) ;
    }
}
