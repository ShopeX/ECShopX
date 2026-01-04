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

namespace MembersBundle\Http\FrontApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class MemberInfo
{
    // HACK: temporary solution
    /**
     *                      @SWG\Property( property="memberInfo", type="object",
     *                          ref="#/definitions/MemberDetail",
     *                          @SWG\Property( property="gradeInfo", type="object",
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="grade_name", type="string", example="普通会员", description="等级名称"),
     *                                  @SWG\Property( property="default_grade", type="string", example="true", description="是否默认等级"),
     *                                  @SWG\Property( property="background_pic_url", type="string", example="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="会员卡背景图"),
     *                                  @SWG\Property( property="promotion_condition", type="object",
     *                                          @SWG\Property( property="total_consumption", type="string", example="0", description=""),
     *                                  ),
     *                                  @SWG\Property( property="privileges", type="object",
     *                                          @SWG\Property( property="discount", type="string", example="0", description="折扣值"),
     *                                          @SWG\Property( property="discount_desc", type="string", example="0", description=""),
     *                                  ),
     *                          ),
     *                          @SWG\Property( property="nextGradeInfo", type="object",
     *                                  @SWG\Property( property="grade_id", type="string", example="8", description="等级ID"),
     *                                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                                  @SWG\Property( property="grade_name", type="string", example="高级会员", description="等级名称"),
     *                                  @SWG\Property( property="default_grade", type="string", example="false", description="是否默认等级"),
     *                                  @SWG\Property( property="background_pic_url", type="string", example="http://bbctest.aixue7.com/1/2019/12/09/dc1114b979d13cf778446d0dc089024aOUon16uQBRxSdmxvZqQZmCcAOMFxWEzt", description="会员卡背景图"),
     *                                  @SWG\Property( property="promotion_condition", type="object",
     *                                          @SWG\Property( property="total_consumption", type="string", example="1500", description=""),
     *                                  ),
     *                                  @SWG\Property( property="privileges", type="object",
     *                                          @SWG\Property( property="discount", type="string", example="10", description="折扣值"),
     *                                          @SWG\Property( property="discount_desc", type="string", example="9", description=""),
     *                                  ),
     *                                  @SWG\Property( property="created", type="string", example="1561462425", description=""),
     *                                  @SWG\Property( property="updated", type="string", example="1606642797", description="修改时间"),
     *                                  @SWG\Property( property="third_data", type="string", example="", description="百胜等第三方返回的数据"),
     *                                  @SWG\Property( property="member_count", type="string", example="4", description="新增会员数"),
     *                                  @SWG\Property( property="crm_open", type="string", example="false", description="是否开启crm"),
     *                          ),
     *                          @SWG\Property( property="totalConsumption", type="string", example="0", description=""),
     *                          @SWG\Property( property="open_id", type="string", example="", description="openid"),
     *                  ),
     *                  @SWG\Property( property="userDiscountCount", type="string", example="355", description=""),
     *                  @SWG\Property( property="deposit", type="string", example="0", description="余额"),
     *                  @SWG\Property( property="point", type="string", example="0", description="积分"),
     *                  @SWG\Property( property="coupon", type="string", example="358", description="优惠券数量"),
     *                  @SWG\Property( property="luckdraw", type="string", example="0", description=""),
     *                  @SWG\Property( property="point_open_status", type="string", example="false", description="积分开启状态"),
     *                  @SWG\Property( property="cardInfo", type="object",
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="brand_name", type="string", example="111", description="商户名称"),
     *                          @SWG\Property( property="logo_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_png/Hw4Ssicubkrea6fnM3LTSgicDZNfnf6DnpmqgoHL9k1k09oVqlarzOUSBw11h1LiaW1wYazQY1j0KsUm3dbPs9ciaQ/0?wx_fmt=png", description="logo"),
     *                          @SWG\Property( property="title", type="string", example="2222", description="标题"),
     *                          @SWG\Property( property="color", type="string", example="#409EFF", description="券颜色值"),
     *                          @SWG\Property( property="code_type", type="string", example="CODE_TYPE_QRCODE", description="卡券码类型(CODE_TYPE_TEXT CODE_TYPE_BARCODE CODE_TYPE_QRCODE CODE_TYPE_ONLY_QRCODE CODE_TYPE_ONLY_BARCODE CODE_TYPE_NONE)"),
     *                          @SWG\Property( property="background_pic_url", type="string", example="", description="背景图"),
     *                          @SWG\Property( property="created", type="string", example="1604545567", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1604545567", description="修改时间"),
     *                  ),
     *                  @SWG\Property( property="vipgrade", type="object",
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     *                          @SWG\Property( property="vip_type", type="string", example="vip", description="会员类型"),
     *                          @SWG\Property( property="vip_grade_id", type="string", example="1", description="付费会员卡等级ID"),
     *                          @SWG\Property( property="end_date", type="string", example="1601434134", description="有效期结束时间"),
     *                          @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型"),
     *                          @SWG\Property( property="is_had_vip", type="string", example="true", description="是否有过VIP"),
     *                          @SWG\Property( property="is_vip", type="string", example="false", description="是否VIP"),
     *                          @SWG\Property( property="end_time", type="string", example="2020-09-30", description="结束时间"),
     *                          @SWG\Property( property="day", type="string", example="0", description="生日日期"),
     *                          @SWG\Property( property="valid", type="string", example="false", description=""),
     *                          @SWG\Property( property="is_open", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *                          @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     *                          @SWG\Property( property="guide_title", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description="购买引导文本"),
     *                          @SWG\Property( property="background_pic_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/MUQsdY0GdK5nQNFBaEhiao8MfBoP4B70L2rfqJDROzKgwUBvANmHMq9bQV2G1IWibKxK8iaukqbHiaicNkGKZPbX8EA/0?wx_fmt=jpeg", description="商家自定义会员卡背景图"),
     *                  ),
     *                  @SWG\Property( property="is_open_popularize", type="string", example="false", description="是否开启推广员分销"),
     *                  @SWG\Property( property="is_staff", type="string", example="false", description="是否员工"),
     *                  @SWG\Property( property="is_recharge_status", type="string", example="false", description="储值状态"),
     *
     */
}
