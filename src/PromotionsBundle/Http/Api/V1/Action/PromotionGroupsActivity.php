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

namespace PromotionsBundle\Http\Api\V1\Action;

use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller as BaseController;
use PromotionsBundle\Services\PromotionGroupsActivityService;
use PromotionsBundle\Services\PromotionGroupsTeamMemberService;
use PromotionsBundle\Services\PromotionGroupsTeamService;

class PromotionGroupsActivity extends BaseController
{
    /**
     * @SWG\Definition(
     * definition="GroupsBase",
     * type="object",
     * @SWG\Property( property="groups_activity_id", type="string", example="132", description="活动ID"),
     * @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     * @SWG\Property( property="act_name", type="string", example="活动名称", description="活动名称"),
     * @SWG\Property( property="goods_id", type="string", example="5294", description="产品ID"),@SWG\Property( property="group_goods_type", type="string", example="services", description="团购活动商品类型 services:服务类商品 normal:实体商品"),
     * @SWG\Property( property="pics", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkrc2Zn94c3cQqg8Wm872g5MVdWQfWaHDOg1dicTETWsd4rhNl8UToSsESlVicicKgE1jBr7PicWfEhIA6g/0?wx_fmt=png", description="活动封面"),
     * @SWG\Property( property="act_price", type="string", example="1", description="活动价格(分)"),
     * @SWG\Property( property="person_num", type="string", example="2", description="拼团人数"),
     * @SWG\Property( property="begin_time", type="string", example="1611763200", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611836220", description="活动结束时间"),
     * @SWG\Property( property="limit_buy_num", type="string", example="1", description="限买数量"),
     * @SWG\Property( property="limit_time", type="string", example="1", description="成团时效(单位时)"),
     * @SWG\Property( property="store", type="string", example="11", description="拼团库存"),
     * @SWG\Property( property="free_post", type="string", example="true", description="是否包邮"),
     * @SWG\Property( property="rig_up", type="string", example="true", description="是否展示开团列表"),
     * @SWG\Property( property="robot", type="string", example="true", description="成团机器人"),
     * @SWG\Property( property="share_desc", type="string", example="分享描述", description="分享描述"),
     * @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     * @SWG\Property( property="created", type="string", example="1611836186", description=""),
     * @SWG\Property( property="updated", type="string", example="1611836221", description=""),
     * @SWG\Property( property="remaining_time", type="string", example="0", description="剩余结束时间"),
     * @SWG\Property( property="last_seconds", type="string", example="0", description="剩余结束秒"),
     * @SWG\Property( property="show_status", type="string", example="noend", description="展示状态 nostart:未开始 noend:未结束"),
     * @SWG\Property( property="goods_name", type="string", example="服务类商品1", description="商品名称"),
     * @SWG\Property( property="activity_status", type="string", example="3", description="活动状态 1:未开始 2:进行中 3:已结束"),
     * )
     */

