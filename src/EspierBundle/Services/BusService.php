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

use Illuminate\Support\Arr;

class BusService
{
    /**
     * 微服务配置列表
     * @var array
     */
    private $serviceList = [];
    /**
     * 微服务名称
     * @var string
     */
    private $serviceName = "";
    /**
     * 初始化bus
     * @param string $serviceName 微服务名称
     * @param array  $serviceList 微服务配置列表
     */
    public function __construct($serviceName, $serviceList = array())
    {
        $this->serviceName = $serviceName;
        $this->serviceList = $serviceList;
    }
    public static function instance($serviceName = '')
    {
        return (new self($serviceName, config('services')))->create();
    }
    /**
     * 设置微服务名称
     * @param array $config 配置文件
     */
    public function setServiceList(array $config)
    {
        $this->serviceList = $config;
    }

    /**
     * 创建微服务链接
     *
     * @return \Shopex\Contracts\Service\Bus
     * @author
     */
    public function create()
    {
        $config = $this->getServiceConfig();
        $service = $this->getServiceClass($config['rpc_type']);
        if (!class_exists($service)) {
            throw new \Exception('微服务bus['.$service.']不存在');
        }
        $instance = new $service();
        $instance->setServiceName($this->serviceName);
        $instance->setBaseUrl($config['base_url']);
        return $instance;
    }

    private function getServiceConfig()
    {
        $defalut = [
            "rpc_type" => "local",
            "base_url" => "http://127.0.0.1"
        ];
        return Arr::get($this->serviceList, $this->serviceName, $defalut);
    }

    private function getServiceClass($type)
    {
        return "\EspierBundle\Services\Bus\\".ucwords($type)."Bus";
    }
}
