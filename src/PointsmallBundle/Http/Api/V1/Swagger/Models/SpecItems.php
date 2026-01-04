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

namespace PointsmallBundle\Http\Api\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="SpecItems"))
 */
class SpecItems
{
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $item_id;

    /**
     * @SWG\Property(description="商品状态 onsale 前台可销售，offline_sale前端不展示，instock 不可销售, only_show:前台仅展示", example="onsale")
     * @var string
     */
    public $approve_status;

    /**
     * @SWG\Property(description="", example="库存")
     * @var string
     */
    public $store;

    /**
     * @SWG\Property(description="成本价（分）", example="1")
     * @var int
     */
    public $cost_price;

    /**
     * @SWG\Property(description="市场价（分）", example="1")
     * @var int
     */
    public $market_price;

    /**
     * @SWG\Property(description="积分价格", example="1")
     * @var int
     */
    public $point;

    /**
     * @SWG\Property(description="货品编码", example="S5FF5332CCEEC4")
     * @var string
     */
    public $item_bn;

    /**
     * @SWG\Property(description="条形码", example="BDCS123")
     * @var string
     */
    public $barcode;

    /**
     * @SWG\Property(description="体积", example="1")
     * @var string
     */
    public $volume;

    /**
     * @SWG\Property(description="重量(kg)", example="1")
     * @var string
     */
    public $weight;

    /**
     * @SWG\Property(description="商品是否为默认商品", example=true)
     * @var boolean
     */
    public $is_default;

    /**
     * @SWG\Property(
     *      type="array",
     *      @SWG\Items(
     *           @SWG\Property(property="item_id", type="integer", description="商品id", example=1),
     *           @SWG\Property(property="spec_id", type="integer", description="规格项id", example=1),
     *           @SWG\Property(property="spec_value_id", type="integer", description="规格值id", example=1),
     *           @SWG\Property(property="spec_name", type="string", description="规格项名称", example="尺码"),
     *           @SWG\Property(property="spec_custom_value_name", description="规格值自定义名称", type="string", example="S"),
     *           @SWG\Property(property="spec_value_name", type="integer", description="规格值名称", example="S"),
     *           @SWG\Property(property="item_image_url", type="string", description="商品图片地址数组", example="商品图片地址数组"),
     *           @SWG\Property(property="spec_image_url", type="string", description="规格图片地址数组", example="规格图片地址数组"),
     *      )
     * )
     */
    public $item_spec;
}
