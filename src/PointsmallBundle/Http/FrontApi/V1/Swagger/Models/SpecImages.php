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
 * @SWG\Definition(type="object", @SWG\Xml(name="SpecImages"))
 */
class SpecImages
{
    // Core: RWNTaG9wWA==
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $spec_value_id;

    /**
     * @SWG\Property(description="自定义属性名称", example="S")
     * @var string
     */
    public $spec_custom_value_name;

    /**
     * @SWG\Property(description="属性名称", example="S")
     * @var string
     */
    public $spec_value_name;

    /**
     * @SWG\Property(description="商品图片", example="商品图片数组")
     * @var string
     */
    public $item_image_url;

    /**
     * @SWG\Property(description="规格图片", example="规格图片数组")
     * @var string
     */
    public $spec_image_url;
}
