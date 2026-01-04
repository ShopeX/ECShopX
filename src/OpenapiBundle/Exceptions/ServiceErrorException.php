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
