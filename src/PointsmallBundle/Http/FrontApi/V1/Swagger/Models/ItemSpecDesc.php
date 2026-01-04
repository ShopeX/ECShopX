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

namespace PointsmallBundle\Http\FrontApi\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="ItemSpecDesc"))
 */
class ItemSpecDesc
{
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $spec_id;

    /**
     * @SWG\Property(description="规格名称", example="规格名称")
     * @var string
     */
    public $spec_name;

    /**
     * @SWG\Property(example="3")
     * @var string
     */
    public $is_image;

    /**
     * @SWG\Property(
     *      type="array",
     *      @SWG\Items(
     *           @SWG\Property(property="spec_value_id", type="integer", example=1),
     *           @SWG\Property(property="spec_custom_value_name", description="规格值自定义名称", type="string", example="S"),
     *           @SWG\Property(property="spec_value_name", type="integer", description="规格值名称", example="S"),
     *           @SWG\Property(property="item_image_url", type="string", description="商品图片地址数组", example="商品图片地址数组"),
     *           @SWG\Property(property="spec_image_url", type="string", description="规格图片地址数组", example="规格图片地址数组"),
     *      )
     * )
     */
    public $spec_values;
}
