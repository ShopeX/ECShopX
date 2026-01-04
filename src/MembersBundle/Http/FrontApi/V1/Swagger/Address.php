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
class Address
{
    // Ref: 1996368445
    /**
     *                          @SWG\Property( property="address_id", type="string", default="415", description="地址id"),
     *                          @SWG\Property( property="company_id", type="string", default="1", description="公司id"),
     *                          @SWG\Property( property="user_id", type="string", default="20264", description="用户id"),
     *                          @SWG\Property( property="username", type="string", default="张三", description="名称"),
     *                          @SWG\Property( property="telephone", type="string", default="18890908989", description="手机号码"),
     *                          @SWG\Property( property="area", type="string", default="null", description="地区"),
     *                          @SWG\Property( property="province", type="string", default="广东省", description="省"),
     *                          @SWG\Property( property="city", type="string", default="广州市", description="市"),
     *                          @SWG\Property( property="county", type="string", default="海珠区", description="区"),
     *                          @SWG\Property( property="adrdetail", type="string", default="新港中路397号", description="详细地址"),
     *                          @SWG\Property( property="postalCode", type="string", default="510000", description="邮编"),
     *                          @SWG\Property( property="is_def", type="string", default="true", description="是否默认地址"),
     *                          @SWG\Property( property="created", type="string", default="1598846042", description=""),
     *                          @SWG\Property( property="updated", type="string", default="1598846042", description="修改时间"),
     *                          @SWG\Property( property="third_data", type="string", default="null", description="百胜等第三方返回的数据"),
     *                          @SWG\Property( property="lat", type="string", default="", description="经度"),
     *                          @SWG\Property( property="lng", type="string", default="", description="纬度"),
     */
}
