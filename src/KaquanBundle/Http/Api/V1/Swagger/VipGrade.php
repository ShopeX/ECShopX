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
class VipGrade
{
    // Built with ShopEx Framework
    /**
     * @SWG\Property( property="vip_grade_id", type="string", example="1", description="付费会员卡等级ID"),
     * @SWG\Property( property="company_id", type="string", example="1", description="公司id"),
     * @SWG\Property( property="grade_name", type="string", example="一般付费", description="等级名称"),
     * @SWG\Property( property="lv_type", type="string", example="vip", description="等级类型"),
     * @SWG\Property( property="default_grade", type="string", example="false", description="是否默认等级"),
     * @SWG\Property( property="is_disabled", type="string", example="false", description="是否禁用"),
     * @SWG\Property( property="background_pic_url", type="string", example="http://mmbiz.qpic.cn/mmbiz_jpg/MUQsdY0GdK5nQNFBaEhiao8MfBoP4B70L2rfqJDROzKgwUBvANmHMq9bQV2G1IWibKxK8iaukqbHiaicNkGKZPbX8EA/0?wx_fmt=jpeg", description="背景图"),
     * @SWG\Property( property="description", type="string", example="1、VIP2、整场促销3、畅想优惠", description="内容"),
     * @SWG\Property( property="price_list", type="array",
     *     @SWG\Items( type="object",
     *         @SWG\Property( property="name", type="string", example="monthly", description="名称"),
     *         @SWG\Property( property="price", type="string", example="0.01", description="价格"),
     *         @SWG\Property( property="day", type="string", example="30", description="生日日期"),
     *         @SWG\Property( property="desc", type="string", example="30天", description=""),
     *      ),
     * ),
     * @SWG\Property( property="privileges", type="object",
     *         @SWG\Property( property="discount", type="string", example="20", description="折扣值"),
     *         @SWG\Property( property="discount_desc", type="string", example="8", description=""),
     * ),
     * @SWG\Property( property="created", type="string", example="1560947408", description=""),
     * @SWG\Property( property="updated", type="string", example="1560947408", description="修改时间"),
     * @SWG\Property( property="guide_title", type="string", example="开通vip的引导文本，更多优惠等你来享受哦！", description="购买引导文本"),
     * @SWG\Property( property="is_default", type="string", example="true", description="是否是默认"),
     */
}
