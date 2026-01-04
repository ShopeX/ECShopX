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
class Distribution
{
    // ShopEx EcShopX Service Component
    /**
     *                          @SWG\Property( property="distributor_id", type="string", example="6", description="店铺ID"),
     *                          @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                          @SWG\Property( property="mobile", type="string", example="13676496826", description="手机号"),
     *                          @SWG\Property( property="address", type="string", example="宜山路700号(近桂林路)", description="具体地址"),
     *                          @SWG\Property( property="name", type="string", example="普天信息产业园", description="名称"),
     *                          @SWG\Property( property="created", type="string", example="1563964390", description=""),
     *                          @SWG\Property( property="updated", type="string", example="1587376299", description="修改时间"),
     *                          @SWG\Property( property="is_valid", type="string", example="false", description="店铺是否有效"),
     *                          @SWG\Property( property="province", type="string", example="上海市", description="省"),
     *                          @SWG\Property( property="city", type="string", example="上海市", description="市"),
     *                          @SWG\Property( property="area", type="string", example="徐汇", description="区"),
     *                          @SWG\Property( property="regions_id", type="array",
     *                              @SWG\Items( type="string", example="310000", description=""),
     *                          ),
     *                          @SWG\Property( property="regions", type="array",
     *                              @SWG\Items( type="string", example="上海市", description=""),
     *                          ),
     *                          @SWG\Property( property="contact", type="string", example="杨二", description="联系人名称"),
     *                          @SWG\Property( property="child_count", type="string", example="0", description="导购员引入的会员数"),
     *                          @SWG\Property( property="shop_id", type="string", example="0", description="门店id"),
     *                          @SWG\Property( property="is_default", type="string", example="false", description="是否是默认门店"),
     *                          @SWG\Property( property="is_ziti", type="string", example="true", description="是否支持自提"),
     *                          @SWG\Property( property="lng", type="string", example="121.417435", description="地图纬度"),
     *                          @SWG\Property( property="lat", type="string", example="31.176539", description="地图经度"),
     *                          @SWG\Property( property="hour", type="string", example="08:00   -   19:00", description="营业时间，格式11:11-12:12"),
     *                          @SWG\Property( property="auto_sync_goods", type="string", example="true", description="自动同步总部商品"),
     *                          @SWG\Property( property="logo", type="string", example="null", description="店铺logo"),
     *                          @SWG\Property( property="banner", type="string", example="null", description="店铺banner"),
     *                          @SWG\Property( property="is_audit_goods", type="string", example="false", description="是否审核店铺商品"),
     *                          @SWG\Property( property="is_delivery", type="string", example="true", description="是否支持配送"),
     *                          @SWG\Property( property="shop_code", type="string", example="null", description="店铺号"),
     *                          @SWG\Property( property="review_status", type="string", example="0", description="入驻审核状态，0未审核，1已审核"),
     *                          @SWG\Property( property="source_from", type="string", example="1", description="店铺来源，1管理端添加，2小程序申请入驻 | 来源类型 default默认"),
     *                          @SWG\Property( property="distributor_self", type="string", example="0", description="是否是总店配置"),
     *                          @SWG\Property( property="is_distributor", type="string", example="true", description="是否是主店铺"),
     *                          @SWG\Property( property="contract_phone", type="string", example="13676496826", description="联系电话"),
     *                          @SWG\Property( property="is_domestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     *                          @SWG\Property( property="is_direct_store", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     *                          @SWG\Property( property="wechat_work_department_id", type="string", example="0", description="企业微信的部门ID"),
     *                          @SWG\Property( property="regionauth_id", type="string", example="0", description="地区id"),
     *                          @SWG\Property( property="is_open", type="string", example="false", description="是否开启 1:开启,0:关闭"),
     *                          @SWG\Property( property="rate", type="string", example="", description="平台服务费率"),
     *                          @SWG\Property( property="store_address", type="string", example="上海市徐汇宜山路700号(近桂林路)", description="门店地址"),
     *                          @SWG\Property( property="store_name", type="string", example="普天信息产业园", description="门店名称"),
     *                          @SWG\Property( property="phone", type="string", example="13676496826", description=""),
     *                          @SWG\Property( property="distance_show", type="string", example="", description=""),
     *                          @SWG\Property( property="distance_unit", type="string", example="", description=""),
     *                          @SWG\Property( property="fav_num", type="string", example="2", description=""),
     */
}
