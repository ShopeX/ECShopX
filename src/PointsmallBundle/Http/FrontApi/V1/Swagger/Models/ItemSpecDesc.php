<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
