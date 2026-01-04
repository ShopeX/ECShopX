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

namespace OpenapiBundle\Constants;

use EspierBundle\Services\Constants\BaseErrorCode;

/**
 * 对外开放的接口 错误码
 * 错误码的描述定义规范：主要写在@message注解中，并在内容的前后添加双引号
 * 错误码定义规范：目前是四位数
 *      第一位：场景【4 接口相关】【5 业务相关】
 *      第二位：大模块类型（目前只适用 业务相关）。【0 通用】【1 商品】【2 库存】【3 订单】【4 店铺】【5 会员】【6 导购】【7 微信小程序相关】
 *      第三位：该模块下的分类。
 *      第四位：对应分类下对各个操作错误的细分。【0 操作错误】【1 找不到数据】【2 已存在数据】【3 删除错误】
 */
class ErrorCode extends BaseErrorCode
{
    /**
     * ===================== 商品 【开始】 ===========================
     * 第三位：【0基础信息】，【1商品品牌】，【2商品分类】，【3商品主类目】
     * 第四位：【0错误】，【1找不到】，【2已存在】，【3删除失败】
     */

    /**
     * @message("商品错误")
     */
    public const GOODS_ERROR = "5100";

    /**
     * @message("商品找不到")
     */
    public const GOODS_NOT_FOUND = "5101";

    /**
     * @message("商品删除错误")
     */
    public const GOODS_DELETE_ERROR = "5103";

    /**
     * @message("商品品牌错误")
     */
    public const GOODS_BRAND_ERROR = "5110";

    /**
     * @message("商品品牌删除错误")
     */
    public const GOODS_BRAND_DELETE_ERROR = "5113";

    /**
     * @message("商品分类错误")
     */
    public const GOODS_CATEGORY_ERROR = "5120";

    /**
     * @message("商品分类删除错误")
     */
    public const GOODS_CATEGORY_DELETE_ERROR = "5123";

    /**
     * @message("商品主类目错误")
     */
    public const GOODS_MAINCATEGORY_ERROR = "5130";

    /**
     * @message("商品主类目找不到")
     */
    public const GOODS_MAINCATEGORY_NOT_FOUND = "5131";


    /** ===================== 商品 【结束】 =========================== **/

    /** ===================== 库存 【开始】 =========================== **/

    /**
     * @message("库存错误")
     */
    public const STORE_ERROR = "5200";

    /** ===================== 库存 【结束】 =========================== **/

    /** ===================== 订单 【开始】 ===========================
    * 第三位：【0基础信息】，【1订单处理】，【2订单售后】，【3会员】，【4退款单】
    * 第四位：【0错误】，【1找不到】，【2已存在】，【3删除失败】
    */

    /**
     * @message("订单错误")
     */
    public const ORDER_ERROR = "5300";

    /**
     * @message("订单相应的明细不存在")
     */
    public const ORDER_NOT_FOUND = "5301";

    /**
     * @message("订单处理错误")
     */
    public const ORDER_HANDLE_ERROR = "5310";

    /**
     * @message("订单已处理")
     */
    public const ORDER_HANDLE_EXIST = "5312";

    /**
     * @message("订单售后处理错误")
     */
    public const ORDER_AFTERSALES_HANDLE_ERROR = "5320";

    /**
     * @message("订单退款单处理错误")
     */
    public const ORDER_REFUND_HANDLE_ERROR = "5340";

    /**
     * @message("会员找不到")
     */
    public const ORDER_MEMBER_NOT_FOUND = "5331";

    /** ===================== 订单 【结束】 =========================== **/

    /**
     * ===================== 店铺 【开始】 ===========================
     * 第三位：【0基础信息】，【1店铺商品】
     */

    /**
     * @message("店铺错误")
     */
    public const DISTRIBUTOR_ERROR = "5400";

    /**
     * @message("店铺找不到")
     */
    public const DISTRIBUTOR_NOT_FOUND = "5401";

    /**
     * @message("店铺已存在")
     */
    public const DISTRIBUTOR_EXIST = "5402";

    /**
     * @message("店铺商品错误")
     */
    public const DISTRIBUTOR_ITEM_ERROR = "5410";

    /** ===================== 店铺 【结束】 =========================== **/

    /**
     * ===================== 会员 【开始】 ===========================
     * 第三位：【0基础信息】，【1推荐人】，【2会员卡基础设置】，【3会员卡等级】，【4付费会员等级】，【5积分】，【6会员标签】，【7会员标签分类】，【8会员储值】
     * 第四位：【0错误】，【1找不到】，【2已存在】，【3删除失败】
     */

