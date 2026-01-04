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

namespace PromotionsBundle\Http\FrontApi\V1\Action;

use App\Http\Controllers\Controller as Controller;
use CompanysBundle\Traits\GetDefaultCur;
use Dingo\Api\Exception\ResourceException;
use Illuminate\Http\Request;
use PromotionsBundle\Services\MarketingActivityService;
use GoodsBundle\Services\ItemsService;
use PromotionsBundle\Entities\MarketingGiftItems;
use OrdersBundle\Services\CartService;

class MarketingActivity extends Controller
{
    use GetDefaultCur;

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/fullpromotion/getlist",
     *     summary="获取商品促销活动列表(废弃)",
     *     tags={"营销"},
     *     description="获取商品促销活动列表",
     *     operationId="getMarketingActivitysList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="status", in="query", description="活动状态", type="string"),
     *     @SWG\Parameter( name="item_type", in="query", description="商品类型 normal or services", type="string"),
     *     @SWG\Parameter( name="marketing_type", in="query", description="商品类型 normal or services", type="string"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
     *         response=200,
     *         description="成功返回结构",
     *         @SWG\Schema(
     *             @SWG\Property(
     *                 property="data",
     *                 type="array",
     *                 @SWG\Items(
     *                     type="object",
     *                     @SWG\Property(property="marketing_id", type="integer"),
     *                     @SWG\Property(property="company_id", type="integer", example="1"),
     *                     @SWG\Property(property="marketing_name", type="integer", example="1"),
     *                     @SWG\Property(property="marketing_desc", type="integer", example="1"),
     *                     @SWG\Property(property="start_time", type="string", example="1"),
     *                     @SWG\Property(property="end_time", type="string", example="1"),
     *                     @SWG\Property(
     *                          property="items",
     *                          type="array",
     *                          @SWG\Items(
     *                               type="object",
     *                               @SWG\Property(property="marketing_id", type="integer"),
     *                               @SWG\Property(property="item_id", type="integer", example="1"),
     *                               @SWG\Property(property="item_title", type="string", example="1"),
     *                               @SWG\Property(property="price", type="integer", example="1"),
     *                               @SWG\Property(property="company_id", type="integer", example="1"),
     *                               @SWG\Property(property="pics", type="integer", example="1"),
     *                               @SWG\Property(property="id", type="integer", example="1"),
     *                               @SWG\Property(property="item_rel_id", type="integer", example="1"),
     *                               @SWG\Property(property="marketing_type", type="integer", example="1"),
     *                               @SWG\Property(property="item_type", type="integer", example="1"),
     *                               @SWG\Property(property="item_brief", type="integer", example="1"),
     *                               @SWG\Property(property="promotion_tag", type="integer", example="1"),
     *                               @SWG\Property(property="start_time", type="integer", example="1"),
     *                               @SWG\Property(property="end_time", type="integer", example="1"),
     *                          )
     *                     ),
     *                     @SWG\Property(property="used_platform", type="string", example="1"),
     *                     @SWG\Property(property="use_bound", type="string", example="1"),
     *                     @SWG\Property(property="use_shop", type="string", example="1"),
     *                     @SWG\Property(property="shop_ids", type="string", example="1"),
     *                     @SWG\Property(property="valid_grade", type="string", example="1"),
     *                     @SWG\Property(property="condition_type", type="string", example="1"),
     *                     @SWG\Property(property="marketing_type", type="string", example="1"),
     *                     @SWG\Property(property="condition_value", type="string", example="1"),
     *                     @SWG\Property(property="promotion_tag", type="string", example="1"),
     *                     @SWG\Property(property="free_postage", type="string", example="1"),
     *                     @SWG\Property(property="join_limit", type="string", example="1"),
     *                     @SWG\Property(property="created", type="string", example="1"),
     *                     @SWG\Property(property="updated", type="string", example="1"),
     *                 )
     *             ),
     *          ),
     *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getMarketingActivitysList(Request $request)
    {
        $authUser = $request->get('auth');
        $filter['company_id'] = $authUser['company_id'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['start_time' => 'desc'];
        if ($request->input('marketing_type')) {
            $filter['marketing_type'] = $request->input('marketing_type');
        }
        if ($request->input('item_type')) {
            $filter['item_type'] = $request->input('item_type');
        }
        $status = $request->input('status', 'ongoing');
        switch ($status) {
            case "waiting":
                $filter['start_time|gte'] = time();
                $filter['end_time|gte'] = time();
                break;
            case "ongoing":
                $filter['start_time|lte'] = time();
                $filter['end_time|gte'] = time();
                break;
            case "end":
                $filter['start_time|lte'] = time();
                $filter['end_time|lte'] = time();
                break;
        }
        $marketingService = new MarketingActivityService();
        $result = $marketingService->getMarketingLists($filter, $page, $pageSize, $orderBy, false);
        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }


    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/fullpromotion/getitemlist",
     *     summary="获取促销商品列表",
     *     tags={"营销"},
     *     description="获取促销商品列表",
     *     operationId="getMarketingActivityItemsList",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="marketing_id", in="query", description="活动id", type="integer"),
     *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
     *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
     *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(
      *                    property="activity",
      *                    type="object",
      *                    @SWG\Property( property="marketing_id", type="string", example="179", description="促销id"),
     *          @SWG\Property( property="marketing_type", type="string", example="plus_price_buy", description="营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购 | 营销类型: full_discount:满折,full_minus:满减,full_gift:满赠"),
     *                     @SWG\Property( property="rel_marketing_id", type="string", example="0", description="关联其他营销id"),
     *                     @SWG\Property( property="marketing_name", type="string", example="加价", description="营销活动名称"),
     *                     @SWG\Property( property="marketing_desc", type="string", example="男女通用 AHC B5玻尿酸臻致补水护肤套装 经典款 化妆水", description="营销活动描述"),
     *                     @SWG\Property( property="start_time", type="string", example="1610640000", description="活动开始时间"),
     *                     @SWG\Property( property="end_time", type="string", example="1614355199", description="活动结束时间"),
     *                     @SWG\Property( property="release_time", type="string", example="null", description="活动发布时间"),
     *                     @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端"),
     *                     @SWG\Property( property="use_bound", type="string", example="4", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                     @SWG\Property( property="use_shop", type="string", example="0", description="适用店铺: 0:全场可用,1:指定店铺可用"),
     *                     @SWG\Property( property="shop_ids", type="string", example="null", description="店铺id集合"),
     *                     @SWG\Property( property="valid_grade", type="string", example="", description="会员级别集合,id数组"),
     *                     @SWG\Property( property="condition_type", type="string", example="totalfee", description="营销条件标准 quantity:按总件数, totalfee:按总金额"),
     *                     @SWG\Property( property="condition_value", type="string", example="", description="营销规则值"),
     *                     @SWG\Property( property="canjoin_repeat", type="string", example="0", description="是否上不封顶"),
     *                     @SWG\Property( property="join_limit", type="string", example="11", description="可参与次数"),
     *                     @SWG\Property( property="free_postage", type="string", example="0", description="是否免邮"),
     *                     @SWG\Property( property="promotion_tag", type="string", example="加价购", description="促销标签"),
     *                     @SWG\Property( property="check_status", type="string", example="agree", description="促销状态: non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
     *                     @SWG\Property( property="reason", type="string", example="null", description="拒绝原因"),
     *                     @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                     @SWG\Property( property="is_increase_purchase", type="string", example="null", description="开启加价购，满赠时启用"),
     *                     @SWG\Property( property="created", type="string", example="1610698338", description=""),
     *                     @SWG\Property( property="updated", type="string", example="1611563204", description=""),
     *                     @SWG\Property( property="ad_pic", type="string", example="null", description="活动广告图"),
     *                     @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                     @SWG\Property( property="brand_ids", type="string", example="", description="品牌id集合"),
     *                     @SWG\Property( property="in_proportion", type="string", example="0", description="是否按比例多次赠送"),
     *                     @SWG\Property( property="left_time", type="string", example="2716581", description="自行更改字段描述"),
     *                  ),
      *                 @SWG\Property(
      *                    property="list",
      *                    type="array",
      *                    @SWG\Items(
      *                        ref="#/definitions/GoodsList"
      *                    )
      *                 ),
      *
      *                 @SWG\Property(
      *                    property="cur",
      *                    type="object",
      *                    ref="#/definitions/Cur"
      *                 ),
      *                 @SWG\Property(property="total_count", type="string", example="2", description="总条数"),
      *
      *             ),
      *          ),
      *     ),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getMarketingActivityItemsList(Request $request)
    {
        if (!$request->input('marketing_id')) {
            throw new ResourceException(trans('PromotionsBundle.activity_expired'));
        }
        $authUser = $request->get('auth');
        $filter['company_id'] = $authUser['company_id'];
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['item_id' => 'desc'];
        $filter['marketing_id'] = $request->input('marketing_id');
        $marketingService = new MarketingActivityService();
        $filter['is_show'] = true;
        $result = $marketingService->getActivityItemList($filter, $page, $pageSize, $orderBy);
        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }

