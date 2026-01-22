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

use GoodsBundle\Services\ItemsCategoryService;
use GoodsBundle\Services\ItemsTagsService;
use GoodsBundle\Services\ItemStoreService;
use Illuminate\Http\Request;
use Dingo\Api\Exception\ResourceException;
use App\Http\Controllers\Controller as Controller;

use PromotionsBundle\Services\PromotionSeckillActivityService;
use GoodsBundle\Services\ItemsService;
use DistributionBundle\Services\DistributorService;
use PromotionsBundle\Traits\CheckMarketingRulesParams;

// use PromotionsBundle\Traits\CheckPromotionsValid;

class SeckillActivity extends Controller
{
    // use CheckPromotionsValid;
    use CheckMarketingRulesParams;
    public $service;

    public function __construct()
    {
        $this->service = new PromotionSeckillActivityService();
    }

    /**
     * @SWG\Definition(
     * definition="SeckillBase",
     * type="object",
     * @SWG\Property( property="seckill_id", type="string", example="301", description="秒杀活动id"),
     * @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     * @SWG\Property( property="activity_name", type="string", example="4444", description="秒杀活动名称"),
     * @SWG\Property( property="distributor_id", type="string", example="", description="店铺id"),
     * @SWG\Property( property="seckill_type", type="string", example="normal", description="秒杀类型 normal正常的秒杀活动， limited_time_sale限时特惠"),
     * @SWG\Property( property="limit_total_money", type="string", example="", description="每人累计限额"),
     * @SWG\Property( property="otherext", type="string", example="", description="其他扩展字段"),
     * @SWG\Property( property="limit_money", type="string", example="", description="每人单笔限额"),
     * @SWG\Property( property="ad_pic", type="string", example="http://b-img-cdn.yuanyuanke.cn/image/1/2020/10/27/29c57ca983c4c37b59b5a2ba68d6cecaJwqf2uUBnaQaHCBzv3fjf88GwRPRlBYP", description="秒杀活动广告图"),
     * @SWG\Property( property="activity_start_time", type="string", example="1608739200", description="秒杀开始时间"),
     * @SWG\Property( property="activity_end_time", type="string", example="1608911999", description="秒杀结束时间"),
     * @SWG\Property( property="activity_release_time", type="string", example="1608652800", description="秒杀活动发布时间"),
     * @SWG\Property( property="is_activity_rebate", type="string", example="false", description="秒杀活动是否返佣"),
     * @SWG\Property( property="is_free_shipping", type="string", example="false", description="秒杀活动是否包邮"),
     * @SWG\Property( property="validity_period", type="string", example="15", description="未付款订单保留时长（分钟）"),
     * @SWG\Property( property="description", type="string", example="null", description="秒杀活动描述"),
     * @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     * @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     * @SWG\Property( property="created", type="string", example="1608608236", description=""),
     * @SWG\Property( property="updated", type="string", example="1608608658", description=""),
     * @SWG\Property( property="activity_start_date", type="string", example="2020-12-24 00:00:00", description="活动开始日期"),
     * @SWG\Property( property="activity_end_date", type="string", example="2020-12-25 23:59:59", description="活动结束日期"),
     * @SWG\Property( property="activity_release_date", type="string", example="2020-12-23 00:00:00", description="活动预告日期"),
     * @SWG\Property( property="created_date", type="string", example="2020-12-22 11:37:16", description="创建日期"),
     * @SWG\Property( property="updated_date", type="string", example="2020-12-22 11:44:18", description="更新日期"),
     * @SWG\Property( property="status", type="string", example="it_has_ended", description="活动状态  waiting:未开始 in_the_notice:预告中 in_sale:售卖中 it_has_ended:已结束"),
     *
     * )
     */

    /**
     * @SWG\Definition(
     * definition="SeckillItems",
     * type="object",
     * @SWG\Property( property="seckill_id", type="string", example="300", description="秒杀活动id"),
     * @SWG\Property( property="item_id", type="string", example="5025", description="关联货品id"),
     * @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     * @SWG\Property( property="seckill_type", type="string", example="normal", description="秒杀类型 normal正常的秒杀活动， limited_time_sale限时特惠"),
     * @SWG\Property( property="item_title", type="string", example="123", description="秒杀活动商品名称"),
     * @SWG\Property( property="activity_price", type="string", example="12300", description="秒杀活动价格(分)"),
     * @SWG\Property( property="activity_store", type="string", example="12", description="秒杀活动库存"),
     * @SWG\Property( property="activity_start_time", type="string", example="1608652800", description="秒杀开始时间"),
     * @SWG\Property( property="activity_end_time", type="string", example="1608911999", description="秒杀结束时间"),
     * @SWG\Property( property="activity_release_time", type="string", example="1608652800", description="秒杀活动发布时间"),
     * @SWG\Property( property="sales_store", type="string", example="0", description="已购买库存"),
     * @SWG\Property( property="limit_num", type="string", example="1", description="秒杀活动限购"),
     * @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     * @SWG\Property( property="item_pic", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdYj0vHb58T6H7GkDhEoPMnzWy1w7MCzdBZKtc1ziac7tdUx4saPbIcrhaPoh6ibPt05yolY851Ds4A/0?wx_fmt=jpeg", description="秒杀活动商品图片"),
     * @SWG\Property( property="sort", type="string", example="0", description="商品排序"),
     * @SWG\Property( property="is_show", type="string", example="true", description="查询列表是否显示"),
     * @SWG\Property( property="item_spec_desc", type="string", example="测试-颜色-0803:红色", description="商品规格描述"),
     * @SWG\Property( property="created", type="string", example="1608608836", description=""),
     * @SWG\Property( property="updated", type="string", example="1608608836", description=""),
     * @SWG\Property( property="status", type="string", example="it_has_ended", description="活动状态  waiting:未开始 in_the_notice:预告中 in_sale:售卖中 it_has_ended:已结束"),
     * @SWG\Property( property="created_date", type="string", example="2020-12-22 11:47:16", description="创建日期"),
     * @SWG\Property( property="updated_date", type="string", example="2020-12-22 11:47:16", description="修改日期"),
     * @SWG\Property( property="pics", type="array",
     *     @SWG\Items( type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/Hw4SsicubkrdYj0vHb58T6H7GkDhEoPMnzWy1w7MCzdBZKtc1ziac7tdUx4saPbIcrhaPoh6ibPt05yolY851Ds4A/0?wx_fmt=jpeg", description="商品图片"),
     * ),
     * @SWG\Property( property="price", type="string", example="12300", description="商品原销售金额,单位为‘分’"),
     * @SWG\Property( property="market_price", type="string", example="0", description="原价,单位为‘分’"),
     * @SWG\Property( property="item_name", type="string", example="123", description="商品名称"),
     *  @SWG\Property( property="nospec", type="string", example="false", description="商品是否为单规格"),
     * @SWG\Property( property="brand_logo", type="string", example="", description="品牌图片"),
     * @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     * @SWG\Property( property="goods_id", type="string", example="5025", description="关联商品id"),
     *
     * )
     */

