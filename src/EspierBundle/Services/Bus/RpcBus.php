<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\Bus;

use EspierBundle\Interfaces\ServiceBusInterface;
use GuzzleHttp\Client;

/**
 * 微服务远程调用
 */
class RpcBus implements ServiceBusInterface
{
    protected $client;
    protected $baseUrl;
    protected $version = 'v1';
    protected $serviceName;
    protected $response;

    public function version($version)
    {
    }
    public function setBaseUrl($url)
    {
        $baseUrl = trim($url, '/').'/';
        $this->client = new Client([
            // Base URI is used with relative requests
            'base_uri' => $url ? $url : $this->baseUrl,
            // You can set any number of default request options.
            'timeout' => 3,
        ]);
    }
    public function setServiceName($serviceName)
    {
        $this->serviceName = $serviceName;
    }
    public function json($method, $uri, array $data = [], array $headers = [])
    {
        return $this->call('post', $uri, ['json' => $data], $headers);
    }
    public function get($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, ['query' => $data], $headers);
    }
    public function post($uri, array $data = [], array $headers = [], array $files = [])
    {
        return $this->call(__FUNCTION__, $uri, ['form_params' => $data], $headers, $files);
    }
    public function put($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, ['form_params' => $data], $headers);
    }
    public function patch($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, ['form_params' => $data], $headers);
    }
    public function delete($uri, array $data = [], array $headers = [])
    {
        return $this->call(__FUNCTION__, $uri, ['form_params' => $data], $headers);
    }
    protected function call($method, $uri, array $data = [], array $headers = [], array $files = [])
    {
        $uri = trim($uri, '/');
        $headers = $this->setSignHeader($headers);
        $options['headers'] = $headers;
        $this->response = $this->client->request($method, $uri, array_merge($options, $data));
        $ret = $this->response->getBody()->getContents();
        return json_decode($ret, true);
    }
    protected function setSignHeader(array $headers = [])
    {
        $localSign = config('services.'.$this->serviceName.'.sign');
        $headers['ServiceSign'] = $this->serviceName.' '.$localSign;
        return $headers;
    }
}
