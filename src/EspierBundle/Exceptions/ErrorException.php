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