    /**
     * @SWG\Definition(
     * definition="SeckillItemsActivity",
     * type="object",
     * @SWG\Property( property="seckill_id", type="string", example="300", description="秒杀活动id"),
     * @SWG\Property( property="activity_price", type="string", example="229", description="秒杀活动价格(分)"),
     * @SWG\Property( property="activity_store", type="string", example="975", description="秒杀活动库存"),
     * @SWG\Property( property="activity_start_time", type="string", example="1608652800", description="秒杀开始时间"),
     * @SWG\Property( property="activity_end_time", type="string", example="1608911999", description="秒杀结束时间"),
     * @SWG\Property( property="activity_release_time", type="string", example="1608652800", description="秒杀活动发布时间"),
     * @SWG\Property( property="status", type="string", example="it_has_ended", description="活动状态  waiting:未开始 in_the_notice:预告中 in_sale:售卖中 it_has_ended:已结束"),
     * @SWG\Property( property="last_seconds", type="string", example="0", description="结束剩余时间（秒）"),
     * )
     */

    /**
     * @SWG\Definition(
     * definition="SeckillResult",
     * type="object",
     * @SWG\Property( property="seckill_id", type="string", example="301", description="秒杀活动id"),
     * @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     * @SWG\Property( property="activity_name", type="string", example="4444", description="秒杀活动名称"),
     * @SWG\Property( property="distributor_id", type="string", example="", description="店铺id"),
     * @SWG\Property( property="seckill_type", type="string", example="normal", description="秒杀类型 normal正常的秒杀活动， limited_time_sale限时特惠"),
     * @SWG\Property( property="limit_total_money", type="string", example="", description="每人累计限额"),
     * @SWG\Property( property="otherext", type="string", example="", description="其他扩展字段"),
     * @SWG\Property( property="limit_money", type="string", example="", description="每人单笔限额"),
     * @SWG\Property( property="ad_pic", type="string", example="http://b-img-cdn.yuanyuanke.cn/image/1/2020/10/27/29c57ca983c4c37b59b5a2ba68d6cecaJwqf2uUBnaQaHCBzv3fjf88GwRPRlBYP", description="秒杀活动广告图"),
     * @SWG\Property( property="activity_start_time", type="string", example="1608739200", description="秒杀开始时间"),
     * @SWG\Property( property="activity_end_time", type="string", example="1608911999", description="秒杀结束时间"),
     * @SWG\Property( property="activity_release_time", type="string", example="1608652800", description="秒杀活动发布时间"),
     * @SWG\Property( property="is_activity_rebate", type="string", example="false", description="秒杀活动是否返佣"),
     * @SWG\Property( property="is_free_shipping", type="string", example="false", description="秒杀活动是否包邮"),
     * @SWG\Property( property="validity_period", type="string", example="15", description="未付款订单保留时长（分钟）"),
     * @SWG\Property( property="description", type="string", example="null", description="秒杀活动描述"),
     * @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     * @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     * @SWG\Property( property="created", type="string", example="1608608236", description=""),
     * @SWG\Property( property="updated", type="string", example="1608608658", description=""),
     * @SWG\Property( property="activity_start_date", type="string", example="2020-12-24 00:00:00", description="活动开始日期"),
     * @SWG\Property( property="activity_end_date", type="string", example="2020-12-25 23:59:59", description="活动结束日期"),
     * @SWG\Property( property="activity_release_date", type="string", example="2020-12-23 00:00:00", description="活动预告日期"),
     * @SWG\Property( property="created_date", type="string", example="2020-12-22 11:37:16", description="创建日期"),
     * @SWG\Property( property="updated_date", type="string", example="2020-12-22 11:44:18", description="更新日期"),
     * @SWG\Property( property="status", type="string", example="it_has_ended", description="活动状态  waiting:未开始 in_the_notice:预告中 in_sale:售卖中 it_has_ended:已结束"),
     * @SWG\Property(
     *   property="items",
     *   type="array",
     *   @SWG\Items(ref="#/definitions/SeckillItems")
     * ),
     * )
     */

    /**
     * @SWG\Definition(
     * definition="SeckillDetail",
     * type="object",
     * @SWG\Property( property="seckill_id", type="string", example="301", description="秒杀活动id"),
     * @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     * @SWG\Property( property="activity_name", type="string", example="4444", description="秒杀活动名称"),
     * @SWG\Property( property="distributor_id", type="string", example="", description="店铺id"),
     * @SWG\Property( property="seckill_type", type="string", example="normal", description="秒杀类型 normal正常的秒杀活动， limited_time_sale限时特惠"),
     * @SWG\Property( property="limit_total_money", type="string", example="", description="每人累计限额"),
     * @SWG\Property( property="otherext", type="string", example="", description="其他扩展字段"),
     * @SWG\Property( property="limit_money", type="string", example="", description="每人单笔限额"),
     * @SWG\Property( property="ad_pic", type="string", example="http://b-img-cdn.yuanyuanke.cn/image/1/2020/10/27/29c57ca983c4c37b59b5a2ba68d6cecaJwqf2uUBnaQaHCBzv3fjf88GwRPRlBYP", description="秒杀活动广告图"),
     * @SWG\Property( property="activity_start_time", type="string", example="1608739200", description="秒杀开始时间"),
     * @SWG\Property( property="activity_end_time", type="string", example="1608911999", description="秒杀结束时间"),
     * @SWG\Property( property="activity_release_time", type="string", example="1608652800", description="秒杀活动发布时间"),
     * @SWG\Property( property="is_activity_rebate", type="string", example="false", description="秒杀活动是否返佣"),
     * @SWG\Property( property="is_free_shipping", type="string", example="false", description="秒杀活动是否包邮"),
     * @SWG\Property( property="validity_period", type="string", example="15", description="未付款订单保留时长（分钟）"),
     * @SWG\Property( property="description", type="string", example="null", description="秒杀活动描述"),
     * @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     * @SWG\Property( property="use_bound", type="string", example="1", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     * @SWG\Property( property="created", type="string", example="1608608236", description=""),
     * @SWG\Property( property="updated", type="string", example="1608608658", description=""),
     * @SWG\Property( property="activity_start_date", type="string", example="2020-12-24 00:00:00", description="活动开始日期"),
     * @SWG\Property( property="activity_end_date", type="string", example="2020-12-25 23:59:59", description="活动结束日期"),
     * @SWG\Property( property="activity_release_date", type="string", example="2020-12-23 00:00:00", description="活动预告日期"),
     * @SWG\Property( property="created_date", type="string", example="2020-12-22 11:37:16", description="创建日期"),
     * @SWG\Property( property="updated_date", type="string", example="2020-12-22 11:44:18", description="更新日期"),
     * @SWG\Property( property="status", type="string", example="it_has_ended", description="活动状态  waiting:未开始 in_the_notice:预告中 in_sale:售卖中 it_has_ended:已结束"),
     * @SWG\Property(
     *   property="items",
     *   type="array",
     *   @SWG\Items(ref="#/definitions/SeckillItems")
     * ),
     * @SWG\Property(
     *   property="itemTreeLists",
     *   type="array",
     *   description="子商品的商品数据",
     *   @SWG\Items( type="object",
     *     ref="#definitions/ItemList",
     *     ),
     *   ),
     * )
     */

