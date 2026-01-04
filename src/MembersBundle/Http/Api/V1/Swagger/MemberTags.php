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
class MemberTags
{
    /**
     *                  @SWG\Property( property="tag_id", type="string", example="241", description="标签id"),
     *                  @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     *                  @SWG\Property( property="tag_name", type="string", example="尊尊会员", description="标签名称"),
     *                  @SWG\Property( property="description", type="string", example="", description="内容"),
     *                  @SWG\Property( property="tag_icon", type="string", example="null", description="标签icon"),
     *                  @SWG\Property( property="saleman_id", type="string", example="0", description="导购员id"),
     *                  @SWG\Property( property="tag_status", type="string", example="online", description="标签类型，online：线上发布, self: 私有自定义"),
     *                  @SWG\Property( property="category_id", type="string", example="2", description="标签分类id"),
     *                  @SWG\Property( property="self_tag_count", type="string", example="0", description="自定义标签下会员数量"),
     *                  @SWG\Property( property="tag_color", type="string", example="#ff1939", description="标签颜色"),
     *                  @SWG\Property( property="font_color", type="string", example="rgba(8, 5, 5, 1)", description="字体颜色"),
     *                  @SWG\Property( property="distributor_id", type="string", example="0", description="店铺ID"),
     *                  @SWG\Property( property="created", type="string", example="1612158857", description=""),
     *                  @SWG\Property( property="updated", type="string", example="1612158857", description="修改时间"),
     */
}
