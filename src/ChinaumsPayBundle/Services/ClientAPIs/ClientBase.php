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