<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ChinaumsPayBundle\Services\ClientAPIs;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use ChinaumsPayBundle\Interfaces\ClientInterface;

abstract class ClientBase implements ClientInterface
{
    public $uri;
    public $config;
    public $method;
    public $methodType = [
        'post' => 'form_params',
        'get'  => 'query',
    ];
    public $headers = [];
    // 枚举值：
    //  json        对应 HEADERS 是 ['Content-Type' => 'application/json']
    //  multipart   对应 HEADERS 是 ['Content-Type' => 'multipart/form-data']

    public function setOptions(array $options = []): UmsClient
    {
        $this->method     = $options['method'] ?? 'post';
        $this->methodType = $options['method_type'] ?? $this->methodType;
        $this->headers    += $options['headers'] ?? [];
        return $this;
    }

    private function getOptions($params): array
    {
        return [
            $this->methodType[$this->method] => $params,
            'headers' => $this->headers
        ];
    }


    public function call(array $params = []): ?string
    {
        try {
            $options = $this->getOptions($params);
            app('log')->debug(__CLASS__ . __FUNCTION__ . __LINE__ . 'API request === ' . json_encode([
                    $this->method,
                    $this->uri,
                    $this->headers,
                    $options,
                ]));
            $resObj = (new HttpClient)->request($this->method, $this->uri, $options)->getBody()->getContents();
            app('log')->info(__CLASS__ . __FUNCTION__ . __LINE__ . $resObj);
    
            return $resObj;
        } catch (GuzzleException $e) {
            $msg = $e->getFile() . $e->getLine() . $e->getMessage();
            app('log')->debug(__CLASS__ . __FUNCTION__ . __LINE__ . $msg);

            return $msg;
        }
    }
}