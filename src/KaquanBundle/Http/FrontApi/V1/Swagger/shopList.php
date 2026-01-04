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

namespace KaquanBundle\Http\FrontApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class shopList
{
    /**
     * @SWG\Property( property="wxShopId", type="string", example="1", description="微信门店ID"),
     * @SWG\Property( property="mapPoiId", type="string", example="18291581753863139488", description="从腾讯地图换取的位置点id"),
     * @SWG\Property( property="picList", type="string", example="[]", description="门店图片"),
     * @SWG\Property( property="contractPhone", type="string", example="02199663333", description="联系电话"),
     * @SWG\Property( property="hour", type="string", example="08:00 - 20:00", description="营业时间"),
     * @SWG\Property( property="credential", type="string", example="null", description="经营资质证件号"),
     * @SWG\Property( property="companyName", type="string", example="null", description="主体名字"),
     * @SWG\Property( property="qualificationList", type="string", example="null", description="相关证明材料"),
     * @SWG\Property( property="cardId", type="string", example="null", description="卡券id"),
     * @SWG\Property( property="status", type="string", example="5", description="审核状态，1：审核成功，2：审核中，3：审核失败，4：管理员拒绝, 5: 无需审核"),
     * @SWG\Property( property="companyId", type="string", example="1", description="公司ID"),
     * @SWG\Property( property="created", type="string", example="1561146116", description="创建时间"),
     * @SWG\Property( property="updated", type="string", example="1590166439", description="修改时间"),
     * @SWG\Property( property="lng", type="string", example="121.417435", description="腾讯地图纬度"),
     * @SWG\Property( property="lat", type="string", example="31.176539", description="腾讯地图经度"),
     * @SWG\Property( property="address", type="string", example="上海市徐汇区宜山路700号(近桂林路)", description="腾讯地图门店地址"),
     * @SWG\Property( property="category", type="string", example="房产小区:产业园区", description="腾讯地图门店类目"),
     * @SWG\Property( property="poiId", type="string", example="null", description="门店id"),
     * @SWG\Property( property="errmsg", type="string", example="null", description="审核失败原因"),
     * @SWG\Property( property="auditId", type="string", example="null", description="微信返回的审核id"),
     * @SWG\Property( property="resourceId", type="string", example="1", description="资源包id"),
     * @SWG\Property( property="expiredAt", type="string", example="1878362772", description="过期时间"),
     * @SWG\Property( property="isDefault", type="string", example="false", description="是否是默认门店"),
     * @SWG\Property( property="storeName", type="string", example="普天信息产业园", description="腾讯地图的门店名称"),
     * @SWG\Property( property="addType", type="string", example="1", description="1,公众号主体；2,相关主体; 3,无主体"),
     * @SWG\Property( property="country", type="string", example="null", description="非中国国家名称"),
     * @SWG\Property( property="city", type="string", example="null", description="非中国门店所在城市"),
     * @SWG\Property( property="isDomestic", type="string", example="1", description="是否是中国国内门店 1:国内(包含港澳台),2:非国内"),
     * @SWG\Property( property="isDirectStore", type="string", example="1", description="是否为直营店 1:直营店,2:非直营店"),
     * @SWG\Property( property="isOpen", type="string", example="true", description="是否开启 1:开启,0:关闭"),
     * @SWG\Property( property="distributorId", type="string", example="0", description="门店所属店铺ID"),
     * @SWG\Property( property="is_valid", type="string", example="true", description=""),
     */
}
