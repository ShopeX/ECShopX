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

namespace CommunityBundle\Http\FrontApi\V1\Action\chief;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityChiefDistributorService;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use CommunityBundle\Services\CommunityItemsService;
use Illuminate\Http\Request;
use CommunityBundle\Services\CommunitySettingService;

class CommunityActivity extends BaseController
{
    /**
     * @SWG\Definition(
     *     definition="CommunityChiefActivityItems",
     *         @SWG\Property(property="item_id", type="integer", example="1",description="商品ID"),
     *         @SWG\Property(property="item_name", type="integer", example="1",description="商品名称"),
     *         @SWG\Property(property="item_brief", type="integer", example="1",description="简介"),
     *         @SWG\Property(property="item_pics", type="string", example="123",description="图片"),
     *          @SWG\Property(property="price", type="string", example="1",description="价格"),
     *          @SWG\Property(property="cost_price", type="string", example="1611045460",description=""),
     *          @SWG\Property(property="market_price", type="string", example="desc",description=""),
     * )
     */
    /**
     * @SWG\Definition(
     *     definition="CommunityChiefActivityZiti",
     *         @SWG\Property(property="ziti_id", type="integer", example="1",description="自提ID"),
     *         @SWG\Property(property="condition_num", type="integer", example="1",description="成团数量"),
     *         @SWG\Property(property="remark", type="string", example="1",description="备注"),
     * )
     */
    /**
     * @SWG\Definition(
     *     definition="CommunityChiefActivityOrder",
     *         @SWG\Property(property="activity_trade_no", type="integer", example="1",description="跟团ID"),
     *         @SWG\Property(property="username", type="string", example="1",description="用户名"),
     *         @SWG\Property(property="avatar", type="string", example="1",description="头像"),
     *         @SWG\Property(property="item_name", type="string", example="1",description="商品名称"),
     *         @SWG\Property(property="num", type="string", example="1",description="商品数量"),
     *         @SWG\Property(property="save_time", type="string", example="1",description="跟团时间"),
     * )
     */
    /**
     * @SWG\Definition(
     *     definition="CommunityChiefActivity",
     *         @SWG\Property(property="activity_id", type="integer", example="1",description="活动ID"),
     *         @SWG\Property(property="distributor_id", type="integer", example="1",description="店铺ID"),
     *         @SWG\Property(property="chief_id", type="integer", example="1",description="团长ID"),
     *         @SWG\Property(property="company_id", type="integer", example="1",description="company_id"),
     *         @SWG\Property(property="activity_name", type="string", example="123",description="活动名称"),
     *          @SWG\Property(property="activity_pics", type="string", example="1",description="活动图片"),
     *          @SWG\Property(property="activity_desc", type="string", example="1611045460",description="活动简介"),
     *          @SWG\Property(property="activity_intro", type="string", example="desc",description="活动详情"),
     *          @SWG\Property(property="start_time", type="integer", example="101",description="开始时间"),
     *          @SWG\Property(property="end_time", type="string", example="desc",description="结束时间"),
     *          @SWG\Property(property="activity_status", type="string", example="desc",description="活动状态 private草稿 public已发布 protected已暂停 success确认成团 fail成团失败"),
     *          @SWG\Property(property="min_price", type="string", example="1",description="商品最小价格"),
     *          @SWG\Property(property="max_price", type="string", example="1",description="商品最高价格"),
     *          @SWG\Property(property="price_rang", type="string", example="1",description="商品价格范围"),
     *          @SWG\Property(property="delivery_status", type="string", example="1",description="发货状态。可选值有 DONE—已发货;PENDING—待发货"),
     *          @SWG\Property(property="delivery_time", type="string", example="1",description="发货时间"),
     *          @SWG\Property(property="total_fee", type="string", example="1",description="订单金额"),
     *          @SWG\Property(property="order_num", type="string", example="1",description="订单数量"),
     *          @SWG\Property(property="user_num", type="string", example="1",description="跟团人数"),
     *          @SWG\Property(property="last_second", type="string", example="1",description="距离结束还有多少秒"),
     *          @SWG\Property(property="save_time", type="string", example="1",description="发布时间"),
     *          @SWG\Property(property="is_activity_author", type="boolean", example="false",description="是否有修改活动的权限"),
     *          @SWG\Property(property="buttons", type="string", example="['update','success','fail']",description="团管理的按钮 update修改 success成团 fail取消团"),
     *          @SWG\Property(property="chief_info", type="array",
     *               @SWG\Items(
     *                  type="object",
     *                  ref="#/definitions/CommunityChief"
     *                )
     *         ),
     *          @SWG\Property(property="items", type="array",
     *               @SWG\Items(
     *                  type="object",
     *                  ref="#/definitions/CommunityChiefActivityItems"
     *                )
     *         ),
     *          @SWG\Property(property="ziti", type="array",
     *               @SWG\Items(
     *                  type="object",
     *                  ref="#/definitions/CommunityChiefZiti"
     *                )
     *         ),
     *          @SWG\Property(property="orders", type="array",
     *               @SWG\Items(
     *                  type="object",
     *                  ref="#/definitions/CommunityChiefActivityOrder"
     *                )
     *         ),
     * )
     */

