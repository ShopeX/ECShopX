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

namespace MembersBundle\Http\Api\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class Member
{
    /**
     *                  @SWG\Property( property="user_id", type="string", example="20399", description="用户id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="grade_id", type="string", example="4", description="等级ID"),
     *                  @SWG\Property( property="mobile", type="string", example="18530870713", description="手机号"),
     *                  @SWG\Property( property="user_card_code", type="string", example="A3514D180BA5", description="会员卡号"),
     *                  @SWG\Property( property="offline_card_code", type="string", example="null", description="线下会员卡号"),
     *                  @SWG\Property( property="inviter_id", type="string", example="0", description="推荐人id"),
     *                  @SWG\Property( property="source_from", type="string", example="default", description="来源类型 default默认"),
     *                  @SWG\Property( property="source_id", type="string", example="0", description="来源id"),
     *                  @SWG\Property( property="monitor_id", type="string", example="0", description="监控id"),
     *                  @SWG\Property( property="latest_source_id", type="string", example="0", description="最近来源id"),
     *                  @SWG\Property( property="latest_monitor_id", type="string", example="0", description="最近监控页面id"),
     *                  @SWG\Property( property="authorizer_appid", type="string", example="", description="公众号的appid "),
     *                  @SWG\Property( property="use_point", type="string", example="false", description="是否可以使用积分"),
     *                  @SWG\Property( property="wxa_appid", type="string", example="", description="appid"),
     *                  @SWG\Property( property="created", type="string", example="1611903667", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1611903667", description="修改时间"),
     *                  @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     *                  @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     *                  @SWG\Property( property="third_data", type="string", example="null", description="第三方数据"),
     */
}