    /**
     * @SWG\Definition(
     * definition="GropusDetail",
     * type="object",
     * @SWG\Property( property="groups_activity_id", type="string", example="132", description="活动ID"),
     * @SWG\Property( property="company_id", type="string", example="1", description="企业id"),
     * @SWG\Property( property="act_name", type="string", example="活动名称", description="活动名称"),
     * @SWG\Property( property="goods_id", type="string", example="5294", description="产品ID"),@SWG\Property( property="group_goods_type", type="string", example="services", description="团购活动商品类型 services:服务类商品 normal:实体商品"),
     * @SWG\Property( property="pics", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkrc2Zn94c3cQqg8Wm872g5MVdWQfWaHDOg1dicTETWsd4rhNl8UToSsESlVicicKgE1jBr7PicWfEhIA6g/0?wx_fmt=png", description="活动封面"),
     * @SWG\Property( property="act_price", type="string", example="1", description="活动价格(分)"),
     * @SWG\Property( property="person_num", type="string", example="2", description="拼团人数"),
     * @SWG\Property( property="begin_time", type="string", example="1611763200", description="活动开始时间"),
     * @SWG\Property( property="end_time", type="string", example="1611836220", description="活动结束时间"),
     * @SWG\Property( property="limit_buy_num", type="string", example="1", description="限买数量"),
     * @SWG\Property( property="limit_time", type="string", example="1", description="成团时效(单位时)"),
     * @SWG\Property( property="store", type="string", example="11", description="拼团库存"),
     * @SWG\Property( property="free_post", type="string", example="true", description="是否包邮"),
     * @SWG\Property( property="rig_up", type="string", example="true", description="是否展示开团列表"),
     * @SWG\Property( property="robot", type="string", example="true", description="成团机器人"),
     * @SWG\Property( property="share_desc", type="string", example="分享描述", description="分享描述"),
     * @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     * @SWG\Property( property="created", type="string", example="1611836186", description=""),
     * @SWG\Property( property="updated", type="string", example="1611836221", description=""),
     * @SWG\Property( property="remaining_time", type="string", example="0", description="剩余结束时间"),
     * @SWG\Property( property="last_seconds", type="string", example="0", description="剩余结束秒"),
     * @SWG\Property( property="show_status", type="string", example="noend", description="展示状态 nostart:未开始 noend:未结束"),
     * @SWG\Property( property="goods_name", type="string", example="服务类商品1", description="商品名称"),
     * @SWG\Property( property="activity_status", type="string", example="3", description="活动状态 1:未开始 2:进行中 3:已结束"),
     * @SWG\Property( property="goods", type="object", description="活动状态 1:未开始 2:进行中 3:已结束",
     *     ref="#/definitions/GoodsBase"
     * ),
     * )
     */

