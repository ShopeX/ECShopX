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

namespace CommunityBundle\Http\FrontApi\V1\Action\member;

use App\Http\Controllers\Controller as BaseController;
use CommunityBundle\Services\CommunityActivityService;
use CommunityBundle\Services\CommunityChiefDistributorService;
use Dingo\Api\Exception\ResourceException;
use GoodsBundle\Services\ItemsService;
use Illuminate\Http\Request;
use CommunityBundle\Services\CommunityItemsService;

class CommunityActivity extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/community/member/activity",
     *     summary="个人中心活动列表",
     *     tags={"社区团"},
     *     description="个人中心活动列表",
     *     operationId="getActivityList",
     *     @SWG\Parameter( name="page", in="query", description="page", required=false, type="integer"),
     *     @SWG\Parameter( name="page_size", in="query", description="page_size", required=false, type="integer"),
     *     @SWG\Parameter( name="activity_name", in="query", description="活动名称", required=false, type="string"),
     *     @SWG\Parameter( name="tab_status", in="query", description="状态 all全部 waiting未开始 end已结束 running进行中", required=false, type="string"),
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

        $filter = [];

        $activityStatus = $request->input('activity_status');
        if ($activityStatus) {
            $filter['activity_status'] = $activityStatus;
        }

        $tab_status = $request->input('tab_status', 'all');
        switch ($tab_status) {
            case 'all': // 全部
                $filter['activity_status'] = ['public', 'success', 'fail'];
                break;
            case 'waiting': // 未开始
                $filter['activity_status'] = 'public';
                $filter['start_time|gt'] = time();
                break;
            case 'end': // 已结束
                $filter['activity_status'] = ['public', 'success', 'fail'];
                $filter['end_time|lt'] = time();
                break;
            case 'running': // 进行中
                $filter['activity_status'] = 'public';
                $filter['start_time|lte'] = time();
                $filter['end_time|gte'] = time();
                break;
        }
        $authInfo = $request->get('auth');
        if (!empty($authInfo['chief_id'])) {
            $filter['chief_id'] = $authInfo['chief_id'];
        }

        $activity_name = $request->input('activity_name');
        if ($activity_name) {
            $filter['activity_name|contains'] = $activity_name;
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
     *     path="/wxapp/community/member/activity/{activity_id}",
     *     summary="消费者获取活动详情",
     *     tags={"社区团"},
     *     description="消费者获取活动详情",
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
        $filter['activity_id'] = $activity_id;

        $service = new CommunityActivityService();
        $result = $service->getActivity($filter);

        if (empty($result)) {
            return $this->response->array($result);
        }

        $chiefDistributorService = new CommunityChiefDistributorService();
        $distributorData = $chiefDistributorService->getInfo(['chief_id' => $result['chief_id']]);
        $result['distributor_id'] = $distributorData['distributor_id'] ?? 0;

        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/community/member/items",
     *     summary="消费者获取团长的店铺商品列表",
     *     tags={"社区团"},
     *     description="消费者获取团长的店铺商品列表",
     *     operationId="getDisitrbutorItemList",
     *     @SWG\Parameter( name="chief_id", in="query", description="团长ID", required=true, type="integer"),
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
        // This module is part of ShopEx EcShopX system
        $chief_id = $request->input('chief_id');
        if (!$chief_id) {
            throw new ResourceException('必须选择活动的团长信息');
        }
        $page = $request->input('page', 1);
        $page_size = $request->input('page_size', 20);

        $chiefDistributorService = new CommunityChiefDistributorService();
        $distributorList = $chiefDistributorService->getLists(['chief_id' => $chief_id]);
        $distributor_ids = array_values(array_unique(array_column($distributorList, 'distributor_id')));

        if (empty($distributor_ids)) {
            throw new ResourceException('当前团长没有配置店铺');
        }

        $filter = [
            'distributor_id' => $distributor_ids,
            'approve_status' => 'onsale',
            'audit_status' => 'approved',
            'is_default' => true,
            'item_type' => 'normal',
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
}
