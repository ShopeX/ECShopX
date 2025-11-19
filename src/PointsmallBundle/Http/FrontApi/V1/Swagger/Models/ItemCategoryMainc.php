<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PointsmallBundle\Http\FrontApi\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="ItemCategoryMainc"))
 */
class ItemCategoryMainc
{
    /**
     * @SWG\Property(format="int64", example="2")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(description="主类目id", example="2")
     * @var int
     */
    public $category_id;

    /**
     * @SWG\Property(description="主类目名称", example="类目名称")
     * @var string
     */
    public $category_name;

    /**
     * @SWG\Property(description="标签", example="类目名称")
     * @var int
     */
    public $label;

    /**
     * @SWG\Property(description="上级id", example="1")
     * @var int
     */
    public $parent_id;

    /**
     * @SWG\Property(description="路径", example="2")
     * @var string
     */
    public $path;

    /**
     * @SWG\Property(description="排序", example="0")
     * @var string
     */
    public $sort;

    /**
     * @SWG\Property(description="是否为主类目", example=true)
     * @var boolean
     */
    public $is_main_category;

    /**
     * @SWG\Property(description="主类目下的属性数组", example="主类目下的属性数组")
     * @var string
     */
    public $goods_params;

    /**
     * @SWG\Property(description="主类目下的规格数组", example="主类目下的规格数组")
     * @var string
     */
    public $goods_spec;

    /**
     * @SWG\Property(description="类目层级,从1开始", example=2)
     * @var int
     */
    public $category_level;

    /**
     * @SWG\Property(description="图片地址", example="图片地址")
     * @var string
     */
    public $image_url;

    /**
     * @SWG\Property(description="层级,从0开始", example="1")
     * @var int
     */
    public $level;
}