    /**
     * @SWG\Get(
     *     path="/promotion/groups",
     *     summary="获取拼团活动列表",
     *     tags={"营销"},
     *     description="获取拼团活动列表",
     *     operationId="getPromotionGroupsActivityList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="keywords", in="query", description="活动名称", type="integer"),
     *     @SWG\Parameter( name="view", in="query", description="1未开始 2进行中 3已结束", type="integer" ),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="page", in="query", description="页数", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,最大不能超过50，并且如果传入的limit参数是0，那么按默认值20处理", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="91", description="总条数"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/GroupsBase"
     *                      ),
     *                  ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getPromotionGroupsActivityList(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('keywords', null)) {
            $params['act_name'] = $request->input('keywords');
        }
        if ($request->input('view', 0)) {
            $params['view'] = $request->input('view');
        }
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result = $promotionGroupsActivityService->getList($params, $page, $pageSize);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotion/groups/{group_id}",
     *     summary="获取拼团活动详情",
     *     tags={"营销"},
     *     description="获取拼团活动详情",
     *     operationId="getPromotionGroupsActivityDetail",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="group_id", in="path", description="活动id", type="integer" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/GropusDetail"
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getPromotionGroupsActivityDetail($groupId, Request $request)
    {
        $params['company_id'] = app('auth')->user()->get('company_id');
        $params['groups_activity_id'] = $groupId;
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result = $promotionGroupsActivityService->getInfo($params);
        $itemsService = new ItemsService();
        $result['goods'] = $itemsService->getItemsDetail($result['goods_id']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/groups/{groupId}/team/",
     *     summary="获取拼团数据详情",
     *     tags={"营销"},
     *     description="获取拼团数据详情",
     *     operationId="getPromotionGroupsTeamList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="groupId", in="path", description="活动id", type="integer"),
     *     @SWG\Parameter( name="view", in="query", description="1未开始 2进行中 3已结束", type="integer" ),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", type="integer" ),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", type="integer" ),
     *     @SWG\Parameter( name="page", in="query", description="页数,默认1", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,默认20", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="91", description="总条数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(ref="#/definitions/TeamBase")
     *                 ),
     *                 @SWG\Property(
     *                     property="groupsActivity",
     *                     type="object",
     *                     ref="#/definitions/GroupsBase",
     *                 ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getPromotionGroupsTeamList($groupId, Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $params['company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('start_time', null)) {
            $params['start_time|gte'] = $request->input('start_time');
        }
        if ($request->input('end_time', 0)) {
            $params['end_time|lte'] = $request->input('end_time');
        }
        if ($request->input('view', 0)) {
            $params['team_status'] = $request->input('view');
        }
        $params['act_id'] = $groupId;

        $promotionGroupsTeamService = new PromotionGroupsTeamService();
        $result = $promotionGroupsTeamService->getList($params, $page, $pageSize);

        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $infoParams['groups_activity_id'] = $groupId;
        $infoParams['company_id'] = app('auth')->user()->get('company_id');
        $result['groupsActivity'] = $promotionGroupsActivityService->getInfo($infoParams);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/promotions/groups/team/{teamId}",
     *     summary="获取拼团数据成员详情",
     *     tags={"营销"},
     *     description="获取拼团数据成员详情",
     *     operationId="getPromotionGroupsTeamInfo",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="teamId", in="path", description="拼团id", type="integer"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", type="integer" ),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", type="integer" ),
     *     @SWG\Parameter( name="page", in="query", description="页数,默认1", type="integer" ),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量,默认20", type="integer"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 @SWG\Property( property="total_count", type="string", example="91", description="总条数"),
     *                 @SWG\Property(
     *                     property="list",
     *                     type="array",
     *                     @SWG\Items(ref="#/definitions/TeamBase")
     *                 ),
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getPromotionGroupsTeamInfo($teamId, Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $params['m.company_id'] = app('auth')->user()->get('company_id');
        if ($request->input('start_time', null)) {
            $params['m.start_time|gte'] = $request->input('start_time');
        }
        if ($request->input('end_time', 0)) {
            $params['m.end_time|lte'] = $request->input('end_time');
        }
        if ($request->input('order_id', 0)) {
            $params['m.order_id'] = $request->input('order_id');
        }
        $params['m.team_id'] = $teamId;

        $promotionGroupsTeamMemberService = new PromotionGroupsTeamMemberService();
        $result = $promotionGroupsTeamMemberService->getList($params['m.company_id'], $params, $page, $pageSize);

        $promotionGroupsTeamService = new PromotionGroupsTeamService();
        $infoParams['company_id'] = app('auth')->user()->get('company_id');
        $infoParams['team_id'] = $teamId;
        $result['teamInfo'] = $promotionGroupsTeamService->getInfo($infoParams);
        return $this->response->array($result);
    }



    /**
     * @SWG\Post(
     *     path="/promotions/groups",
     *     summary="创建拼团活动",
     *     tags={"营销"},
     *     description="创建拼团活动",
     *     operationId="createPromotionGroupsActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="act_name", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="act_price", in="formData", description="拼团价格", required=true, type="string"),
     *     @SWG\Parameter( name="store", in="formData", description="拼团商品库存", required=true, type="string"),     *
     *     @SWG\Parameter( name="date[0]", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="date[1]", in="formData", description="结束时间", type="string"),
     *     @SWG\Parameter( name="free_post", in="formData", description="是否包邮", default="true", type="string"),
     *     @SWG\Parameter( name="goods_id", in="formData", description="商品id", type="integer"),
     *     @SWG\Parameter( name="limit_buy_num", in="formData", description="限制购买个数，0为不限制", type="integer"),
     *     @SWG\Parameter( name="limit_time", in="formData", description="成团时效", type="integer"),
     *     @SWG\Parameter( name="person_num", in="formData", description="成团人数", type="string"),
     *     @SWG\Parameter( name="pics", in="formData", description="列表图片", type="string"),
     *     @SWG\Parameter( name="rig_up", in="formData", description="凑团", type="string"),
     *     @SWG\Parameter( name="robot", in="formData", description="成团机器人", default="true", type="string"),
     *     @SWG\Parameter( name="share_desc", in="formData", description="分享内容", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#definitions/GroupsBase"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function createPromotionGroupsActivity(Request $request)
    {
        $params = $request->input();
        $rules = [
            'act_name' => ['required|max:10', trans('PromotionsBundle.activity_name_required_max_10')],
            'limit_buy_num' => ['required|numeric|between:0,999', trans('PromotionsBundle.limit_quantity_max_999')],
            'person_num' => ['required|numeric|between:2,999', trans('PromotionsBundle.group_member_min_2_max_999')],
            'goods_id' => ['required', '请选择商品'],
            'pics' => ['required', '请上传图片'],
            'share_desc' => ['required', '分享描述必填'],
            'store' => ['required|numeric|min:0', trans('PromotionsBundle.stock_quantity_required_min_0')],
            'act_price' => ['required|numeric|min:0.01', trans('PromotionsBundle.sale_price_required_min_001')],
            'limit_time' => ['required|numeric|between:1,99', '成团时效必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');

        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result = $promotionGroupsActivityService->createActivity($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Put(
     *     path="/promotions/groups/{group_id}",
     *     summary="创建拼团活动",
     *     tags={"营销"},
     *     description="创建拼团活动",
     *     operationId="createPromotionGroupsActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="groups_activity_id", in="path", description="拼团活动id", required=true, type="string"),
     *     @SWG\Parameter( name="act_name", in="formData", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="act_price", in="formData", description="拼团价格", required=true, type="string"),
     *     @SWG\Parameter( name="store", in="formData", description="拼团商品库存", required=true, type="string"),     *
     *     @SWG\Parameter( name="date[0]", in="formData", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="date[1]", in="formData", description="结束时间", type="string"),
     *     @SWG\Parameter( name="free_post", in="formData", description="是否包邮", default="true", type="string"),
     *     @SWG\Parameter( name="goods_id", in="formData", description="商品id", type="integer"),
     *     @SWG\Parameter( name="limit_buy_num", in="formData", description="限制购买个数，0为不限制", type="integer"),
     *     @SWG\Parameter( name="limit_time", in="formData", description="成团时效", type="integer"),
     *     @SWG\Parameter( name="person_num", in="formData", description="成团人数", type="string"),
     *     @SWG\Parameter( name="pics", in="formData", description="列表图片", type="string"),
     *     @SWG\Parameter( name="rig_up", in="formData", description="凑团", type="string"),
     *     @SWG\Parameter( name="robot", in="formData", description="成团机器人", default="true", type="string"),
     *     @SWG\Parameter( name="share_desc", in="formData", description="分享内容", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/GroupsBase"
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function updatePromotionGroupsActivity($groupId, Request $request)
    {
        $params = $request->input();
        $rules = [
            'act_name' => ['required|max:10', trans('PromotionsBundle.activity_name_required_max_10')],
            'limit_buy_num' => ['required|numeric|between:0,999', trans('PromotionsBundle.limit_quantity_max_999')],
            'person_num' => ['required|numeric|between:2,999', trans('PromotionsBundle.group_member_min_2_max_999')],
            'goods_id' => ['required', '请选择商品'],
            'pics' => ['required', '请上传图片'],
            'share_desc' => ['required', '分享描述必填'],
            'store' => ['required|numeric|min:0', trans('PromotionsBundle.stock_quantity_required_min_0')],
            'act_price' => ['required|numeric|min:0.01', trans('PromotionsBundle.sale_price_required_min_001')],
            'limit_time' => ['required|numeric|between:1,99', '成团时效必填'],
        ];

        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['company_id'] = app('auth')->user()->get('company_id');

        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result = $promotionGroupsActivityService->updateActivity($groupId, $params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/promotion/groups/finish/{group_id}",
     *     summary="结束拼团活动",
     *     tags={"营销"},
     *     description="结束拼团活动",
     *     operationId="finishPromotionGroupsActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="group_id", in="path", description="活动id", type="integer" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="object",
     *                 ref="#/definitions/GroupsBase"
     *             )
     *          )
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function finishPromotionGroupsActivity($groupId)
    {
        $params['groups_activity_id'] = $groupId;
        $params['company_id'] = app('auth')->user()->get('company_id');
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $result = $promotionGroupsActivityService->finishActivity($params);
        return $this->response->array($result);
    }

    /**
     * @SWG\Delete(
     *     path="/promotion/groups/{group_id}",
     *     summary="删除拼团活动",
     *     tags={"营销"},
     *     description="删除拼团活动",
     *     operationId="deletePromotionGroupsActivity",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="group_id", in="path", description="活动id", type="integer" ),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function deletePromotionGroupsActivity($groupId)
    {
        $params['groups_activity_id'] = $groupId;
        $params['company_id'] = app('auth')->user()->get('company_id');
        $promotionGroupsActivityService = new PromotionGroupsActivityService();
        $promotionGroupsActivityService->deleteActivity($params);
        return $this->response->noContent();
    }
}
