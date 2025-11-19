<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
