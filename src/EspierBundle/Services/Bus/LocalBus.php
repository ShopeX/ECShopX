<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Bus;

use EspierBundle\Interfaces\ServiceBusInterface;
use Dingo\Api\Exception\ResourceException;
use Dingo\Api\Exception\InternalHttpException;

/**
 * 微服务本地调用
 */
class LocalBus implements ServiceBusInterface
{
    protected $dispatcher;
    protected $version = 'v1';
    protected $serviceName;

    public function __construct()
    {
        $this->dispatcher = app('Dingo\Api\Dispatcher');
        $this->dispatcher->version($this->version);
    }
    public function version($version)
    {
        $this->dispatcher->version($version);
        return $this;
    }
    public function setBaseUrl($url)
    {
    }
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
    }
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        $this->setSignHeader($headers);
        return $this->dispatcher->json($data)->post($uri);
    }
    public function get($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, $data, $headers);
    }
    public function post($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, $data, $headers);
    }
    public function put($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, $data, $headers);
    }
    public function patch($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, $data, $headers);
    }
    public function delete($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, $data, $headers);
    }
    protected function call($method, $uri, array $data = [], array $headers = [])
    {
        $this->setSignHeader($headers);
        try {
            return $this->dispatcher->with($data)->$method($uri);
        } catch (InternalHttpException $e) {
            $message = json_decode($e->getResponse()->getContent(), true);
            if (isset($message['data']['message']) && $message['data']['message']) {
                $message = $message['data']['message'];
            } else {
                $message = $e->getMessage();
            }
            throw new ResourceException($message);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    protected function setSignHeader(array $headers = [])
    {
        $localSign = config('services.'.$this->serviceName.'.sign');
        $headers['ServiceSign'] = $this->serviceName.' '.$localSign;
        foreach ($headers as $key => $value) {
            $this->dispatcher->header($key, $value);
        }
    }
}
