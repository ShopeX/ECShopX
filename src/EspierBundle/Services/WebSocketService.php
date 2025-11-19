<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services;

use EspierBundle\Interfaces\WebSocketInterface;

class WebSocketService
{
    /** @var webSocketInterface */
    public $webSocketInterface;

    /**
     * WebSocketService 构造函数.
     */
    public function __construct(WebSocketInterface $webSocketInterface)
    {
        $this->webSocketInterface = $webSocketInterface;
    }

    /**
     * Dynamically call the WebSocketService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->webSocketInterface->$method(...$parameters);
    }
}
