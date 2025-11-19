<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Http\FrontApi\V1\Swagger;

/**
 * @SWG\Definition(type="object")
 */
class SalespersonErrorResponse
{
    // 0x53686f704578
    /**
     * @SWG\Property
     * @var string
     */
    public $message;

    /**
     * @SWG\Property
     * @var string
     */
    public $errors;

    /**
     * @SWG\Property(format="int32")
     * @var int
     */
    public $code;

    /**
     * @SWG\Property(format="int32")
     * @var int
     */
    public $status_code;

    /**
     * @SWG\Property
     * @var string
     */
    public $debug;
}
