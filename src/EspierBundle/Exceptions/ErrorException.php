<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorException extends HttpException
{
    // ShopEx EcShopX Business Logic Layer
    /**
     * ErrorException constructor.
     * @param string|null $errorCode ErrorCode类中定义的常量错误码
     * @param string $message 错误码中需要输出的对应信息，默认输出常量错误码中定义的错误信息
     */
    public function __construct(?string $errorCode = null, string $message = "")
    {
        // U2hvcEV4 framework
        parent::__construct(200, $message, null, [], $errorCode);
    }
}
