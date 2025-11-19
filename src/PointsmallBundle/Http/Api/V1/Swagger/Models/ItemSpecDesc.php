<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointsmallBundle\Http\Api\V1\Swagger\Models;

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
     * @SWG\Property(description="规格项名称", example="尺码")
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
     *           ref="#/definitions/SpecValues",
     *      )
     * )
     */
    public $spec_values;
}

/**
 * @SWG\Definition(
 *     definition="SpecValues",
 *     type="object",
 *     @SWG\Property(property="spec_value_id", type="integer", example=1),
 *           @SWG\Property(property="spec_custom_value_name", description="规格值自定义名称", type="string", example="S"),
 *           @SWG\Property(property="spec_value_name", type="integer", description="规格值名称", example="S"),
 *           @SWG\Property(property="item_image_url", type="string", description="商品图片地址数组", example="商品图片地址数组"),
 *           @SWG\Property(property="spec_image_url", type="string", description="规格图片地址数组", example="规格图片地址数组"),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ItemParams",
 *     type="object",
 *     description="商品属性",
 *     @SWG\Property( property="attribute_id", type="string", example="9", description="id"),
 *     @SWG\Property( property="attribute_type", type="string", example="item_params", description="商品属性类型 unit单位，brand品牌，item_params商品参数, item_spec规格"),
 *     @SWG\Property( property="attribute_name", type="string", example="参数test", description="名称"),
 *     @SWG\Property( property="attribute_memo", type="string", example="null", description="备注"),
 *     @SWG\Property( property="attribute_sort", type="string", example="1", description="商品属性排序，越大越在前"),
 *     @SWG\Property( property="is_show", type="string", example="true", description="是否用于筛选"),
 *     @SWG\Property( property="is_image", type="string", example="false", description="是否需要配置图片"),
 *     @SWG\Property( property="image_url", type="string", example="", description="图片url"),
 *     @SWG\Property( property="attribute_values", type="object",
 *         @SWG\Property( property="total_count", type="string", example="2", description="属性总数"),
 *         @SWG\Property( property="list", type="array",
 *             @SWG\Items( type="object",
 *                 @SWG\Property( property="attribute_value_id", type="string", example="13", description="属性值id"),
 *                 @SWG\Property( property="attribute_id", type="string", example="9", description="属性id"),
 *                 @SWG\Property( property="attribute_value", type="string", example="2", description="属性值"),
 *                 @SWG\Property( property="sort", type="string", example="1", description="排序"),
 *                 @SWG\Property( property="image_url", type="string", example="null", description="图片url"),
 *             ),
 *         ),
 *     ),
 * )
 */

/**
 * @SWG\Definition(
 *     definition="ItemSpec",
 *     type="object",
 *     description="商品规格",
 *     @SWG\Property( property="attribute_id", type="string", example="9", description="id"),
 *     @SWG\Property( property="attribute_type", type="string", example="item_spec", description="商品属性类型 unit单位，brand品牌，item_params商品参数, item_spec规格"),
 *     @SWG\Property( property="attribute_name", type="string", example="尺码", description="名称"),
 *     @SWG\Property( property="attribute_memo", type="string", example="鞋", description="备注"),
 *     @SWG\Property( property="attribute_sort", type="string", example="1", description="商品属性排序，越大越在前"),
 *     @SWG\Property( property="is_show", type="string", example="true", description="是否用于筛选"),
 *     @SWG\Property( property="is_image", type="string", example="false", description="是否需要配置图片"),
 *     @SWG\Property( property="image_url", type="string", example="", description="规格项图片url"),
 *     @SWG\Property( property="attribute_values", type="object", description="规格值数据",
 *         @SWG\Property( property="total_count", type="string", example="2", description="规格值总数"),
 *         @SWG\Property( property="list", type="array",
 *             @SWG\Items( type="object",
 *                 @SWG\Property( property="attribute_value_id", type="string", example="7", description="规格值id"),
 *                 @SWG\Property( property="attribute_id", type="string", example="9", description="规格项id"),
 *                 @SWG\Property( property="attribute_value", type="string", example="S", description="规格名"),
 *                 @SWG\Property( property="custom_attribute_value", type="string", example="S", description="规格别名"),
 *                 @SWG\Property( property="sort", type="string", example="1", description="排序"),
 *                 @SWG\Property( property="image_url", type="string", example="null", description="规格值图片url"),
 *             ),
 *         ),
 *     ),
 * )
 */