    /**
      * @SWG\Post(
      *     path="/promotions/seckillactivity/create",
      *     summary="创建秒杀活动",
      *     tags={"营销"},
      *     description="创建秒杀活动",
      *     operationId="createSeckillActivity",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="activity_name", in="formData", description="活动名称", type="string"),
      *     @SWG\Parameter( name="activity_start_time", in="formData", description="活动开始时间", required=true, type="string"),
      *     @SWG\Parameter( name="activity_end_time", in="formData", description="活动结束时间", required=true, type="string"),
      *     @SWG\Parameter( name="items", in="formData", description="商品信息,json,包含字段:item_id,item_title,activity_store,activity_price,item_spec_desc,sort,limit_num,item_type", type="string"),
      *     @SWG\Parameter( name="is_activity_rebate", in="formData", description="活动佣金", type="integer"),
      *     @SWG\Parameter( name="ad_pic", in="formData", description="活动广告图", type="string"),
      *     @SWG\Parameter( name="description", in="formData", description="活动描述", type="string"),
      *     @SWG\Parameter( name="is_free_shipping", in="formData", description="是否包邮", type="boolean"),
      *     @SWG\Parameter( name="validity_period", in="formData", description="未付款保留时长(分钟)", required=true, type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 ref="#/definitions/SeckillResult",
      *
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function createSeckillActivity(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        if (isset($params['items']) && !is_array($params['items'])) {
            $params['items'] = json_decode($params['items'], true);
        }
        $rules = [
            'activity_name' => ['required', trans('PromotionsBundle.activity_name_required')],
            'activity_start_time' => ['required', trans('PromotionsBundle.activity_start_time_required')],
            'activity_end_time' => ['required', trans('PromotionsBundle.activity_end_time_required')],
            'activity_release_time' => ['required', trans('PromotionsBundle.activity_release_time_required')],
            'company_id' => ['required', trans('PromotionsBundle.company_id_required')],
            // 'ad_pic' => ['required', trans('PromotionsBundle.activity_cover_required')],
            'items.*.item_id' => ['required', trans('PromotionsBundle.product_id_param_missing')],
            'items.*.item_title' => ['required', trans('PromotionsBundle.product_title_param_missing')],
            'items.*.activity_price' => ['required', trans('PromotionsBundle.product_activity_price_param_missing')],
            'items.*.activity_store' => ['required', trans('PromotionsBundle.product_activity_stock_param_missing')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        if (!($params['limit_total_money'] ?? false)) {
            $params['limit_total_money'] = 0;
        }
        if (!($params['limit_money'] ?? false)) {
            $params['limit_money'] = 0;
        }

        if (isset($params['limit_total_money']) && strlen((string)$params['limit_total_money']) > 13) {
            throw new ResourceException(trans('PromotionsBundle.amount_cannot_exceed_13_digits'));
        }

        if (isset($params['limit_money']) && strlen((string)$params['limit_money']) > 13) {
            throw new ResourceException(trans('PromotionsBundle.amount_cannot_exceed_13_digits'));
        }

        $params['activity_start_time'] = strtotime($params['activity_start_time']);
        $params['activity_end_time'] = strtotime($params['activity_end_time']);
        $params['activity_release_time'] = strtotime($params['activity_release_time']);
        $params['seckill_type'] = $params['seckill_type'] ?? 'normal';
        $params['distributor_id'] = (isset($params['distributor_id']) && is_array($params['distributor_id'])) ? implode(',', $params['distributor_id']) : null;

        if (!$params['items']) {
            throw new ResourceException(trans('PromotionsBundle.bind_product_param_missing'));
        }

        if ($request->get('distributor_id') && !$params['distributor_id']) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        $params['source_id'] = app('auth')->user()->get('distributor_id');//如果是平台，这里是0
        $params['source_type'] = app('auth')->user()->get('operator_type');//如果是平台，这里是admin or staff

        $result = $this->service->create($params);
        return $this->response->array($result);
    }

    /**
      * @SWG\Put(
      *     path="/promotions/seckillactivity/updatestatus",
      *     summary="更新秒杀活动状态",
      *     tags={"营销"},
      *     description="更新秒杀活动状态",
      *     operationId="updateStatus",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="seckill_id", in="query", description="活动id", type="integer", required=true),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property(property="status", type="boolean", description="状态", example=true),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function updateStatus(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $result = $this->service->endActivity($authUser['company_id'], $request->input('seckill_id'));
        return $this->response->array(['status' => true]);
    }

    /**
      * @SWG\Put(
      *     path="/promotions/seckillactivity/update",
      *     summary="修改秒杀活动",
      *     tags={"营销"},
      *     description="修改秒杀活动",
      *     operationId="updateSeckillActivity",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="seckill_id", in="query", description="活动id", type="integer"),
      *     @SWG\Parameter( name="activity_name", in="formData", description="活动名称", type="string"),
      *     @SWG\Parameter( name="activity_start_time", in="formData", description="活动开始时间", required=true, type="string"),
      *     @SWG\Parameter( name="activity_end_time", in="formData", description="活动结束时间", required=true, type="string"),
      *     @SWG\Parameter( name="items", in="formData", description="商品信息,json,包含字段:item_id,item_title,activity_store,activity_price,item_spec_desc,sort,limit_num,item_type", type="string"),
      *     @SWG\Parameter( name="is_activity_rebate", in="formData", description="活动佣金", type="integer"),
      *     @SWG\Parameter( name="ad_pic", in="formData", description="活动广告图", type="string"),
      *     @SWG\Parameter( name="description", in="formData", description="活动描述", type="string"),
      *     @SWG\Parameter( name="is_free_shipping", in="formData", description="是否包邮", type="boolean"),
      *     @SWG\Parameter( name="validity_period", in="formData", description="未付款保留时长(分钟)", required=true, type="integer"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 ref="#/definitions/SeckillResult"
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function updateSeckillActivity(Request $request)
    {
        // fe10e2f6 module
        $authUser = app('auth')->user()->get();
        $params = $request->input();
        $params['company_id'] = $authUser['company_id'];
        if (isset($params['items']) && !is_array($params['items'])) {
            $params['items'] = json_decode($params['items'], true);
        }
        $rules = [
            'seckill_id' => ['required', trans('PromotionsBundle.activity_id_required')],
            'activity_name' => ['required', trans('PromotionsBundle.activity_name_required')],
            'activity_start_time' => ['required', trans('PromotionsBundle.activity_start_time_required')],
            'activity_end_time' => ['required', trans('PromotionsBundle.activity_end_time_required')],
            'activity_release_time' => ['required', trans('PromotionsBundle.activity_release_time_required')],
            'company_id' => ['required', trans('PromotionsBundle.company_id_required')],
            // 'ad_pic' => ['required', trans('PromotionsBundle.activity_cover_required')],
            'items.*.item_id' => ['required', trans('PromotionsBundle.product_id_param_missing')],
            'items.*.item_title' => ['required', trans('PromotionsBundle.product_title_param_missing')],
            'items.*.activity_price' => ['required', trans('PromotionsBundle.product_activity_price_param_missing')],
            'items.*.activity_store' => ['required', trans('PromotionsBundle.product_activity_stock_param_missing')],
        ];
        $errorMessage = validator_params($params, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }
        $params['activity_start_time'] = strtotime($params['activity_start_time']);
        $params['activity_end_time'] = strtotime($params['activity_end_time']);
        $params['activity_release_time'] = strtotime($params['activity_release_time']);
        $params['distributor_id'] = (isset($params['distributor_id']) && is_array($params['distributor_id'])) ? implode(',', $params['distributor_id']) : null;

        if ($request->get('distributor_id') && !$params['distributor_id']) {
            $params['distributor_id'] = $request->get('distributor_id');
        }

        if (!isset($params['items']) || !$params['items']) {
            throw new ResourceException(trans('PromotionsBundle.no_activity_products_please_add'));
        }

        $filter['company_id'] = $params['company_id'];
        $filter['seckill_id'] = $params['seckill_id'];
        $params['seckill_type'] = $params['seckill_type'] ?? 'normal';

        $params['source_id'] = app('auth')->user()->get('distributor_id');//如果是平台，这里是0
        $params['source_type'] = app('auth')->user()->get('operator_type');//如果是平台，这里是admin or staff

        $result = $this->service->updateActivity($filter, $params);
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/promotions/seckillactivity/getinfo",
      *     summary="获取秒杀活动详情",
      *     tags={"营销"},
      *     description="获取秒杀活动详情",
      *     operationId="getSeckillActivityInfo",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="seckill_id", in="query", description="活动id", type="integer", required=true),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 ref="#/definitions/SeckillDetail"
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getSeckillActivityInfo(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['seckill_id'] = $request->input('seckill_id');
        $result = $this->service->getSeckillInfo($filter);
        $result['is_activity_rebate'] = $result['is_activity_rebate'] ? 'true' : 'false';
        $result['is_free_shipping'] = $result['is_free_shipping'] ? 'true' : 'false';
        $itemIds = [];

        $distributorService = new DistributorService();
        if ($result['distributor_id']) {
            $distributorList = $distributorService->lists(['distributor_id' => $result['distributor_id'], 'company_id' => $filter['company_id']], ["created" => "DESC"], 1000, 1, false);
            $result['distributor_info'] = $distributorList['list'];
        } else {
            $result['distributor_info'] = [];
        }

        foreach ($result['items'] as &$items) {
            $itemIds[] = $items['item_id'];
            $items['activity_price'] = $items['activity_price'] ? $items['activity_price'] / 100 : 0;
        }
        if ($itemIds) {
            $filter = ['company_id' => $authUser['company_id'], 'item_id' => $itemIds];
            $itemService = new ItemsService();
            $itemList = $itemService->getSkuItemsList($filter);
            $itemTreeList = $itemService->formatItemsList($itemList['list']);
            $result['itemTreeLists'] = $itemTreeList;

            //获取商品标签
            $itemIds = array_column($result['itemTreeLists'], 'item_id');
            $tagFilter = [
                'item_id' => $itemIds,
                'company_id' => $filter['company_id'],
            ];
            $itemsTagsService = new ItemsTagsService();
            $tagList = $itemsTagsService->getItemsRelTagList($tagFilter);
            foreach ($tagList as $tag) {
                $newTags[$tag['item_id']][] = $tag;
            }

            foreach ($result['itemTreeLists'] as &$value) {
                $value['tagList'] = $newTags[$value['item_id']] ?? [];
                if ($value['nospec'] === false || $value['nospec'] === 'false' || $value['nospec'] === 0 || $value['nospec'] === '0') {
                    foreach ($value['spec_items'] as &$spec) {
                        $spec['tagList'] = $value['tagList'];
                    }
                }
            }
        }
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/promotions/seckillactivity/getlist",
      *     summary="获取秒杀活动列表",
      *     tags={"营销"},
      *     description="获取秒杀活动列表",
      *     operationId="getSeckillActivityList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="seckill_id", in="query", description="活动id", type="integer"),
      *     @SWG\Parameter( name="name", in="query", description="活动名称", type="string"),
      *     @SWG\Parameter( name="start_time", in="query", description="活动开始时间", required=true, type="string"),
      *     @SWG\Parameter( name="end_time", in="query", description="活动结束时间", required=true, type="string"),
      *     @SWG\Parameter( name="item_title", in="query", description="商品名称", type="string"),
      *     @SWG\Parameter( name="is_free_shipping", in="query", description="是否包邮('true','false')", type="string"),
      *     @SWG\Parameter( name="status", in="query", description="活动状态 waiting:未开始 in_the_notice:预告中 in_sale:售卖中 it_has_ended:已结束 valid:有效", type="string"),
      *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                 @SWG\Property( property="total_count", type="string", example="91", description="总条数"),
      *                 @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          ref="#/definitions/SeckillBase",
     *                      ),
     *                  ),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getSeckillActivityList(Request $request)
    {
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $sourceId = floatval($request->get('distributor_id', 0));//如果是平台，这里是0

        $input = $request->all('seckill_id', 'keywords', 'name', 'start_time', 'end_time', 'item_title', 'is_free_shipping', 'status', 'seckill_type');

        if ($input['item_title']) {
            $filter['item_title|contains'] = $input['item_title'];
        }
        if ($request->get('distributor_id')) {
            $filter['distributor_id'] = ','.$request->get('distributor_id').',';
        }
        if ($input['status']) {
            switch ($input['status']) {
                case "waiting":
                    $filter['activity_release_time|gte'] = time();
                    $filter['activity_end_time|gte'] = time();
                    $filter['disabled'] = 0;
                    break;
                case "in_the_notice":
                    $filter['activity_release_time|lte'] = time();
                    $filter['activity_start_time|gt'] = time();
                    $filter['activity_end_time|gte'] = time();
                    $filter['disabled'] = 0;
                    break;
                case "in_sale":
                    $filter['activity_start_time|lte'] = time();
                    $filter['activity_end_time|gt'] = time();
                    $filter['disabled'] = 0;
                    break;
                case "it_has_ended":
                    $filter['or'] = [
                        'activity_end_time|lte' => time(),
                        'disabled' => 1,
                    ];
                    break;
                case "valid":
                    $filter['activity_release_time|lte'] = time();
                    $filter['activity_end_time|gte'] = time();
                    $filter['disabled'] = 0;
                    break;
                case "not_end":
                    $filter['activity_end_time|gte'] = time();
                    $filter['disabled'] = 0;
                    break;
            }
        }

        if ($input['seckill_id']) {
            $filter['seckill_id'] = $input['seckill_id'];
        }

        if ($input['keywords']) {
            $filter['activity_name|contains'] = $input['keywords'];
        }

        if ($input['name']) {
            $filter['activity_name|contains'] = $input['name'];
        }

        if ($input['is_free_shipping']) {
            $filter['is_free_shipping'] = $input['is_free_shipping'];
        }

        if (isset($input['start_time'],$input['end_time']) && $input['start_time'] && $input['end_time']) {
            $filter['activity_release_time|gte'] = $input['start_time'];
            $filter['activity_end_time|lte'] = $input['end_time'];
        }

        $filter['seckill_type'] = $input['seckill_type'] ?: 'normal';

        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $orderBy = ['seckill_id' => 'desc'];
        $result = $this->service->lists($filter, $page, $pageSize, $orderBy);
        if ($result['total_count'] > 0) {
            $distributorids = [];
            foreach ($result['list'] as $row) {
                if ($row['distributor_id']) {
                    $distributorids = array_merge($distributorids, $row['distributor_id']);
                }
            }

            $distributorids = array_unique($distributorids);
            $distributorService = new DistributorService();
            $distributorList = [];
            if ($distributorids) {
                $distributorList = $distributorService->lists(['distributor_id' => $distributorids, 'company_id' => $filter['company_id']], ["created" => "DESC"], 2000, 1, false);
                if ($distributorList['total_count'] > 0) {
                    $distributorList = array_column($distributorList['list'], null, 'distributor_id');
                }
            }

            $result = $this->__getSourceName($result);//获取店铺名称

            foreach ($result['list'] as &$row) {
                if ($row['source_id'] != $sourceId) {
                    if ($row['source_type'] == 'staff' && $sourceId == 0) {
                        $row['edit_btn'] = 'Y';//平台子账号创建的促销，超管可以编辑
                    } else {
                        $row['edit_btn'] = 'N';//屏蔽编辑按钮，平台只能编辑自己的促销
                    }
                } else {
                    $row['edit_btn'] = 'Y';
                }
                $row['distributor_info'] = [];
                if (!$row['distributor_id']) {
                    continue;
                }
                foreach ($row['distributor_id'] as $distributorId) {
                    if (isset($distributorList[$distributorId])) {
                        $row['distributor_info'][] = $distributorList[$distributorId];
                    }
                }
            }
        }

        return $this->response->array($result);
    }

    private function __getSourceName($result = [])
    {
        $distributorIds = [];
        $sourceName = [
            'distributor' => []
        ];
        foreach ($result['list'] as $v) {
            if ($v['source_type'] == 'distributor') {
                $distributorIds[] = $v['source_id'];
            }
        }
        if ($distributorIds) {
            $distributorService = new DistributorService();
            $rs = $distributorService->getLists(['distributor_id' => $distributorIds], 'distributor_id,name');
            if ($rs) {
                $sourceName['distributor'] = array_column($rs, 'name', 'distributor_id');
            }
        }

        foreach ($result['list'] as $k => $v) {
            $source_name = '';
            if (isset($sourceName[$v['source_type']][$v['source_id']])) {
                $source_name = $sourceName[$v['source_type']][$v['source_id']];
            }
            $result['list'][$k]['source_name'] = $source_name;
        }
        return $result;
    }

    /**
      * @SWG\Get(
      *     path="/promotions/seckillactivity/getIteminfo",
      *     summary="获取秒杀活动商品列表",
      *     tags={"营销"},
      *     description="获取秒杀活动商品列表,sku",
      *     operationId="getSeckillItemList",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="seckill_id", in="query", description="活动名称", type="integer"),
      *     @SWG\Parameter( name="page", in="query", description="页码，默认1", type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页数量，默认20", type="string"),
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
      *                     @SWG\Items(
     *                          ref="#/definitions/SeckillItems"
     *                      ),
      *                 ),
      *                 @SWG\Property(
      *                     property="activity",
      *                     type="object",
      *                     ref="#/definitions/SeckillItemsActivity",
      *
      *                 ),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getSeckillItemList(Request $request)
    {
        $page = $request->input('page', 1);
        $pageSize = $request->input('pageSize', 20);
        $authUser = app('auth')->user()->get();
        $filter['company_id'] = $authUser['company_id'];
        $filter['seckill_id'] = $request->input('seckill_id');
        $isSku = $request->input('is_sku', true);
        $result = $this->service->getSeckillItemList($filter, $page, $pageSize, [], $isSku);
        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/promotions/seckillactivity/wxcode",
      *     summary="获取秒杀活动小程序码",
      *     tags={"营销"},
      *     description="获取秒杀活动小程序码",
      *     operationId="getSeckillWxaCode",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="seckill_id", in="query", description="活动名称", type="integer"),
      *     @SWG\Parameter( name="seckill_type", in="query", description="活动类型", type="string"),
      *     @SWG\Parameter( name="distributor_id", in="query", description="店铺id", type="string"),
      *     @SWG\Response(
      *         response=200,
      *         description="成功返回结构",
      *         @SWG\Schema(
      *             @SWG\Property(
      *                 property="data",
      *                 type="object",
      *                  @SWG\Property(property="code", type="string", example="图片的base64字符串"),
      *             ),
      *          ),
      *     ),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function getSeckillWxaCode(Request $request)
    {
        $authUser = app('auth')->user()->get();

        $distributorid = $request->input('distributor_id', 0);
        $seckillType = $request->input('seckill_type', 'normal');
        $result['code'] = $this->service->getSeckillWxaCode($authUser['company_id'], $request->input('seckill_id'), $seckillType, $distributorid);

        return $this->response->array($result);
    }

    /**
      * @SWG\Get(
      *     path="/promotions/seckillactivity/search/items",
      *     summary="根据条件获取商品列表",
      *     tags={"营销"},
      *     description="根据条件获取商品列表",
      *     operationId="searchItems",
      *     @SWG\Parameter( name="Authorization", in="header", description="JWT验证token", required=true, type="string"),
      *     @SWG\Parameter( name="page", in="query", description="页数", required=true, type="string"),
      *     @SWG\Parameter( name="pageSize", in="query", description="每页条数", required=true, type="string"),
      *     @SWG\Parameter( name="item_type", in="query", description="商品类型", required=false, type="string"),
      *     @SWG\Parameter( name="templates_id", in="query", description="", required=false, type="string"),
      *     @SWG\Parameter( name="keywords", in="query", description="关键词", required=false, type="string"),
      *     @SWG\Parameter( name="category", in="query", description="分类id", required=false, type="string"),
      *     @SWG\Parameter( name="is_warning", in="query", description="是否是库存警告", required=false, type="string"),
      *     @SWG\Parameter( name="tag_id", in="query", description="标签id", required=false, type="string"),
      *     @SWG\Parameter( name="type", in="query", description="商品类型", required=false, type="string"),
      *     @SWG\Parameter( name="is_sku", in="query", description="是否查询sku", required=false, type="string"),
      *     @SWG\Parameter( name="item_id", in="query", description="商品id", required=false, type="string"),
      *     @SWG\Parameter( name="main_cat_id", in="query", description="主类目id", required=false, type="string"),
      *     @SWG\Parameter( name="brand_id", in="query", description="品牌id", required=false, type="string"),
      *     @SWG\Parameter( name="marketing_type", in="query", description="营销类型", required=false, type="string"),
      *     @SWG\Parameter( name="activity_start_time", in="query", description="活动开始时间", required=false, type="string"),
      *     @SWG\Parameter( name="activity_release_time", in="query", description="活动预发布时间", required=false, type="string"),
      *     @SWG\Parameter( name="activity_end_time", in="query", description="活动结束时间", required=false, type="string"),
      *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="object",
     *                          @SWG\Property( property="validItems", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="item_id", type="string", example="5885", description="商品ID"),
     *                                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                                  @SWG\Property( property="store", type="string", example="8", description="商品库存"),
     *                                  @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                                  @SWG\Property( property="sales", type="string", example="3", description="商品销量"),
     *                                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                                  @SWG\Property( property="rebate", type="string", example="0", description="返佣金额,单位为‘分’"),
     *                                  @SWG\Property( property="rebate_conf", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="cost_price", type="string", example="100", description="价格,单位为‘分’"),
     *                                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                                  @SWG\Property( property="point", type="string", example="0", description="积分兑换价格"),
     *                                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                                  @SWG\Property( property="goods_id", type="string", example="5885", description="产品ID"),
     *                                  @SWG\Property( property="brand_id", type="string", example="1631", description="品牌id"),
     *                                  @SWG\Property( property="item_name", type="string", example="69测试", description="商品名称"),
     *                                  @SWG\Property( property="item_unit", type="string", example="", description="商品计量单位"),
     *                                  @SWG\Property( property="item_bn", type="string", example="6969", description="商品编码"),
     *                                  @SWG\Property( property="brief", type="string", example="69测试", description="简洁的描述"),
     *                                  @SWG\Property( property="price", type="string", example="100", description="销售金额,单位为‘分’"),
     *                                  @SWG\Property( property="market_price", type="string", example="100", description="原价,单位为‘分’"),
     *                                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                                  @SWG\Property( property="volume", type="string", example="null", description="商品体积"),
     *                                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                                  @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                                  @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                                  @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                                  @SWG\Property( property="regions_id", type="string", example="null", description="地区id(DC2Type:json_array)"),
     *                                  @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                                  @SWG\Property( property="sort", type="string", example="0", description="商品排序"),
     *                                  @SWG\Property( property="templates_id", type="string", example="94", description="运费模板id"),
     *                                  @SWG\Property( property="is_default", type="string", example="true", description="商品是否为默认商品"),
     *                                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                                  @SWG\Property( property="default_item_id", type="string", example="5885", description="默认商品ID"),
     *                                  @SWG\Property( property="pics", type="array",
     *                                      @SWG\Items( type="string", example="https://bbctest.aixue7.com/image/1/2021/05/28/61597efd4fca7031b9df8eab13563504PAsWeUQjYQXxV6McDp2UkXooMpyqURUE", description="图片地址"),
     *                                  ),
     *                                  @SWG\Property( property="pics_create_qrcode", type="array",
     *                                      @SWG\Items( type="string", example="false", description="是否添加分享码"),
     *                                  ),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺id"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                                  @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                                  @SWG\Property( property="item_category", type="string", example="1733", description="商品主类目"),
     *                                  @SWG\Property( property="rebate_type", type="string", example="default", description="分佣计算方式"),
     *                                  @SWG\Property( property="weight", type="string", example="0", description="商品重量"),
     *                                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                                  @SWG\Property( property="tax_rate", type="string", example="0", description="商品税率"),
     *                                  @SWG\Property( property="created", type="string", example="1623209672", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1623211353", description="修改时间"),
     *                                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                                  @SWG\Property( property="videos", type="string", example="", description="视频"),
     *                                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                                  @SWG\Property( property="profit_type", type="string", example="0", description="分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)"),
     *                                  @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                                  @SWG\Property( property="taxation_num", type="string", example="1", description="计税单位份数"),
     *                                  @SWG\Property( property="type", type="string", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                                  @SWG\Property( property="tdk_content", type="string", example="", description="tdk详情"),
     *                                  @SWG\Property( property="itemId", type="string", example="5885", description="商品ID"),
     *                                  @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                                  @SWG\Property( property="itemName", type="string", example="69测试", description="商品名称"),
     *                                  @SWG\Property( property="itemBn", type="string", example="6969", description="商品编号"),
     *                                  @SWG\Property( property="companyId", type="string", example="1", description="企业ID"),
     *                                  @SWG\Property( property="item_main_cat_id", type="string", example="1733", description="商品主类目id"),
     *                                  @SWG\Property( property="type_labels", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="tagList", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="标签列表"),
     *                                  ),
     *                                  @SWG\Property( property="itemMainCatName", type="string", example="连衣裙", description="商品主类目名称"),
     *                                  @SWG\Property( property="itemCatName", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="商品类目名称"),
     *                                  ),
     *                               ),
     *                          ),
     *                          @SWG\Property( property="invalidItems", type="array",
     *                              @SWG\Items( type="object",
     *                                  @SWG\Property( property="item_id", type="string", example="5848", description="商品ID"),
     *                                  @SWG\Property( property="item_type", type="string", example="normal", description="商品类型，services：服务商品，normal: 普通商品"),
     *                                  @SWG\Property( property="consume_type", type="string", example="every", description="核销类型，every：每个物料都要核销(例如3个物料要核销3次)，all：所有物料作为一个整体核销一次(例如3个物料只需要核销1次)"),
     *                                  @SWG\Property( property="is_show_specimg", type="string", example="false", description="详情页是否显示规格图片"),
     *                                  @SWG\Property( property="store", type="string", example="17", description="商品库存"),
     *                                  @SWG\Property( property="barcode", type="string", example="", description="商品条形码"),
     *                                  @SWG\Property( property="sales", type="string", example="10", description="商品销量"),
     *                                  @SWG\Property( property="approve_status", type="string", example="onsale", description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售"),
     *                                  @SWG\Property( property="rebate", type="string", example="0", description="单个分销金额，以分为单位"),
     *                                  @SWG\Property( property="rebate_conf", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="cost_price", type="string", example="300", description="价格,单位为‘分’"),
     *                                  @SWG\Property( property="is_point", type="string", example="null", description="是否积分兑换 true可以 false不可以"),
     *                                  @SWG\Property( property="point", type="string", example="0", description="积分兑换价格"),
     *                                  @SWG\Property( property="item_source", type="string", example="mall", description="商品来源:mall:主商城，distributor:店铺自有"),
     *                                  @SWG\Property( property="goods_id", type="string", example="5848", description="产品ID"),
     *                                  @SWG\Property( property="brand_id", type="string", example="1461", description="品牌id"),
     *                                  @SWG\Property( property="item_name", type="string", example="商品0001", description="商品名称"),
     *                                  @SWG\Property( property="item_unit", type="string", example="1", description="商品计量单位"),
     *                                  @SWG\Property( property="item_bn", type="string", example="F1", description="商品编码"),
     *                                  @SWG\Property( property="brief", type="string", example="商品0001", description="简洁的描述"),
     *                                  @SWG\Property( property="price", type="string", example="2000", description="销售金额,单位为‘分’"),
     *                                  @SWG\Property( property="market_price", type="string", example="200", description="原价,单位为‘分’"),
     *                                  @SWG\Property( property="special_type", type="string", example="normal", description="商品特殊类型 drug 处方药 normal 普通商品"),
     *                                  @SWG\Property( property="goods_function", type="string", example="null", description="商品功能"),
     *                                  @SWG\Property( property="goods_series", type="string", example="null", description="商品系列"),
     *                                  @SWG\Property( property="volume", type="string", example="11", description="商品体积"),
     *                                  @SWG\Property( property="goods_color", type="string", example="null", description="商品颜色"),
     *                                  @SWG\Property( property="goods_brand", type="string", example="null", description="商品品牌"),
     *                                  @SWG\Property( property="item_address_province", type="string", example="", description="产地省"),
     *                                  @SWG\Property( property="item_address_city", type="string", example="", description="产地市"),
     *                                  @SWG\Property( property="regions_id", type="string", example="110000,110100,110102", description="地区id(DC2Type:json_array)"),
     *                                  @SWG\Property( property="brand_logo", type="string", example="null", description="品牌图片"),
     *                                  @SWG\Property( property="sort", type="string", example="0", description="商品排序"),
     *                                  @SWG\Property( property="templates_id", type="string", example="87", description="运费模板id"),
     *                                  @SWG\Property( property="is_default", type="string", example="true", description="商品是否为默认商品"),
     *                                  @SWG\Property( property="nospec", type="string", example="true", description="商品是否为单规格"),
     *                                  @SWG\Property( property="default_item_id", type="string", example="5848", description="默认商品ID"),
     *                                  @SWG\Property( property="pics", type="array",
     *                                      @SWG\Items( type="string", example="https://bbctest.aixue7.com/1/2019/09/25/849ea5f8819debe4530f360160f42acfDqJlJi1k9Im78CUtgwxna38AXh5rwKuN", description="商品图片"),
     *                                  ),
     *                                  @SWG\Property( property="pics_create_qrcode", type="array",
     *                                      @SWG\Items( type="string", example="true", description="是否增加分享码"),
     *                                  ),
     *                                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="企业ID"),
     *                                  @SWG\Property( property="enable_agreement", type="string", example="false", description="开启购买协议"),
     *                                  @SWG\Property( property="date_type", type="string", example="", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                                  @SWG\Property( property="item_category", type="string", example="1733", description="商品主类目"),
     *                                  @SWG\Property( property="rebate_type", type="string", example="default", description="返佣模式"),
     *                                  @SWG\Property( property="weight", type="string", example="22", description="商品重量"),
     *                                  @SWG\Property( property="begin_date", type="string", example="0", description="有效期开始时间"),
     *                                  @SWG\Property( property="end_date", type="string", example="0", description="有效期结束时间"),
     *                                  @SWG\Property( property="fixed_term", type="string", example="0", description="有效期的有效天数"),
     *                                  @SWG\Property( property="tax_rate", type="string", example="0", description="商品税率"),
     *                                  @SWG\Property( property="created", type="string", example="1621404666", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1623222493", description="修改时间"),
     *                                  @SWG\Property( property="video_type", type="string", example="local", description="视频类型 local:本地视频 tencent:腾讯视频"),
     *                                  @SWG\Property( property="videos", type="string", example="https://bbctest.aixue7.com/videos/1/2020/11/05/733786f29dd5c3b79dd73decaaa41198vCtFZXIiI0s9pUeIXw5P8i9OfJENnjS9", description="视频"),
     *                                  @SWG\Property( property="video_pic_url", type="string", example="null", description="视频封面图"),
     *                                  @SWG\Property( property="audit_status", type="string", example="approved", description="审核状态 approved成功 processing审核中 rejected审核拒绝"),
     *                                  @SWG\Property( property="audit_reason", type="string", example="null", description="审核拒绝原因"),
     *                                  @SWG\Property( property="is_gift", type="string", example="false", description="是否为赠品"),
     *                                  @SWG\Property( property="is_package", type="string", example="false", description="是否为打包产品"),
     *                                  @SWG\Property( property="profit_type", type="string", example="0", description="分润类型, 默认为0配置分润,1主类目分润,2商品指定分润(比例),3商品指定分润(金额)"),
     *                                  @SWG\Property( property="profit_fee", type="string", example="0", description="分润金额,单位为分 冗余字段"),
     *                                  @SWG\Property( property="is_profit", type="string", example="true", description="是否支持分润"),
     *                                  @SWG\Property( property="crossborder_tax_rate", type="string", example="", description="跨境税率，百分比，小数点2位"),
     *                                  @SWG\Property( property="origincountry_id", type="string", example="0", description="产地国id"),
     *                                  @SWG\Property( property="taxstrategy_id", type="string", example="0", description="税费策略id"),
     *                                  @SWG\Property( property="taxation_num", type="string", example="1", description="计税单位份数"),
     *                                  @SWG\Property( property="type", type="string", example="0", description="商品类型，0普通，1跨境商品，可扩展"),
     *                                  @SWG\Property( property="tdk_content", type="string", example="", description="tdk详情"),
     *                                  @SWG\Property( property="itemId", type="string", example="5848", description=""),
     *                                  @SWG\Property( property="consumeType", type="string", example="every", description=""),
     *                                  @SWG\Property( property="itemName", type="string", example="商品0001", description=""),
     *                                  @SWG\Property( property="itemBn", type="string", example="F1", description=""),
     *                                  @SWG\Property( property="companyId", type="string", example="1", description=""),
     *                                  @SWG\Property( property="item_main_cat_id", type="string", example="1733", description=""),
     *                                  @SWG\Property( property="type_labels", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="tagList", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description=""),
     *                                  ),
     *                                  @SWG\Property( property="itemMainCatName", type="string", example="连衣裙", description=""),
     *                                  @SWG\Property( property="itemCatName", type="array",
     *                                      @SWG\Items( type="string", example="undefined", description="自行更改字段描述"),
     *                                  ),
     *                               ),
     *                          ),
     *                  ),
     *          ),
     *     )),
      *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/PromotionsErrorRespones") ) )
      * )
      */
    public function searchItems(Request $request)
    {
        $inputData = $request->input();
        $validator = app('validator')->make($inputData, [
            'page' => 'required|integer|min:1',
            'pageSize' => 'required|integer',
        ]);

        if ($validator->fails()) {
            throw new ResourceException(trans('PromotionsBundle.get_product_list_error'), $validator->errors());
        }

        $rules = [
            'activity_start_time' => ['required', trans('PromotionsBundle.activity_start_time_required')],
            'activity_end_time' => ['required', trans('PromotionsBundle.activity_end_time_required')],
            'activity_release_time' => ['required', trans('PromotionsBundle.activity_release_time_required')],
        ];
        $errorMessage = validator_params($inputData, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $params['company_id'] = app('auth')->user()->get('company_id');

        if ($inputData['activity_release_time'] > $inputData['activity_start_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_release_time'));
        }

        if ($inputData['activity_start_time'] >= $inputData['activity_end_time']) {
            throw new ResourceException(trans('PromotionsBundle.activity_start_time_cannot_greater_than_end_time'));
        }

        // 判断是否跨境
        if ($request->input('type') !== null) {
            $params['type'] = $request->input('type');
        }
        if (isset($inputData['item_name']) && $inputData['item_name']) {
            $params['item_name|contains'] = $request->input('item_name');
        }
        if (isset($inputData['consume_type']) && $inputData['consume_type']) {
            $params['consume_type'] = $request->input('consume_type');
        }
        if (isset($inputData['templates_id']) && $inputData['templates_id']) {
            $params['templates_id'] = $request->input('templates_id');
        }
        if (isset($inputData['regions_id']) && $inputData['regions_id']) {
            $params['regions_id'] = implode(',', $request->input('regions_id'));
        }
        if (isset($inputData['keywords']) && $inputData['keywords']) {
            $params['item_name|contains'] = trim($inputData['keywords']);
        }

        if (isset($inputData['nospec'])) {
            $params['nospec'] = $inputData['nospec'];
        }

        if (isset($inputData['is_gift'])) {
            $params['is_gift'] = ($inputData['is_gift'] == 'true') ? true : false;
        }
        if ((!isset($params['is_gift']) || !$params['is_gift']) && isset($inputData['approve_status']) && $inputData['approve_status']) {
            $params['approve_status'] = $request->input('approve_status');
        }

        $distributorId = $request->get('distributor_id') ?: $request->input('distributor_id', 0);
        $params['distributor_id'] = $distributorId;

        if (isset($inputData['approve_status']) && $inputData['approve_status']) {
            if (in_array($request->input('approve_status'), ['processing', 'rejected'])) {
                $params['audit_status'] = $request->input('approve_status');
            } else {
                $params['approve_status'] = $request->input('approve_status');
            }
        }

        if (isset($inputData['audit_status']) && $inputData['audit_status']) {
            //如果不是分销池商品审核
            if ($request->input('audit_status') == 'rebate') {
            } else {
                $params['audit_status'] = $request->input('audit_status');
            }
            if (!$params['distributor_id']) {
                unset($params['distributor_id']);
                $params['distributor_id|neq'] = 0;
            }
        }

        if (isset($inputData['rebate']) && in_array($inputData['rebate'], [1, 0,2,3])) {
            $params['rebate'] = $request->input('rebate');
        }
        if (isset($inputData['rebate_type']) && $inputData['rebate_type']) {
            $params['rebate_type'] = $request->input('rebate_type');
        }

        if (isset($inputData['item_id']) && $inputData['item_id']) {
            $params['item_id'] = $inputData['item_id'];
            if (!$params['distributor_id']) {
                unset($params['distributor_id']);
            }
        }

        if (isset($inputData['main_cat_id']) && $inputData['main_cat_id']) {
            $itemsCategoryService = new ItemsCategoryService();
            $itemCategory = $itemsCategoryService->getMainCatChildIdsBy($inputData['main_cat_id'], $params['company_id']);
            $itemCategory[] = $inputData['main_cat_id'];
            $params['item_category'] = $itemCategory;
        }

        if (isset($inputData['category']) && $inputData['category']) {
            $itemsCategoryService = new ItemsCategoryService();
            $ids = $itemsCategoryService->getItemIdsByCatId($inputData['category'], $params['company_id']);
            if (!$ids) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }

            if (isset($params['item_id'])) {
                $params['item_id'] = array_intersect($params['item_id'], $ids);
            } else {
                $params['item_id'] = $ids;
            }
        }

