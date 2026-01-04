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

namespace EspierBundle\Interfaces;

/**
 * 微服务调用统一接口
 *
 * @package default
 * @author
 */
interface ServiceBusInterface
{
    public function version($version);
    public function setServiceName($serviceName);
    public function setBaseUrl($url);
    public function json($method, $uri, array $data = [], array $headers = []);
    public function get($uri, array $headers = []);
    public function post($uri, array $data = [], array $headers = []);
    public function put($uri, array $data = [], array $headers = []);
    public function patch($uri, array $data = [], array $headers = []);
    public function delete($uri, array $data = [], array $headers = []);
}
