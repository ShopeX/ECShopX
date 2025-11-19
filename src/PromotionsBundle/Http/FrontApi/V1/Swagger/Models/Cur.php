<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Http\FrontApi\V1\Swagger\Models;

/**
 * @SWG\Definition(type="object", @SWG\Xml(name="Cur"))
 */
class Cur
{
    /**
     * @SWG\Property(format="int64", example="1")
     * @var int
     */
    public $id;

    /**
     * @SWG\Property(description="币种", example="CNY")
     * @var string
     */
    public $currency;

    /**
     * @SWG\Property(description="币种名称", example="中国人民币"),
     * @var string
     */
    public $title;

    /**
     * @SWG\Property(description="币种符号", example="￥")
     * @var int
     */
    public $symbol;

    /**
     * @SWG\Property(description="币种税率", example="1")
     * @var string
     */
    public $rate;

    /**
     * @SWG\Property(description="是否默认", example=true)
     * @var boolean
     */
    public $is_default;

    /**
     * @SWG\Property(description="适用端。可选值为 service,normal", example="normal")
     * @var string
     */
    public $use_platform;
}