        $params['item_type'] = $request->input('item_type', 'services');

        if ($inputData['store_gt'] ?? 0) {
            $params["store|gt"] = intval($inputData['store_gt']);
        }

        if ($inputData['store_lt'] ?? 0) {
            $params["store|lt"] = intval($inputData['store_lt']);
        }

        if ($inputData['price_gt'] ?? 0) {
            $params["price|gt"] = bcmul($inputData['price_gt'], 100);
        }

        if ($inputData['price_lt'] ?? 0) {
            $params["price|lt"] = bcmul($inputData['price_lt'], 100);
        }

        if (isset($inputData['special_type']) && in_array($inputData['special_type'], ['normal', 'drug'])) {
            $params['special_type'] = $inputData['special_type'];
        }

        $itemStoreService = new ItemStoreService();
        $warningStore = $itemStoreService->getWarningStore($params['company_id'], $distributorId);
        if (isset($inputData['is_warning']) && $inputData['is_warning'] == 'true') {
            $params['store|lte'] = $warningStore;
        }

        if (isset($inputData['tag_id']) && $inputData['tag_id']) {
            $itemsTagsService = new ItemsTagsService();
            $filter = ['company_id' => $params['company_id'], 'tag_id' => $inputData['tag_id']];
            if (isset($params['item_id']) && $params['item_id']) {
                $filter['item_id'] = $params['item_id'];
            }
            $itemIds = $itemsTagsService->getItemIdsByTagids($filter);
            if (!$itemIds) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            $params['item_id'] = $itemIds;
        }

