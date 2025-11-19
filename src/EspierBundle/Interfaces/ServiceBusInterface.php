<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
