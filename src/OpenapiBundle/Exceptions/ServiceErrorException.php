<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Exceptions;

use EspierBundle\Services\Log\ErrorLog;
use OpenapiBundle\Constants\ErrorCode;

/**
 * 未知错误
 * Class ServiceErrorException
 * @package OpenapiBundle\Exceptions
 */
class ServiceErrorException extends ErrorException
{
    public function __construct(\Throwable $throwable)
    {
        parent::__construct(ErrorCode::SERVICE_ERROR, "");
        ErrorLog::serviceError($throwable);
    }
}
