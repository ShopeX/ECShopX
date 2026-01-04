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
class MemberDetail
{
    /**
     * @SWG\Property( property="user_id", type="string", example="20264", description="用户id"),
     * @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     * @SWG\Property( property="grade_id", type="string", example="4", description="等级ID | 等级id | 会员等级"),
     * @SWG\Property( property="mobile", type="string", example="18321148690", description="手机号"),
     * @SWG\Property( property="user_card_code", type="string", example="324A50B01181", description="会员卡号"),
     * @SWG\Property( property="offline_card_code", type="string", example="null", description="线下会员卡号"),
     * @SWG\Property( property="inviter_id", type="string", example="0", description="推荐人id"),
     * @SWG\Property( property="source_from", type="string", example="default", description="来源类型 default默认"),
     * @SWG\Property( property="source_id", type="string", example="0", description="来源id"),
     * @SWG\Property( property="monitor_id", type="string", example="0", description="监控id"),
     * @SWG\Property( property="latest_source_id", type="string", example="0", description="最近来源id"),
     * @SWG\Property( property="latest_monitor_id", type="string", example="0", description="最近监控页面id"),
     * @SWG\Property( property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="公众号的appid"),
     * @SWG\Property( property="use_point", type="string", example="false", description="是否可以使用积分"),
     * @SWG\Property( property="wxa_appid", type="string", example="wx912913df9fef6ddd", description="小程序的appid"),
     * @SWG\Property( property="created", type="string", example="1598845028", description=""),
     * @SWG\Property( property="updated", type="string", example="1600917506", description="修改时间"),
     * @SWG\Property( property="disabled", type="string", example="false", description="是否禁用 true=禁用,false=启用"),
     * @SWG\Property( property="remarks", type="string", example="null", description="备注"),
     * @SWG\Property( property="third_data", type="string", example="100102866937", description="百胜等第三方返回的数据"),
     * @SWG\Property( property="username", type="string", example="null", description="名称"),
     * @SWG\Property( property="avatar", type="string", example="https://thirdwx.qlogo.cn/mmopen/vi_32/28Qz0boz9fjJYJiapHjxu5nNcBKDZMcNlrpctITqfTawwnsw8Wu9Af4k6DzIXlSv01m3nvxV48Nic9JsroJ9NuGA/132", description="头像"),
     * @SWG\Property( property="sex", type="string", example="0", description="性别。0 未知；1 男；2 女"),
     * @SWG\Property( property="birthday", type="string", example="null", description="出生日期"),
     * @SWG\Property( property="address", type="string", example="null", description="具体地址"),
     * @SWG\Property( property="email", type="string", example="null", description="常用邮箱"),
     * @SWG\Property( property="industry", type="string", example="null", description="所属行业 | 从事行业"),
     * @SWG\Property( property="income", type="string", example="null", description="收入"),
     * @SWG\Property( property="edu_background", type="string", example="null", description="学历"),
     * @SWG\Property( property="habbit", type="array",
     *     @SWG\Items( type="string", example="undefined", description="爱好"),
     * ),
     * @SWG\Property( property="have_consume", type="string", example="true", description="是否有消费"),
     */
}
