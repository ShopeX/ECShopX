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