    // 获取活动列表
    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/activity",
     *     summary="获取团长活动列表",
     *     tags={"社区团"},
     *     description="获取团长活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="page", in="query", description="page", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="page_size", required=false, type="integer"),
     *     @SWG\Parameter( name="activity_status", in="query", description="活动状态 private草稿 public已发布 protected已暂停 success确认成团 fail成团失败", required=false, type="string"),
     *     @SWG\Parameter( name="order_by", in="query", description="排序方式 create_time创建时间 order_num销量", required=false, type="string"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                          ref="#/definitions/CommunityChiefActivity"
     *                      )
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getActivityList(Request $request)
    {
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }
        $filter = [
            'chief_id' => $authInfo['chief_id'],
        ];

        $activityStatus = $request->input('activity_status');
        if ($activityStatus) {
            $filter['activity_status'] = $activityStatus;
        }

        $orderBy = $request->input('order_by');
        $sort = ['created_at' => 'desc'];
        if (!empty($orderBy)) {
            switch ($orderBy) {
                case 'create_time':
                    $sort = ['created_at' => 'desc'];
                    break;
                case 'order_num':
                    $sort = ['order_num' => 'desc'];
                    break;
            }
        }

        $service = new CommunityActivityService();
        $lists = $service->getActivityList($filter, $page, $page_size, $sort);
        return $this->response->array($lists);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/items",
     *     summary="获取团长店铺商品列表",
     *     tags={"社区团"},
     *     description="获取团长店铺商品列表",
     *     operationId="getDisitrbutorItemList",
     *     @SWG\Parameter( name="page", in="query", description="page", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="page_size", required=false, type="integer"),
     *     @SWG\Parameter( name="distributor_id", in="query", description="店铺ID", required=false, type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="total_count", type="string", example="1"),
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items(
     *                         type="object",
     *                         @SWG\Property(property="item_id", type="integer", description="商品ID"),
     *                         @SWG\Property(property="item_type", type="string", description="商品类型"),
     *                         @SWG\Property(property="consume_type", type="string", description="核销类型"),
     *                         @SWG\Property(property="is_show_specimg", type="boolean", description="详情页是否显示规格图片"),
     *                         @SWG\Property(property="store", type="integer", description="商品库存"),
     *                         @SWG\Property(property="barcode", type="string", description="条形吗"),
     *                         @SWG\Property(property="sales", type="integer", description="销量"),
     *                         @SWG\Property(property="approve_status", type="string", description="商品状态"),
     *                         @SWG\Property(property="rebate", type="integer", description="推广商品 1已选择 0未选择 2申请加入 3拒绝"),
     *                         @SWG\Property(property="rebate_conf", type="string", description="分佣计算方式"),
     *                         @SWG\Property(property="cost_price", type="integer", description="成本价"),
     *                         @SWG\Property(property="is_point", type="boolean", description="是否积分兑换"),
     *                         @SWG\Property(property="point", type="integer", description="积分个数"),
     *                         @SWG\Property(property="item_source", type="string", description="品来源:mall:主商城，distributor:店铺自有"),
     *                         @SWG\Property(property="goods_id", type="integer", description="产品ID"),
     *                         @SWG\Property(property="item_name", type="string", description="商品名称"),
     *                         @SWG\Property(property="item_unit", type="string", description="商品计量单位"),
     *                         @SWG\Property(property="item_bn", type="string", description="商品编号"),
     *                         @SWG\Property(property="brief", type="string", description="简洁的描述"),
     *                         @SWG\Property(property="price", type="integer", description="销售价"),
     *                         @SWG\Property(property="market_price", type="integer", description="原价"),
     *                         @SWG\Property(property="special_type", type="string", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                         @SWG\Property(property="goods_function", type="string", description="商品功能"),
     *                         @SWG\Property(property="goods_series", type="string", description="商品系列"),
     *                         @SWG\Property(property="volume", type="number", description="商品体积"),
     *                         @SWG\Property(property="goods_color", type="string", description="商品颜色"),
     *                         @SWG\Property(property="goods_brand", type="string", description="商品品牌"),
     *                         @SWG\Property(property="item_address_province", type="string", description="产地省"),
     *                         @SWG\Property(property="item_address_city", type="string", description="产地市"),
     *                         @SWG\Property(property="regions_id", type="string", description="产地地区id"),
     *                      )
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getDisitrbutorItemList(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);

        //todo 当前只能绑定一个店铺
        $chiefDistributorService = new CommunityChiefDistributorService();
        $distributor = $chiefDistributorService->getInfo(['chief_id' => $authInfo['chief_id']]);
        if (empty($distributor)) {
            throw new ResourceException('当前团长没有配置店铺');
        }

        $filter = [
            'distributor_id' => $distributor['distributor_id'],
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
            'is_default' => true,
            'item_type' => 'normal',
            'company_id' => $authInfo['company_id'],
        ];

        $default_distributor_id = $request->input('distributor_id');
        if ($default_distributor_id) {
            $filter['distributor_id'] = $default_distributor_id;
        }

        $communityItemsService = new CommunityItemsService();
        $list = $communityItemsService->getItemsList($filter, $page, $page_size);
        // $itemService = new ItemsService();
        // $list = $itemService->getSkuItemsList($filter, $page, $page_size);

        return $this->response->array($list);
    }

