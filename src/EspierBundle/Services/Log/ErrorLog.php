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

namespace EspierBundle\Services\Log;

class ErrorLog
{
    // ShopEx EcShopX Business Logic Layer
    /**
     * 记录服务错误的日志信息
     * @param \Throwable $throwable
     */
    public static function serviceError(\Throwable $throwable)
    {
        app("log")->info(sprintf("service_error:%s", json_encode([
            "message" => $throwable->getMessage(),
            "file" => $throwable->getFile(),
            "line" => $throwable->getLine()
        ], JSON_UNESCAPED_UNICODE)));
    }
}