    /**
     * @SWG\Get(
     *     path="/wxapp/promotion/getskumarketing",
     *     summary="获取指定商品的活动信息",
     *     tags={"营销"},
     *     description="获取指定商品的活动信息",
     *     operationId="getValidMarketingActivityByItemId",
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
     *     @SWG\Parameter( name="item_id", in="query", description="商品id", type="integer"),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="promotion_activity", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="marketing_id", type="string", example="180", description="营销id"),
     *                          @SWG\Property( property="marketing_type", type="string", example="full_minus", description="营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购"),
     *                          @SWG\Property( property="rel_marketing_id", type="string", example="0", description="关联其他营销id"),
     *                          @SWG\Property( property="marketing_name", type="string", example="测试APP满减", description="营销活动名称"),
     *                          @SWG\Property( property="marketing_desc", type="string", example="测试APP满减", description="营销活动描述"),
     *                          @SWG\Property( property="start_time", type="string", example="1611504000", description="活动开始时间"),
     *                          @SWG\Property( property="end_time", type="string", example="1611849599", description="活动结束时间"),
     *                          @SWG\Property( property="release_time", type="string", example="null", description="活动发布时间"),
     *                          @SWG\Property( property="used_platform", type="string", example="0", description="适用平台:  0:全场可用,1:只用于pc端,2:小程序端,3:h5端"),
     *                          @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                          @SWG\Property( property="use_shop", type="string", example="0", description="适用店铺: 0:全场可用,1:指定店铺可用"),
     *                          @SWG\Property( property="shop_ids", type="array",
     *                              @SWG\Items( type="string", example="", description="店铺id"),
     *                          ),
     *                          @SWG\Property( property="valid_grade", type="array",
     *                              @SWG\Items( type="string", example="4", description="等级id"),
     *                          ),
     *                          @SWG\Property( property="condition_type", type="string", example="quantity", description="营销条件标准 quantity:按总件数, totalfee:按总金额"),
     *                          @SWG\Property( property="condition_value", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="full", type="string", example="2", description=""),
     *                                  @SWG\Property( property="minus", type="string", example="1", description=""),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="canjoin_repeat", type="string", example="0", description="是否上不封顶"),
     *                          @SWG\Property( property="join_limit", type="string", example="1", description="可参与次数"),
     *                          @SWG\Property( property="free_postage", type="string", example="0", description="是否免邮"),
     *                          @SWG\Property( property="promotion_tag", type="string", example="满减", description="促销标签"),
     *                          @SWG\Property( property="check_status", type="string", example="agree", description="促销状态: non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
     *                          @SWG\Property( property="reason", type="string", example="null", description="审核拒绝原因"),
     *                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                          @SWG\Property( property="is_increase_purchase", type="string", example="null", description="开启加价购，满赠时启用"),
     *                          @SWG\Property( property="created", type="string", example="1611558498", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1611558498", description=""),
     *                          @SWG\Property( property="ad_pic", type="string", example="null", description="活动广告图"),
     *                          @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                          @SWG\Property( property="brand_ids", type="string", example="null", description="品牌id集合"),
     *                          @SWG\Property( property="in_proportion", type="string", example="0", description="是否按比例多次赠送"),
     *                          @SWG\Property( property="member_grade", type="object",
     *                                  @SWG\Property( property="4", type="string", example="一般付费", description="会员等级名称"),
     *                          ),
     *                          @SWG\Property( property="items", type="object",
     *                                  @SWG\Property( property="5357", type="object",
     *                                          @SWG\Property( property="marketing_id", type="string", example="180", description="营销id"),
     *                                          @SWG\Property( property="item_id", type="string", example="5357", description="商品id"),
     *                                          @SWG\Property( property="is_show", type="string", example="1", description="列表页是否显示"),
     *                                          @SWG\Property( property="item_spec_desc", type="string", example="null", description="商品规格描述"),
     *                                          @SWG\Property( property="marketing_type", type="string", example="full_minus", description="营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购"),
     *                                          @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                                          @SWG\Property( property="item_name", type="string", example="测试5", description="商品名称"),
     *                                          @SWG\Property( property="price", type="string", example="10", description="销售金额,单位为‘分’"),
     *                                          @SWG\Property( property="item_brief", type="string", example="null", description="商品简介"),
     *                                          @SWG\Property( property="pics", type="string", example="", description="活动封面json"),
     *                                          @SWG\Property( property="promotion_tag", type="string", example="满减", description="促销标签"),
     *                                          @SWG\Property( property="start_time", type="string", example="1611504000", description="活动开始时间"),
     *                                          @SWG\Property( property="end_time", type="string", example="1611849599", description="活动结束时间"),
     *                                          @SWG\Property( property="status", type="string", example="1", description="是否生效中"),
     *                                          @SWG\Property( property="created", type="string", example="1611558498", description=""),
     *                                          @SWG\Property( property="updated", type="string", example="1611558498", description=""),
     *                                          @SWG\Property( property="goods_id", type="string", example="5357", description="关联商品id"),
     *                                  ),
     *                          ),
     *                          @SWG\Property( property="condition_rules", type="string", example="购买满2件，减1元;", description="活动规则描述"),
     *                          @SWG\Property( property="start_date", type="string", example="2021-01-25 00:00:00", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2021-01-28 23:59:59", description="有效期结束时间"),
     *                          @SWG\Property( property="status", type="string", example="ongoing", description="状态 ongoing:进行中"),
     *                          @SWG\Property( property="last_seconds", type="string", example="202084", description="剩余秒数"),
     *                       ),
     *                  ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
     * )
     */
    public function getValidMarketingActivityByItemId(Request $request)
    {
        //获取包含该商品的满折满减营销活动
        $item_id = $request->get('item_id');
        $authUser = $request->get('auth');
        $userId = 0;
        if (isset($authUser['user_id']) && $authUser['user_id']) {
            $userId = $authUser['user_id'];
        }
        $company_id = $authUser['company_id'];

        //获取当前商品对应的店铺ID
        $filter = [
            'company_id' => $company_id,
            'item_id' => $item_id,
        ];
        $itemService = new ItemsService();
        $itemInfo = $itemService->getItem($filter);
        $distributorId = $itemInfo['distributor_id'] ?? 0;

        //促销活动按店铺区分，店铺和平台的活动互相独立
        $marketingService = new MarketingActivityService();
        $marketingActivity = $marketingService->getValidMarketingActivity($company_id, $item_id, $userId, null, $distributorId);
        $result['promotion_activity'] = $marketingActivity;
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/wxapp/promotion/pluspricebuy/getItemList",
      *     summary="获取加价购商品列表",
      *     tags={"营销"},
      *     description="获取促销商品列表",
      *     operationId="getPlusPriceBuyItem",
      *     @SWG\Parameter( name="x-wxapp-session", in="header", description="登录token(小程序端必填)", type="string"),
     *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token(h5端必填)", type="string"),
      *     @SWG\Parameter( name="marketing_id", in="query", description="活动id", type="integer"),
      *     @SWG\Parameter( name="page", in="query", description="页码", type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页数量", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(
      *                    property="promotion_activity",
      *                    type="object",
      *                    @SWG\Property( property="marketing_id", type="string", example="179", description="关联营销id"),
     *          @SWG\Property( property="marketing_type", type="string", example="plus_price_buy", description="营销类型: full_discount:满折,full_minus:满减,full_gift:满赠,self_select:任选优惠,plus_price_buy:加价购"),
     *                     @SWG\Property( property="rel_marketing_id", type="string", example="0", description="关联其他营销id"),
     *                     @SWG\Property( property="marketing_name", type="string", example="加价", description="营销活动名称"),
     *                     @SWG\Property( property="marketing_desc", type="string", example="男女通用 AHC B5玻尿酸臻致补水护肤套装 经典款 化妆水", description="营销活动描述"),
     *                     @SWG\Property( property="start_time", type="string", example="1610640000", description="活动开始时间"),
     *                     @SWG\Property( property="end_time", type="string", example="1614355199", description="活动结束时间"),
     *                     @SWG\Property( property="release_time", type="string", example="null", description="活动发布时间"),
     *                     @SWG\Property( property="used_platform", type="string", example="0", description="适用平台: 0:全场可用,1:只用于pc端,2:小程序端,3:h5端"),
     *                     @SWG\Property( property="use_bound", type="string", example="4", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                     @SWG\Property( property="use_shop", type="string", example="0", description="适用店铺: 0:全场可用,1:指定店铺可用"),
     *                     @SWG\Property( property="shop_ids", type="string", example="null", description="员工管理的门店id集合 | 店铺id集合"),
     *                     @SWG\Property( property="valid_grade", type="string", example="", description="会员级别集合,id数组"),
     *                     @SWG\Property( property="condition_type", type="string", example="totalfee", description="营销条件标准 quantity:按总件数, totalfee:按总金额"),
     *                     @SWG\Property( property="condition_value", type="string", example="", description="营销规则值"),
     *                     @SWG\Property( property="canjoin_repeat", type="string", example="0", description="是否上不封顶"),
     *                     @SWG\Property( property="join_limit", type="string", example="11", description="可参与次数"),
     *                     @SWG\Property( property="free_postage", type="string", example="0", description="是否免邮"),
     *                     @SWG\Property( property="promotion_tag", type="string", example="加价购", description="促销标签"),
     *                     @SWG\Property( property="check_status", type="string", example="agree", description="促销状态: non-reviewed:未审核,pending:待审核,agree:审核通过,refuse:已拒绝,cancel:已取消,overdue:已过期"),
     *                     @SWG\Property( property="reason", type="string", example="null", description="拒绝原因"),
     *                     @SWG\Property( property="activity_background", type="string", example="null", description="加价购活动页面背景"),
     *                     @SWG\Property( property="navbar_color", type="string", example="null", description="加价购活动页面导航栏颜色"),
     *                     @SWG\Property( property="timeBackgroundColor", type="string", example="null", description="加价购活动页面时间背景颜色"),
     *                     @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                     @SWG\Property( property="is_increase_purchase", type="string", example="null", description="开启加价购，满赠时启用"),
     *                     @SWG\Property( property="created", type="string", example="1610698338", description=""),
     *                     @SWG\Property( property="updated", type="string", example="1611563204", description=""),
     *                     @SWG\Property( property="ad_pic", type="string", example="null", description="活动广告图"),
     *                     @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                     @SWG\Property( property="brand_ids", type="string", example="", description="品牌id集合"),
     *                     @SWG\Property( property="in_proportion", type="string", example="0", description="是否按比例多次赠送"),
     *                     @SWG\Property( property="left_time", type="string", example="2716581", description="自行更改字段描述"),
     *                  ),
      *                 @SWG\Property(
      *                    property="list",
      *                    type="array",
      *                    @SWG\Items(
      *                        ref="#/definitions/GoodsList"
      *                    )
      *                 ),
      *
      *                 @SWG\Property(
      *                    property="cur",
      *                    type="object",
      *                    ref="#/definitions/Cur"
      *                 ),
      *                 @SWG\Property(property="total_count", type="string", example="2", description="总条数"),
      *
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getPlusPriceBuyItem(Request $request)
    {
        $rules = [
            'marketing_id' => ['required|integer|min:1', trans('PromotionsBundle.marketing_activity_not_exist')],
        ];
        $postdata = $request->input();
        $errorMessage = validator_params($postdata, $rules);
        if ($errorMessage) {
            return $this->response->array([]);
        }
        $authUser = $request->get('auth');
        $filter['company_id'] = $authUser['company_id'];
        $filter['marketing_id'] = $postdata['marketing_id'];

        $marketingService = new MarketingActivityService();
        $marketingActivity = $marketingService->getValidActivitys($filter['company_id'], $filter['marketing_id']);
        if (!$marketingActivity || $marketingActivity[0]['marketing_type'] != 'plus_price_buy') {
            throw new ResourceException(trans('PromotionsBundle.activity_not_exist'));
        }

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $entityGiftRelRepository = app('registry')->getManager('default')->getRepository(MarketingGiftItems::class);
        $result = $entityGiftRelRepository->lists($filter, $page, $pageSize);

        $itemIds = array_column($result['list'], 'item_id');
        $itemService = new ItemsService();
        $itemFilter = ['company_id' => $filter['company_id'], 'item_id' => $itemIds];
        $itemsList = $itemService->getSkuItemsList($itemFilter);
        $itemdata = array_column($itemsList['list'], null, 'item_id');

        $cartService = new CartService();
        $checked_id = $cartService->getPlusBuyCart($filter['company_id'], $authUser['user_id'], $filter['marketing_id']);

        foreach ($result['list'] as &$value) {
            if ($checked_id && $value['item_id'] == $checked_id) {
                $value['is_checked'] = true;
            }
            if ($itemdata[$value['item_id']] ?? []) {
                $value['plus_price'] = $value['price'];
                $value = array_merge($value, $itemdata[$value['item_id']]);
            }
        }

        $result['promotion_activity'] = $marketingActivity[0];
        // 活动剩余时间
        $result['promotion_activity']['left_time'] = $result['promotion_activity']['end_time'] - time();
        $result['cur'] = $this->getCur($filter['company_id']);
        return $this->response->array($result);
    }
}
