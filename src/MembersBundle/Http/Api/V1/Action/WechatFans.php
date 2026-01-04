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
use App\Http\Controllers\Controller as Controller;
use MembersBundle\Services\WechatFansService;

class WechatFans extends Controller
{
    public $wechatFansService;
    public $limit;

    public function __construct()
    {
        $this->wechatFansService = new WechatFansService();
        $this->limit = 100;
    }

    /**
     * @SWG\Put(
     *     path="/wechat/fans/remark",
     *     summary="修改微信用户备注",
     *     tags={"会员"},
     *     description="修改微信用户备注",
     *     operationId="wxremark",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="open_id",
     *         in="query",
     *         description="微信用户标识",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="remark",
     *         in="query",
     *         description="新的备注名",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/WechatFans"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function wxremark(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $params = $request->all("open_id", "remark");
        if (!$params['open_id']) {
            return $this->response->error("用户标识必填", 411);
        }
        if (strlen($params['remark']) > 30) {
            return $this->response->error("备注长度必须小于30个字符!", 411);
        }
        $params['authorizer_appid'] = $authInfo['authorizer_appid'];
        $params['company_id'] = $authInfo['company_id'];
        $result = $this->wechatFansService->remark($params);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/fans",
     *     summary="获取微信用户基本信息",
     *     tags={"会员"},
     *     description="获取微信用户基本信息",
     *     operationId="getWxFansInfo",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="open_id",
     *         in="query",
     *         description="微信用户标识",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data",
     *              ref="#/definitions/WechatFans"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getWxFansInfo(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $param = $request->all("open_id");
        $params['company_id'] = $authInfo['company_id'];
        $params['authorizer_appid'] = $authInfo['authorizer_appid'];
        $user = $this->wechatFansService->getUserInfo($param);

        return $this->response->array($user);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/fans/list",
     *     summary="获取微信用户列表",
     *     tags={"会员"},
     *     description="获取微信用户列表",
     *     operationId="getWxFansList",
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
     *         description="当前页数",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="显示数量",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="nickname",
     *         in="query",
     *         description="微信昵称",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="remark",
     *         in="query",
     *         description="微信备注",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="query",
     *         description="标签id",
     *         required=false,
     *         type="string",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="29", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          ref="#/definitions/WechatFans"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getWxFansList(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $page = $request->input('page', 1);
        $limit = $request ->input('pageSize', $this->limit);
        $params = $request->all();
        $filter = [
            'authorizer_appid' => $authInfo['authorizer_appid'],
            'company_id' => $authInfo['company_id']
        ];
        if (isset($params['nickname']) && $params['nickname']) {
            $filter['nickname|contains'] = $params['nickname'];
        }
        if (isset($params['remark']) && $params['remark']) {
            $filter['remark|contains'] = $params['remark'];
        }
        if (isset($params['tag_id']) && $params['tag_id']) {
            $filter['tag_id'] = $params['tag_id'];
        }
        $filter['subscribed'] = isset($params['subscribed']) ? $params['subscribed'] : 1;
        $result = $this->wechatFansService->getUserList($page, $limit, $filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/tag/fans",
     *     summary="获取指定标签下用户列表",
     *     tags={"会员"},
     *     description="获取指定标签下用户列表",
     *     operationId="getWxusersOfTag",
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
     *         description="当前页数",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="pageSize",
     *         in="query",
     *         description="显示数量",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Parameter(
     *         name="tag_id",
     *         in="query",
     *         description="标签ID",
     *         required=true,
     *         type="integer",
     *     ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description=""),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/WechatFans"
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getWxFansOfTag(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $page = $request->input('page', 1);
        $limit = $request ->input('pageSize', $this->limit);

        $params = $request->all();
        $filter = [
            'company_id' => $authInfo['company_id'],
            'authorizer_appid' => $authInfo['authorizer_appid']
        ];
        if (isset($params['tag_id']) && $params['tag_id']) {
            $filter['tag_id'] = $params['tag_id'];
        } else {
            return $this->response->error('tag_id必填！', 411);
        }

        $result = $this->wechatFansService->getUsersByTagId($page, $limit, $filter);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/fans/tags",
     *     summary="获取指定用户的标签列表(已废弃)",
     *     tags={"会员"},
     *     description="获取指定用户的标签列表(已废弃)",
     *     operationId="getWxTagsOfFans",
     *     @SWG\Parameter(
     *         name="Authorization",
     *         in="header",
     *         description="JWT验证token",
     *         required=true,
     *         type="string",
     *     ),
     *     @SWG\Parameter(
     *         name="open_id",
     *         in="query",
     *         description="用户标识",
     *         required=true,
     *         type="integer",
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
     *                     @SWG\Property(property="url", type="string"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getWxTagsOfFans(Request $request)
    {
        $authInfo = app('auth')->user()->get();
        $params = $request->all('open_id');
        if (!$params['open_id']) {
            return $this->response->error('用户标识必填', 411);
        }

        $result = $this->wechatFansService->getTagsByOpenId($params['open_id'], $authInfo['company_id'], $authInfo['authorizer_appid']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wechat/fans/sync",
     *     summary="同步微信用户列表",
     *     tags={"会员"},
     *     description="同步微信用户列表",
     *     operationId="syncWechatFans",
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
    public function syncWechatFans()
    {
        $authInfo = app('auth')->user()->get();
        $syncCount = $this->wechatFansService->getSyncUsersCount($authInfo['company_id']);
        if ($syncCount['count'] >= 3) {
            return $this->response->error('一天只能同步三次粉丝', 500);
        }
        if ($syncCount['lastInsert'] && time() - 60 * 10 < $syncCount['lastInsert']) {
            return $this->response->error('十分钟内只能同步一次', 500);
        }
        $result = $this->wechatFansService->syncWechatFans($authInfo['authorizer_appid'], $authInfo['company_id']);

        return $this->response->array(['status' => $result]);
    }
}
