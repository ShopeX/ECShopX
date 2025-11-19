<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
