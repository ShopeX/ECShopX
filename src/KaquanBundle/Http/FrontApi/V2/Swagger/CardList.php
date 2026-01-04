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

namespace KaquanBundle\Http\FrontApi\V2\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class CardList
{
    // Ver: 8d1abe8e
    /**
     *
     *          @SWG\Property( property="data", type="object",
     *                  @SWG\Property( property="list", type="array",
     *                      @SWG\Items( type="object",
     *                          @SWG\Property( property="id", type="string", example="695", description="id"),
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司ID"),
     *                          @SWG\Property( property="card_id", type="string", example="427", description="卡券id"),
     *                          @SWG\Property( property="code", type="string", example="913781934646", description="卡券 code序列号"),
     *                          @SWG\Property( property="status", type="string", example="1", description="状态"),
     *                          @SWG\Property( property="card_type", type="string", example="discount", description="卡券类型，可选值有，discount:折扣券;cash:代金券;gift:兑换券"),
     *                          @SWG\Property( property="begin_date", type="string", example="2020-12-03", description="有效期开始时间"),
     *                          @SWG\Property( property="end_date", type="string", example="2021-03-03", description="有效期结束时间"),
     *                          @SWG\Property( property="title", type="string", example="满300元打6折", description="卡券名"),
     *                          @SWG\Property( property="color", type="string", example="#000000", description="券颜色值"),
     *                          @SWG\Property( property="discount", type="string", example="40", description="折扣券打折额度（百分比)"),
     *                          @SWG\Property( property="least_cost", type="string", example="30000", description="代金券起用金额"),
     *                          @SWG\Property( property="reduce_cost", type="string", example="0", description="代金券减免金额 or 兑换券起用金额"),
     *                          @SWG\Property( property="use_condition", type="string", example="", description="使用条件字段"),
     *                          @SWG\Property( property="rel_shops_ids", type="string", example="all", description="适用的门店"),
     *                          @SWG\Property( property="is_give_by_friend", type="string", example="null", description="是否为转赠领取"),
     *                          @SWG\Property( property="old_code", type="string", example="null", description="转赠之后,旧的 code 序列号"),
     *                          @SWG\Property( property="get_outer_str", type="string", example="null", description="领取场景值"),
     *                          @SWG\Property( property="consume_source", type="string", example="null", description="核销来源"),
     *                          @SWG\Property( property="location_name", type="string", example="null", description="核销卡券的门店名称"),
     *                          @SWG\Property( property="staff_open_id", type="string", example="null", description="卡券核销员"),
     *                          @SWG\Property( property="verify_code", type="string", example="null", description="自助核销的验证码"),
     *                          @SWG\Property( property="remark_amount", type="string", example="null", description="自助核销时备注金额"),
     *                          @SWG\Property( property="consume_outer_str", type="string", example="null", description="核销渠道"),
     *                          @SWG\Property( property="trans_id", type="string", example="null", description="微信支付交易订单号,买单核销专用"),
     *                          @SWG\Property( property="fee", type="string", example="null", description="实付金额,买单核销专用"),
     *                          @SWG\Property( property="original_fee", type="string", example="null", description="应付金额,买单核销专用"),
     *                          @SWG\Property( property="location_id", type="string", example="null", description="当前卡券核销的门店ID"),
     *                          @SWG\Property( property="friend_open_id", type="string", example="null", description="转赠卡券时接收方 open_id"),
     *                          @SWG\Property( property="is_return_back", type="string", example="null", description="转赠时是否退回"),
     *                          @SWG\Property( property="is_chat_room", type="string", example="null", description="是否群转赠"),
     *                          @SWG\Property( property="source_type", type="string", example="商城本地领取", description="卡券来源类型，可选值有 local:本地卡券,wechat:微信卡券"),
     *                          @SWG\Property( property="use_scenes", type="string", example="QUICK", description="核销场景。可选值有，ONLINE:线上商城(兑换券不可使用);QUICK:快捷买单(兑换券不可使用);SWEEP:门店支付(扫码核销);SELF:到店支付(自助核销)"),
     *                          @SWG\Property( property="use_platform", type="string", example="store", description="优惠券适用平台（线上商城专用 or 门店专用"),
     *                          @SWG\Property( property="rel_item_ids", type="string", example="all", description="使用商品"),
     *                          @SWG\Property( property="most_cost", type="string", example="99999900", description="代金券最高消费限额"),
     *                          @SWG\Property( property="rel_distributor_ids", type="string", example="all", description="使用商品"),
     *                          @SWG\Property( property="get_date", type="string", example="1606976061", description="优惠券获取时间"),
     *                          @SWG\Property( property="salesperson_id", type="string", example="0", description="导购员id"),
     *                          @SWG\Property( property="use_bound", type="string", example="0", description="适用范围: 0:全场可用,1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用"),
     *                          @SWG\Property( property="rel_category_ids", type="string", example="null", description="可使用的类目"),
     *                          @SWG\Property( property="apply_scope", type="string", example="", description="适用范围"),
     *                          @SWG\Property( property="use_limited", type="string", example="0", description="是否可以多次使用,仅记录无业务"),
     *                          @SWG\Property( property="remain_times", type="string", example="1", description="剩余使用次数,仅记录无业务"),
     *                          @SWG\Property( property="description", type="string", example="满300元打6折", description="卡券使用说明"),
     *                          @SWG\Property( property="valid", type="string", example="true", description="是否有效"),
     *                          @SWG\Property( property="coupon", type="object",
     *                                  @SWG\Property( property="card_id", type="string", example="427", description="卡券id"),
     *                                  @SWG\Property( property="title", type="string", example="满300元打6折", description="卡券名"),
     *                                  @SWG\Property( property="code", type="string", example="913781934646", description="卡券code序列号"),
     *                                  @SWG\Property( property="card_type", type="string", example="discount", description="优惠券类型,可选值有 discount:折扣券，cash:代金券，gift:兑换券"),
     *                                  @SWG\Property( property="valid", type="string", example="true", description=""),
     *                                  @SWG\Property( property="discount", type="string", example="40", description="折扣值"),
     *                          ),
     *                       ),
     *                  ),
     *                  @SWG\Property( property="total_count", type="string", example="3", description="总条数"),
     *                  @SWG\Property( property="cur", type="object",
     *                          @SWG\Property( property="id", type="string", example="1", description="ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="currency", type="string", example="CNY", description="货币英文缩写"),
     *                          @SWG\Property( property="title", type="string", example="中国人民币", description="货币描述 "),
     *                          @SWG\Property( property="symbol", type="string", example="￥", description="货币符号"),
     *                          @SWG\Property( property="rate", type="string", example="1", description="货币汇率(与人民币)"),
     *                          @SWG\Property( property="is_default", type="string", example="true", description="是否默认货币"),
     *                          @SWG\Property( property="use_platform", type="string", example="normal", description="适用端。可选值为 service,normal"),
     *                  ),
     *          ),
     */
}