        if ($inputData['brand_id'] ?? 0) {
            $params["brand_id"] = $inputData['brand_id'];
        }

        $page = intval($inputData['page']);
        $pageSize = intval($inputData['pageSize']);
        $itemsService = new ItemsService();
        if (isset($inputData['item_bn']) && $inputData['item_bn']) {
            $params['item_bn'] = $inputData['item_bn'];
            $datalist = $itemsService->getItemsLists($params, 'default_item_id,item_id');
            if (!$datalist) {
                $result['list'] = [];
                $result['total_count'] = 0;
                return $this->response->array($result);
            }
            unset($params['item_bn']);
            $params['item_id'] = array_column($datalist, 'default_item_id');
        }

        if (isset($inputData['is_sku']) && $inputData['is_sku'] == 'true') {
            $isGetSkuList = true;
        } else {
            $isGetSkuList = false;
            $params['is_default'] = true;
        }

        if ($isGetSkuList) {
            if (isset($params['item_id']) && $params['item_id']) {
                $params['default_item_id'] = $params['item_id'];
                unset($params['item_id']);
                $pageSize = -1;
            }
            $result = $itemsService->getSkuItemsList($params, $page, $pageSize);
        } else {
            $pageSize = ($pageSize <= 0) ? 10 : $pageSize;
            $result = $itemsService->getItemsList($params, $page, $pageSize);
        }
        $result['warning_store'] = $warningStore;

