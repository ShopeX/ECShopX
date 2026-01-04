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

namespace KaquanBundle\Http\AdminApi\V2\Action;

use App\Http\Controllers\Controller as BaseController;
use Illuminate\Http\Request;
use KaquanBundle\Services\UserDiscountService;

class UserDiscount extends BaseController
{
    /**
     * @SWG\Get(
     *     path="/wxapp/couponrecord",
     *     summary="导购员获取赠券记录",
     *     tags={"卡券"},
     *     description="导购员获取赠券记录",
     *     operationId="getGiveCouponsList",
     *     @SWG\Parameter( name="Accept", in="header", description="V2版本接口标识", required=true, type="string", default="application/vnd.espier.v2+json" ),
     *     @SWG\Parameter( name="x-wxapp-session", in="header", description="导购token", required=true, type="string", default="{{x-wxapp-session}}"),
     *     @SWG\Parameter( name="salesperson-type", in="header", description="登陆类型", required=true, type="string", default="shopping_guide" ),
     *     @SWG\Parameter( name="start", in="query", description="开始时间", type="integer" ),
     *     @SWG\Parameter( name="end", in="query", description="结束时间", type="integer" ),
     *     @SWG\Response( response=200, description="成功返回结构", @SWG\Schema(
     *          @SWG\Property( property="data", type="array",
     *              @SWG\Items( type="object",
     *                  @SWG\Property( property="card_id", type="string", example="357", description="卡券id"),
     *                  @SWG\Property( property="company_id", type="string", example="60", description="公司id"),
     *                  @SWG\Property( property="card_type", type="string", example="discount", description="优惠券类型，可选值有 discount 折扣券;cash:代金券;gift:兑换券"),
     *                  @SWG\Property( property="brand_name", type="string", example="null", description="商户名称"),
     *                  @SWG\Property( property="logo_url", type="string", example="null", description="卡券商户 logo"),
     *                  @SWG\Property( property="title", type="string", example="asdf", description="标题"),
     *                  @SWG\Property( property="color", type="string", example="#000000", description="券颜色值"),
     *                  @SWG\Property( property="notice", type="string", example="null", description="卡券使用提醒,最大16汉字"),
     *                  @SWG\Property( property="description", type="string", example="ddfd", description="内容"),
     *                  @SWG\Property( property="date_type", type="string", example="DATE_TYPE_FIX_TIME_RANGE", description="有效期的类型, DATE_TYPE_FIX_TIME_RANGE:指定日期范围内, DATE_TYPE_FIX_TERM:固定天数后"),
     *                  @SWG\Property( property="begin_date", type="string", example="1587398400", description="有效期开始时间"),
     *                  @SWG\Property( property="end_date", type="string", example="1587484800", description="有效期结束时间"),
     *                  @SWG\Property( property="fixed_term", type="string", example="null", description="有效期的有效天数"),
     *                  @SWG\Property( property="service_phone", type="string", example="null", description="客服电话"),
     *                  @SWG\Property( property="center_title", type="string", example="null", description="卡券顶部居中的按钮，仅在卡券状态正常(可以核销)时显示"),
     *                  @SWG\Property( property="center_sub_title", type="string", example="null", description="显示在入口下方的提示语"),
     *                  @SWG\Property( property="center_url", type="string", example="null", description="顶部居中的url"),
     *                  @SWG\Property( property="custom_url_name", type="string", example="null", description="自定义跳转外链的入口名字"),
     *                  @SWG\Property( property="custom_url", type="string", example="null", description="自定义跳转的URL"),
     *                  @SWG\Property( property="custom_url_sub_title", type="string", example="null", description="显示在入口右侧的提示语"),
     *                  @SWG\Property( property="promotion_url_name", type="string", example="null", description="营销场景的自定义入口名称"),
     *                  @SWG\Property( property="promotion_url", type="string", example="null", description="营销场景的自定义入口url"),
     *                  @SWG\Property( property="promotion_url_sub_title", type="string", example="null", description="营销入口右侧的提示语"),
     *                  @SWG\Property( property="get_limit", type="string", example="1", description="每人可领券的数量限制"),
     *                  @SWG\Property( property="use_limit", type="string", example="null", description="每人可核销的数量限制"),
     *                  @SWG\Property( property="can_share", type="string", example="false", description="卡券领取页面是否可分享"),
     *                  @SWG\Property( property="can_give_friend", type="string", example="false", description="卡券是否可转赠"),
     *                  @SWG\Property( property="abstract", type="string", example="null", description="封面摘要"),
     *                  @SWG\Property( property="icon_url_list", type="string", example="null", description="封面图片"),
     *                  @SWG\Property( property="text_image_list", type="string", example="N;", description="图文列表(DC2Type:array)"),
     *                  @SWG\Property( property="time_limit", type="string", example="", description="使用时段限制(DC2Type:array)"),
     *                  @SWG\Property( property="gift", type="string", example="null", description="兑换券兑换内容名称"),
     *                  @SWG\Property( property="default_detail", type="string", example="null", description="优惠券优惠详情"),
     *                  @SWG\Property( property="discount", type="string", example="90", description="折扣值"),
     *                  @SWG\Property( property="least_cost", type="string", example="0", description="代金券起用金额"),
     *                  @SWG\Property( property="reduce_cost", type="string", example="0", description="代金券减免金额 or 兑换券起用金额"),
     *                  @SWG\Property( property="deal_detail", type="string", example="null", description="团购券详情"),
     *                  @SWG\Property( property="accept_category", type="string", example="null", description="指定可用的商品类目,代金券专用"),
     *                  @SWG\Property( property="reject_category", type="string", example="null", description="指定不可用的商品类目,代金券专用"),
     *                  @SWG\Property( property="object_use_for", type="string", example="null", description="购买xx可用类型门槛，仅用于兑换"),
     *                  @SWG\Property( property="can_use_with_other_discount", type="string", example="", description="是否可与其他优惠共享"),
     *                  @SWG\Property( property="quantity", type="string", example="10", description="数量"),
     *                  @SWG\Property( property="use_all_shops", type="string", example="1", description="是否适用所有门店"),
     *                  @SWG\Property( property="rel_shops_ids", type="string", example=",", description="适用的门店"),
     *                  @SWG\Property( property="created", type="string", example="1587353489", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1587353489", description="修改时间"),
     *                  @SWG\Property( property="use_scenes", type="string", example="ONLINE", description="核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)"),
     *                  @SWG\Property( property="receive", type="string", example="true", description="是否前台直接领取"),
     *                  @SWG\Property( property="self_consume_code", type="string", example="0", description="自助核销验证码"),
     *                  @SWG\Property( property="use_platform", type="string", example="mall", description="优惠券适用平台（mall:线上商城专用, store:门店专用）"),
     *                  @SWG\Property( property="most_cost", type="string", example="99999900", description="代金券最高消费限额"),
     *                  @SWG\Property( property="distributor_id", type="string", example=",", description="店铺ID"),
     *                  @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                  @SWG\Property( property="tag_ids", type="string", example="null", description="标签id集合"),
     *                  @SWG\Property( property="brand_ids", type="string", example="null", description="品牌id集合"),
     *                  @SWG\Property( property="apply_scope", type="string", example="null", description="适用范围"),
     *                  @SWG\Property( property="card_code", type="string", example="null", description="优惠券模板ID-第三方使用"),
     *                  @SWG\Property( property="card_rule_code", type="string", example="null", description="优惠券规则ID-第三方使用"),
     *               ),
     *          ),
     *     )),
     *     @SWG\Response( response="default", description="错误返回结构", @SWG\Schema( type="array", @SWG\Items(ref="#/definitions/CardQuanErrorRespones") ) )
     * )
     */
    public function getCouponsRecord(Request $request)
    {
        $salesperson_info = $this->auth->user();
        $start = $request->input('start', '');
        $end = $request->input('end', '');

        if ($start) {
            $start = date('Ymd', $start);
        }
        if ($end) {
            $end = date('Ymd', $end);
        }

        $filter = [
            'salesperson_id' => $salesperson_info['salesperson_id'],
            'company_id' => $salesperson_info['company_id']
        ];
        if ($start) {
            $filter['date|gte'] = $start;
        }
        if ($end) {
            $filter['date|lte'] = $end + 3600 * 24 - 1;
        }

        $user_discount_service = new UserDiscountService();
        $result = $user_discount_service->getCouponsRecordV2($filter);

        return $this->response->array($result);
    }
}
