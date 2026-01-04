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

namespace KaquanBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class UserDiscount
{
    // Powered by ShopEx EcShopX
    /**
     * @SWG\Property( property="id", type="string", example="49536", description="ID"),
     * @SWG\Property( property="user_id", type="string", example="20350", description="用户id"),
     * @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     * @SWG\Property( property="card_id", type="string", example="590", description="卡券id"),
     * @SWG\Property( property="code", type="string", example="010207545797", description="卡券 code 序列号"),
     * @SWG\Property( property="status", type="string", example="1", description="状态"),
     * @SWG\Property( property="card_type", type="string", example="cash", description="绑卡类型"),
     * @SWG\Property( property="begin_date", type="string", example="1611676800", description="有效期开始时间"),
     * @SWG\Property( property="end_date", type="string", example="1614355199", description="有效期结束时间"),
     * @SWG\Property( property="title", type="string", example="代金10元", description="标题 "),
     * @SWG\Property( property="color", type="string", example="#000000", description="券颜色值"),
     * @SWG\Property( property="discount", type="string", example="0", description="折扣值"),
     * @SWG\Property( property="least_cost", type="string", example="2100", description="代金券起用金额"),
     * @SWG\Property( property="reduce_cost", type="string", example="1000", description="代金券减免金额 or 兑换券起用金额"),
     * @SWG\Property( property="use_condition", type="object",
     *         @SWG\Property( property="accept_category", type="string", example="null", description="指定可用的商品类目,代金券专用"),
     *         @SWG\Property( property="reject_category", type="string", example="null", description="指定不可用的商品类目,代金券专用"),
     *         @SWG\Property( property="least_cost", type="string", example="2100", description="代金券起用金额"),
     *         @SWG\Property( property="object_use_for", type="string", example="null", description="购买xx可用类型门槛，仅用于兑换"),
     *         @SWG\Property( property="can_use_with_other_discount", type="string", example="false", description="是否可与其他优惠共享"),
     * ),
     * @SWG\Property( property="rel_shops_ids", type="string", example="all", description="适用的门店"),
     * @SWG\Property( property="is_give_by_friend", type="string", example="null", description="是否为转赠领取"),
     * @SWG\Property( property="old_code", type="string", example="null", description="转赠之后,旧的 code 序列号"),
     * @SWG\Property( property="get_outer_str", type="string", example="null", description="领取场景值"),
     * @SWG\Property( property="consume_source", type="string", example="null", description="核销来源"),
     * @SWG\Property( property="location_name", type="string", example="null", description="核销卡券的门店名称"),
     * @SWG\Property( property="staff_open_id", type="string", example="null", description="卡券核销员"),
     * @SWG\Property( property="verify_code", type="string", example="null", description="自助核销的验证码"),
     * @SWG\Property( property="remark_amount", type="string", example="null", description="自助核销时备注金额"),
     * @SWG\Property( property="consume_outer_str", type="string", example="null", description="核销渠道"),
     * @SWG\Property( property="trans_id", type="string", example="null", description="微信支付交易订单号,买单核销专用"),
     * @SWG\Property( property="fee", type="string", example="null", description="实付金额,买单核销专用"),
     * @SWG\Property( property="original_fee", type="string", example="null", description="应付金额,买单核销专用"),
     * @SWG\Property( property="location_id", type="string", example="null", description="当前卡券核销的门店ID"),
     * @SWG\Property( property="friend_open_id", type="string", example="null", description="转赠卡券时接收方 open_id"),
     * @SWG\Property( property="is_return_back", type="string", example="null", description="转赠时是否退回"),
     * @SWG\Property( property="is_chat_room", type="string", example="null", description="是否群转赠"),
     * @SWG\Property( property="source_type", type="string", example="大转盘中奖领取", description="卡券来源类型"),
     * @SWG\Property( property="use_scenes", type="string", example="ONLINE", description="核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)"),
     * @SWG\Property( property="use_platform", type="string", example="mall", description="优惠券适用平台（mall:线上商城专用, store:门店专用）"),
     * @SWG\Property( property="rel_item_ids", type="string", example="all", description="使用商品"),
     * @SWG\Property( property="most_cost", type="string", example="99999900", description="代金券最高消费限额"),
     * @SWG\Property( property="rel_distributor_ids", type="string", example="all", description="使用商品"),
     * @SWG\Property( property="get_date", type="string", example="1611755928", description="优惠券获取时间"),
     * @SWG\Property( property="salesperson_id", type="string", example="0", description="导购员id"),
     * @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     * @SWG\Property( property="rel_category_ids", type="string", example="null", description="可使用的类目"),
     * @SWG\Property( property="apply_scope", type="string", example="", description="适用范围"),
     * @SWG\Property( property="use_limited", type="string", example="0", description="是否可以多次使用,仅记录无业务"),
     * @SWG\Property( property="remain_times", type="string", example="1", description="剩余使用次数,仅记录无业务"),
     * @SWG\Property( property="begin_date_str", type="string", example="2021-01-27 00:00:00", description=""),
     * @SWG\Property( property="end_date_str", type="string", example="2021-02-26 23:59:59", description=""),
     * @SWG\Property( property="username", type="string", example="测试", description="姓名"),
     * @SWG\Property( property="mobile", type="string", example="17638125092", description="手机号"),
     */
}