        $activityId = 0;
        if (isset($inputData['activity_id']) && $inputData['activity_id']) {
            $activityId = $inputData['activity_id'];
        }
        $params['activity_start_time'] = strtotime($inputData['activity_start_time']);
        $params['activity_end_time'] = strtotime($inputData['activity_end_time']);
        $params['activity_release_time'] = strtotime($inputData['activity_release_time']);
        $params['seckill_type'] = $inputData['seckill_type'] ?? 'normal';
        $params['marketing_type'] = $inputData['marketing_type'] ?? '';

        $list['list']['validItems'] = [];
        $list['list']['invalidItems'] = [];
        if ($result['list']) {
            //获取商品标签
            $itemIds = array_column($result['list'], 'item_id');
            $tagFilter = [
                'item_id' => $itemIds,
                'company_id' => $params['company_id'],
            ];
            $itemsTagsService = new ItemsTagsService();
            $tagList = $itemsTagsService->getItemsRelTagList($tagFilter);
            foreach ($tagList as $tag) {
                $newTags[$tag['item_id']][] = $tag;
            }

            $itemsCategoryService = new ItemsCategoryService();

            foreach ($result['list'] as &$value) {
                $value['tagList'] = $newTags[$value['item_id']] ?? [];
                $categoryInfo = $itemsCategoryService->getInfoById($value['item_main_cat_id']);
                $value['itemMainCatName'] = $categoryInfo['category_name'] ?? '';

                $cat_arr = [];
                foreach (($value['item_cat_id'] ?? []) as &$v) {
                    $cat_info = $itemsCategoryService->getInfoById($v);
                    if ($cat_info) {
                        $cat_arr[] = '['.$cat_info['category_name'].']';
                    }
                }
                $value['itemCatName'] = $cat_arr;

                try {
                    if ($params['marketing_type']) {
                        $this->checkValidItem($params['company_id'], $params['marketing_type'], $value['item_id'], $params['activity_release_time'], $params['activity_end_time'], $activityId);
                    } else {
                        $this->checkActivityValid($params['company_id'], $value['item_id'], $params['activity_release_time'], $params['activity_end_time'], $activityId);
                    }
                    $list['list']['validItems'][] = $value;
                } catch (ResourceException $exception) {
                    $list['list']['invalidItems'][] = $value;
                    continue;
                }
            }
        }

        return $this->response->array($list);
    }
}