    // 团长添加活动
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/activity",
     *     summary="团长添加活动",
     *     tags={"社区团"},
     *     description="团长添加活动",
     *     operationId="createActivity",
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="activity_pics", in="query", description="活动图片", required=false, type="string"),
     *     @SWG\Parameter( name="activity_desc", in="query", description="活动简介", required=false, type="string"),
     *     @SWG\Parameter( name="activity_intro", in="query", description="活动详情", required=false, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="activity_status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Parameter( name="items", in="query", description="选择的商品id数组[1,2,3]", required=false, type="string"),
     *     @SWG\Parameter( name="ziti", in="query", description="自提点ID", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/CommunityChiefActivity"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function createActivity(Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $params = $request->all();

        $companyId = app('auth')->user()->get('company_id');
        $params['company_id'] = $companyId;
        $params['chief_id'] = $authInfo['chief_id'];

        //todo 当前只能绑定一个店铺
        $chiefDistributorService = new CommunityChiefDistributorService();
        $distributor = $chiefDistributorService->getInfo(['chief_id' => $authInfo['chief_id']]);
        if (empty($distributor)) {
            throw new ResourceException('当前团长没有配置店铺');
        }
        $params['distributor_id'] = $distributor['distributor_id'];

        $rule = [
            'activity_name' => ['required|max:200', '活动名称必填，且最大长度不超过200个汉字'],
            'start_time' => ['required', '开始时间不能为空'],
            'end_time' => ['required', '结束时间不能为空'],
            'items' => ['required|array', '拼团商品不能为空'],
            'ziti' => ['required', '自提地点不能为空'],
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!empty($params['start_time'])) {
            $params['start_time'] = strtotime($params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $params['end_time'] = strtotime($params['end_time']);
        }
        if (!empty($params['items'])) {
            $items = [];
            foreach ($params['items'] as $value) {
                $items[] = [
                    'item_id' => $value,
                ];
            }
            $params['items'] = $items;
        }
        if (!empty($params['ziti'])) {
            $params['ziti'] = [
                ['ziti_id' => $params['ziti']]
            ];
        }

        $service = new CommunityActivityService();
        $result = $service->createActivity($params);

        return $this->response->array($result);
    }

    // 团长修改活动
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/activity/{activity_id}",
     *     summary="团长修改活动",
     *     tags={"社区团"},
     *     description="团长修改活动",
     *     operationId="updateActivity",
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=true, type="string"),
     *     @SWG\Parameter( name="activity_pics", in="query", description="活动图片", required=false, type="string"),
     *     @SWG\Parameter( name="activity_desc", in="query", description="活动简介", required=false, type="string"),
     *     @SWG\Parameter( name="activity_intro", in="query", description="活动详情", required=false, type="string"),
     *     @SWG\Parameter( name="start_time", in="query", description="开始时间", required=true, type="string"),
     *     @SWG\Parameter( name="end_time", in="query", description="结束时间", required=true, type="string"),
     *     @SWG\Parameter( name="activity_status", in="query", description="状态", required=false, type="string"),
     *     @SWG\Parameter( name="items", in="query", description="选择的商品id数组[1,2,3]", required=false, type="string"),
     *     @SWG\Parameter( name="ziti", in="query", description="自提点ID", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/CommunityChiefActivity"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function updateActivity(Request $request, $activity_id)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        // 获取活动信息
        $service = new CommunityActivityService();
        $activity_info = $service->getInfo(['activity_id' => $activity_id]);
        if (empty($activity_info)) {
            throw new ResourceException('无效的活动');
        }
        if ($activity_info['chief_id'] != $authInfo['chief_id']) {
            throw new ResourceException('只能修改自己的拼团活动');
        }

        $params = $request->all();
        $rule = [
            'activity_name' => ['required|max:200', '活动名称必填，且最大长度不超过200个汉字'],
            'start_time' => ['required', '开始时间不能为空'],
            'end_time' => ['required', '结束时间不能为空'],
            'items' => ['required', '拼团商品不能为空'],
            'ziti' => ['required', '自提地点不能为空'],
        ];
        $error = validator_params($params, $rule);
        if ($error) {
            throw new ResourceException($error);
        }

        if (!empty($params['start_time'])) {
            $params['start_time'] = strtotime($params['start_time']);
        }
        if (!empty($params['end_time'])) {
            $params['end_time'] = strtotime($params['end_time']);
        }
        if (!empty($params['items'])) {
            $items = [];
            foreach ($params['items'] as $value) {
                $items[] = [
                    'item_id' => $value,
                ];
            }
            $params['items'] = $items;
        }
        if (!empty($params['ziti'])) {
            $params['ziti'] = [
                ['ziti_id' => $params['ziti']]
            ];
        }

        $result = $service->updateActivity($activity_id, $params);

        return $this->response->array($result);
    }

    // 团长修改活动状态
    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/activity_status/{activity_id}",
     *     summary="团长修改活动状态",
     *     tags={"社区团"},
     *     description="团长修改活动状态",
     *     operationId="updateActivityStatus",
     *     @SWG\Parameter( name="activity_status", in="query", description="状态 public已发布 protected已暂停 success确认成团 fail成团失败", required=false, type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     ref="#/definitions/CommunityChiefActivity"
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function updateActivityStatus(Request $request, $activity_id)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        // 获取活动信息
        $service = new CommunityActivityService();
        $activityInfo = $service->getInfo(['activity_id' => $activity_id]);
        if (empty($activityInfo)) {
            throw new ResourceException('无效的活动');
        }
        if ($activityInfo['chief_id'] != $authInfo['chief_id']) {
            throw new ResourceException('只能修改自己的拼团活动');
        }

        $activity_status = $request->input('activity_status');

        if (!in_array($activity_status, array_keys(CommunityActivityService::activity_status))) {
            throw new ResourceException('活动状态错误');
        }

        if ($activity_status == 'success') {
            $conn = app('registry')->getConnection('default');
            $criteria = $conn->createQueryBuilder();
            $itemList = $criteria->select('i.goods_id,sum(oi.num) as buy_num,sum(oi.total_fee) as total_fee,min(ci.min_delivery_num) as min_delivery_num,min(i.item_name) as item_name')
                ->from('orders_normal_orders_items', 'oi')
                ->leftJoin('oi', 'orders_normal_orders', 'o', 'oi.order_id = o.order_id')
                ->leftJoin('oi', 'items', 'i', 'oi.item_id = i.item_id')
                ->leftJoin('i', 'community_items', 'ci', 'i.goods_id = ci.goods_id')
                ->andWhere($criteria->expr()->eq('o.order_type', $criteria->expr()->literal('normal')))
                ->andWhere($criteria->expr()->eq('o.order_class', $criteria->expr()->literal('community')))
                ->andWhere($criteria->expr()->eq('o.act_id', $activity_id))
                ->andWhere($criteria->expr()->eq('o.order_status', $criteria->expr()->literal('PAYED')))
                ->andWhere(
                    $criteria->expr()->orX(
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('NO_APPLY_CANCEL')),
                        $criteria->expr()->eq('o.cancel_status', $criteria->expr()->literal('FAILS'))
                    )
                )->groupBy('i.goods_id')->execute()->fetchAll();
            if (!$itemList) {
                throw new ResourceException('没有商品被购买，不能成团');
            }

            $settingService = new CommunitySettingService($activityInfo['company_id'], $activityInfo['distributor_id']);
            $setting = $settingService->getSetting();
            if ($setting['condition_type'] == 'money') {
                if ($setting['condition_money'] > 0) {
                    $totalFee = array_reduce($itemList, function($totalFee, $item) {
                        $totalFee += $item['total_fee'];
                        return $totalFee;
                    });
                    if ($totalFee < bcmul($setting['condition_money'], 100)) {
                        throw new ResourceException('未达最低成团金额，不能成团');
                    }
                }
            } else {
                foreach ($itemList as $item) {
                    if ($item['min_delivery_num'] > 0 && $item['buy_num'] < $item['min_delivery_num']) {
                        throw new ResourceException($item['item_name'].'未达起送量，不能成团');
                    }
                }
            }
        }

        // 修改活动状态
        $result = $service->updateActivityStatus($activity_id, $activity_status, $authInfo['chief_id']);

        return $this->response->array($result);
    }

    /**
     * @SWG\Post(
     *     path="/wxapp/community/chief/confirm_delivery/{activity_id}",
     *     summary="团长确认收货",
     *     tags={"社区团"},
     *     description="团长确认收货",
     *     operationId="confirmDeliveryStatus",
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function confirmDeliveryStatus(Request $request, $activity_id)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $service = new CommunityActivityService();
        $result = $service->chiefConfirmDelivery($authInfo['chief_id'], $activity_id);

        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/community/chief/activity/{activity_id}",
     *     summary="团长获取活动详情",
     *     tags={"社区团"},
     *     description="团长获取活动详情",
     *     operationId="getActivityDetail",
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                          ref="#/definitions/CommunityChiefActivity"
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CommunityErrorResponse") ) )
     * )
     */
    public function getActivityDetail($activity_id, Request $request)
    {
        $authInfo = $request->get('auth');
        if (!$authInfo['chief_id']) {
            throw new ResourceException('只有团长才能操作');
        }

        $filter['activity_id'] = $activity_id;

        $service = new CommunityActivityService();
        $result = $service->getActivity($filter);

        if (empty($result)) {
            return $this->response->array($result);
        }

        $chiefDistributorService = new CommunityChiefDistributorService();
        $distributorData = $chiefDistributorService->getInfo(['chief_id' => $result['chief_id']]);
        $result['distributor_id'] = $distributorData['distributor_id'] ?? 0;

        $result['is_activity_author'] = $result['chief_id'] == $authInfo['chief_id'];

        return $this->response->array($result);
    }
}