    /**
     * @message("会员错误")
     */
    public const MEMBER_ERROR = "5500";

    /**
     * @message("会员找不到")
     */
    public const MEMBER_NOT_FOUND = "5501";

    /**
     * @message("会员已存在")
     */
    public const MEMBER_EXIST = "5502";

    /**
     * @message("会员的推荐人找不到")
     */
    public const MEMBER_INVITER_NOT_FOUND = "5511";

    /**
     * @message("会员卡号已存在")
     */
    public const MEMBER_CARD_EXIST = "5522";

    /**
     * @message("会员等级错误")
     */
    public const MEMBER_GRADE_ERROR = "5530";

    /**
     * @message("会员等级找不到")
     */
    public const MEMBER_GRADE_NOT_FOUND = "5531";

    /**
     * @message("会员等级删除失败")
     */
    public const MEMBER_GRADE_DELETE_ERROR = "5533";

    /**
     * @message("会员付费等级错误")
     */
    public const MEMBER_VIP_GRADE_ERROR = "5540";

    /**
     * @message("会员付费等级找不到")
     */
    public const MEMBER_VIP_GRADE_NOT_FOUND = "5541";

    /**
     * @message("会员付费等级已存在")
     */
    public const MEMBER_VIP_GRADE_EXIST = "5542";

    /**
     * @message("会员付费等级删除错误")
     */
    public const MEMBER_VIP_GRADE_DELETE_ERROR = "5543";

    /**
     * @message("会员积分错误")
     */
    public const MEMBER_POINT_ERROR = "5550";

    /**
     * @message("会员标签错误")
     */
    public const MEMBER_TAG_ERROR = "5560";

    /**
     * @message("会员标签找不到")
     */
    public const MEMBER_TAG_NOT_FOUND = "5561";

    /**
     * @message("会员标签已存在")
     */
    public const MEMBER_TAG_EXIST = "5562";

    /**
     * @message("会员标签分类错误")
     */
    public const MEMBER_TAGCATEGORY_ERROR = "5570";

    /**
     * @message("该标签分类不存在")
     */
    public const MEMBER_TAGCATEGORY_NOT_FOUND = "5571";

    /**
     * @message("该分类名已存在")
     */
    public const MEMBER_TAGCATEGORY_EXIST = "5572";

    /**
     * @message("会员储值错误")
     */
    public const MEMBER_RECHARGE_ERROR = "5580";

    /**
     * @message("会员储值找不到")
     */
    public const MEMBER_RECHARGE_NOT_FOUND = "5581";

    /** ===================== 会员 【结束】 =========================== **/

    /**
     * ==================== 导购 【开始】 ===========================
     * 第三位：【0基础信息】，【1导购关联的会员信息】
     */

    /**
     * @message("导购错误")
     */
    public const SALESPERSON_ERROR = "5600";

    /**
     * @message("导购找不到")
     */
    public const SALESPERSON_NOT_FOUND = "5601";

    /**
     * @message("该会员已与导购绑定")
     */
    public const SALESPERSON_RELATION_MEMBER_EXIST = "5612";

    /** ===================== 导购 【结束】 =========================== **/

    /**
     * ==================== 微信相关 【开始】 ===========================
     * 第三位：【0微信小程序】
     * 第四位：【0错误】
     */

    /**
     * @message("微信小程序信息有误")
     */
    public const WECHAT_ERROR = "5700";

    /** ===================== 微信相关 【结束】 =========================== **/

    /**
     * @message("验签缺少参数")
     */
    public const VALIDATION_MISSING_PARAMS = "4001";

    /**
     * @message("timestamp 不合法")
     */
    public const VALIDATION_TIMESTAMP_ERROR = "4002";

    /**
     * @message("app_key 错误")
     */
    public const VALIDATION_APPKEY_ERROR = "4003";

    /**
     * @message("签名错误")
     */
    public const SIGN_ERROR = "4004";

    /**
     * @message("找不到API")
     */
    public const API_NOT_FOUND = "4005";

    /**
     * @message("该版本号不存在API")
     */
    public const API_VERSION_NOT_FOUND = "4006";

    /**
     * @message("找不到方法")
     */
    public const API_FUNCTION_NOT_FOUND = "4007";

    /** ===================== 通用错误码 【开始】 =========================== **/

    /**
     * @message("未知错误")
     */
    public const SERVICE_ERROR = "5000";

    /**
     * @message("缺少参数")
     */
    public const SERVICE_MISSING_PARAMS = "5001";

    /**
     * @message("参数格式错误")
     */
    public const SERVICE_PARAMS_FORMAT_ERROR = "5002";

    /** ===================== 通用错误码 【结束】 =========================== **/
}
