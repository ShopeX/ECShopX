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
use Dingo\Api\Exception\ResourceException;
use MembersBundle\Services\UserGroupService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 *
 */
class UserGroupController extends Controller
{
    /**
     * @SWG\Post(
     *     path="/wxapp/member/grouplist",
     *     summary="创建会员分组",
     *     tags={"会员"},
     *     description="创建会员分组",
     *     operationId="createGroup",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="group_name", in="formData", description="分组名", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="formData", description="排序", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="返回数据(DC2Type:json_array)"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function createGroup(Request $request)
    {
        $salesperon_info = $this->auth->user();
        $group_name = $request -> input('group_name', '');
        $sort = $request -> input('sort', 0);

        $group_name = trim($group_name);
        $sort = intval(trim($sort));
        if (!$group_name) {
            throw new ResourceException("分组名不能为空");
        }
//        if (empty($sort)) {
//            throw new ResourceException('排序不能为空');
//        }

        $data = [
            'company_id' => $salesperon_info['company_id'] ?? 0,
            'salesperson_id' => $salesperon_info['salesperson_id'],
            'group_name' => $group_name,
            'sort' => $sort,
        ];

        $user_group_service = new UserGroupService();
        $result = $user_group_service -> createUserGroup($data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/grouplist",
     *     summary="获取分组列表",
     *     tags={"会员"},
     *     description="获取分组列表",
     *     operationId="getGroupList",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="group_id", type="string", example="25", description="分组id"),
     *                  @SWG\Property( property="group_name", type="string", example="老客", description="分组名"),
     *                  @SWG\Property( property="sort", type="string", example="2", description="排序"),
     *                  @SWG\Property( property="user_count", type="string", example="0", description=""),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getGroupList(Request $request)
    {
        $salesperon_info = $this->auth->user();

        $filter = [
            'salesperson_id' => $salesperon_info['salesperson_id'],
            "company_id" => $salesperon_info['company_id'],
        ];

        $user_group_service = new UserGroupService();
        $result = $user_group_service -> getUserGroupList($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/wxapp/member/grouplist",
     *     summary="修改分组",
     *     tags={"会员"},
     *     description="修改分组",
     *     operationId="updateGroup",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="group_id", in="formData", description="分组id", required=true, type="integer"),
     *     @SWG\Parameter( name="group_name", in="formData", description="分组名", required=true, type="string"),
     *     @SWG\Parameter( name="sort", in="formData", description="分组排序", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="group_id", type="string", example="25", description="分组id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="group_name", type="string", example="老客1", description="分组名"),
     *                  @SWG\Property( property="salesperson_id", type="string", example="38", description="导购员id"),
     *                  @SWG\Property( property="sort", type="string", example="2", description="排序"),
     *                  @SWG\Property( property="created", type="string", example="1612347218", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612347452", description="修改时间"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function updateGroup(Request $request)
    {
        $salesperon_info = $this->auth->user();
        $group_id = $request -> input('group_id', '');
        $group_name = $request -> input('group_name', '');
        $sort = $request -> input('sort', 0);

        $group_name = trim($group_name);
        $sort = intval(trim($sort));
        $group_id = intval($group_id);
        if (!$group_id) {
            throw new ResourceException("无效的分组");
        }
        if (!$group_name) {
            throw new ResourceException("分组名不能为空");
        }
//        if (empty($sort)) {
//            throw new ResourceException('排序不能为空');
//        }

        $filter = [
            "salesperson_id" => $salesperon_info['salesperson_id'],
            "company_id" => $salesperon_info['company_id'],
            "group_id" => $group_id
        ];
        $data = [
            'group_name' => $group_name,
            'sort' => $sort
        ];

        $user_group_service = new UserGroupService();
        $result = $user_group_service -> updateUserGroup($filter, $data);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/member/userlistbygroup",
     *     summary="导购员根据分组id获取会员列表",
     *     tags={"会员"},
     *     description="导购员根据分组id获取会员列表",
     *     operationId="getUsersByGroup",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="group_id", in="query", description="分组id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="2", description="自行更改字段描述"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="user_id", type="string", example="20078", description="用户id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="username", type="string", example="null", description="姓名"),
     *                          @SWG\Property( property="avatar", type="string", example="", description="头像"),
     *                          @SWG\Property( property="sex", type="string", example="0", description="用户性别"),
     *                          @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
     *                          @SWG\Property( property="address", type="string", example="null", description="地址"),
     *                          @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
     *                          @SWG\Property( property="industry", type="string", example="null", description=""),
     *                          @SWG\Property( property="income", type="string", example="null", description="收入"),
     *                          @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
     *                          @SWG\Property( property="habbit", type="array",
     *                              @SWG\Items( type="string", example="undefined", description=""),
     *                          ),
     *                          @SWG\Property( property="created", type="string", example="1587466375", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1600233253", description="修改时间"),
     *                          @SWG\Property( property="have_consume", type="string", example="true", description="是否有消费"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function getUsersByGroup(Request $request)
    {
        $salesperon_info = $this->auth->user();
        $group_id = $request -> input('group_id', '');

        $group_id = intval($group_id);
        if (!$group_id) {
            return $this->response->array(['total_count' => 0,'list' => []]);
        }

        $filter = [
            "salesperson_id" => $salesperon_info['salesperson_id'],
            "company_id" => $salesperon_info['company_id'],
            "group_id" => $group_id
        ];

        $user_group_service = new UserGroupService();
        $result = $user_group_service -> getUsersByGroup($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/wxapp/member/grouplist",
     *     summary="导购员删除分组",
     *     tags={"会员"},
     *     description="导购员删除分组",
     *     operationId="deleteGroup",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="group_id", in="query", description="分组id", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="返回数据"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function deleteGroup(Request $request)
    {
        $salesperon_info = $this->auth->user();
        $group_id = $request -> input('group_id', '');
        $group_id = intval($group_id);

        if (!$group_id) {
            throw new ResourceException("无效的分组");
        }

        $filter = [
            "salesperson_id" => $salesperon_info['salesperson_id'],
            "company_id" => $salesperon_info['company_id'],
            "group_id" => $group_id
        ];

        $user_group_service = new UserGroupService();
        $result = $user_group_service -> deleteUserGroup($filter);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/member/moveusertogroup",
     *     summary="移动会员到分组",
     *     tags={"会员"},
     *     description="移动会员到分组",
     *     operationId="moveUserToGroup",
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="group_id", in="formData", description="分组id", required=true, type="integer"),
     *     @SWG\Parameter( name="user_id", in="formData", description="会员id", required=true, type="integer"),
     *     @SWG\Parameter( name="sort", in="formData", description="分组排序", required=true, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="result", type="string", example="true", description="返回数据"),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/MembersErrorRespones") ) )
     * )
     */
    public function moveUserToGroup(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $user_ids = $request->input('user_ids', '');
        $group_id = $request->input('group_id', '');

        if (!$user_ids) {
            throw new ResourceException('请选择用户');
        }
        $user_ids = json_decode($user_ids, true);
        $user_ids = array_map('intval', $user_ids);
        $group_id = intval($group_id);
        foreach ($user_ids as $user) {
            if (!$user) {
                throw new ResourceException("包含无效的用户");
            }
        }
        if (!$group_id) {
            throw new ResourceException("请选择分组");
        }

        $data = [
            'user_ids' => $user_ids,
            'group_id' => $group_id,
            'salesperson_id' => $salesperson_info['salesperson_id'],
            'company_id' => $salesperson_info['company_id']
        ];

        $user_group_service = new UserGroupService();
        $result = $user_group_service -> moveUserToGroup($data);
        return $this->response->array($result);
    }
}
