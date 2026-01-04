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
class MemberGrade
{
    // Powered by ShopEx EcShopX
    /**
     * @SWG\Property( property="grade_id", type="string", example="4", description="等级ID"),
     * @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     * @SWG\Property( property="grade_name", type="string", example="普通会员", description="等级名称"),
     * @SWG\Property( property="default_grade", type="string", example="true", description="是否默认等级"),
     * @SWG\Property( property="background_pic_url", type="string", example="http://bbctest.aixue7.com/1/2019/12/09/ab7b9466293172e51a8f5856135e3349003xcJT4n4uMYEB9SyovqxzYdaj1Wi7W", description="背景图"),
     * @SWG\Property( property="promotion_condition", type="object",
     *         @SWG\Property( property="total_consumption", type="string", example="0", description=""),
     * ),
     * @SWG\Property( property="privileges", type="object",
     *         @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *         @SWG\Property( property="discount_desc", type="string", example="8", description=""),
     * ),
     * @SWG\Property( property="created", type="string", example="1561461060", description=""),
     * @SWG\Property( property="updated", type="string", example="1611803385", description="修改时间"),
     * @SWG\Property( property="third_data", type="string", example="1111", description="第三方数据"),
     * @SWG\Property( property="member_count", type="string", example="147", description="新增会员数"),
     * @SWG\Property( property="crm_open", type="string", example="false", description=""),
     */
}
