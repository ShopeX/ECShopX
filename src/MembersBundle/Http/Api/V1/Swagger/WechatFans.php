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
class WechatFans
{
    /**
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="authorizer_appid", type="string", example="wx6b8c2837f47e8a09", description="公众号的appid"),
     *                  @SWG\Property( property="subscribed", type="string", example="true", description="是否订阅"),
     *                  @SWG\Property( property="open_id", type="string", example="oj4su0TiY8I7qFKcC5AQgBS0zur4", description="openid"),
     *                  @SWG\Property( property="nickname", type="string", example="绝版小饭团", description="昵称"),
     *                  @SWG\Property( property="sex", type="string", example="1", description="性别。0 未知 1 男 2 女"),
     *                  @SWG\Property( property="city", type="string", example="徐汇", description="市"),
     *                  @SWG\Property( property="country", type="string", example="中国", description="国家名称"),
     *                  @SWG\Property( property="province", type="string", example="上海", description="省"),
     *                  @SWG\Property( property="language", type="string", example="zh_CN", description="语言"),
     *                  @SWG\Property( property="headimgurl", type="string", example="http://thirdwx.qlogo.cn/mmopen/PiajxSqBRaEL9Azia8ZDroDevP9HbpW8JaZ2aRRUxUemq5bBagT49GO2sDylpxDUndnzZVZd9C91JWicu0XY1C6EA/132", description="头像url"),
     *                  @SWG\Property( property="subscribe_time", type="string", example="1609299367", description="订阅时间"),
     *                  @SWG\Property( property="unionid", type="string", example="oCzyo5xWr0YLuVk9BeuYrYRYKZds", description="unionid"),
     *                  @SWG\Property( property="remark", type="string", example="绝版小饭团", description="备注"),
     *                  @SWG\Property( property="groupid", type="string", example="0", description=""),
     *                  @SWG\Property( property="tagids", type="string", example="", description=""),
     *                  @SWG\Property( property="tagpop", type="string", example="false", description="列表标签弹出框所需字段"),
     *                  @SWG\Property( property="remarkpop", type="string", example="false", description="列表页备注弹出框所需字段"),
     */
}
