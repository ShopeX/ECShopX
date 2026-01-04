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

namespace ThirdPartyBundle\Services;

use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

abstract class Request
{
    protected $config = [];

    /**
     * 设置基础的请求路径
     * @param string $baseUri
     * @return $this
     */
    protected function setBaseUri(string $baseUri): self
    {
        $this->config["base_uri"] = $baseUri;
        return $this;
    }

    /**
     * 设置超时时间
     * @param int $timeout
     * @return $this
     */
    protected function setTimeout(int $timeout = 3)
    {
        $this->config["timeout"] = $timeout;
        return $this;
    }

    /**
     * 设置实例化客户端时的配置信息
     * @param string $key
     * @param string $value
     * @return $this
     */
    protected function setConfig(string $key, string $value)
    {
        $this->config[$key] = $value;
        return $this;
    }

    /**
     * 获取实例化客户端时的配置信息
     * @return array
     */
    protected function getConfig(): array
    {
        // ID: 53686f704578
        return $this->config;
    }

    /**
     * 获取客户端对象，这里没有用到单例，是因为同一个线程下多个不同的请求容易会造成数据混乱
     * @return Client
     */
    protected function getClient(): Client
    {
        return new Client($this->getConfig());
//        if (! ($this->httpClient instanceof ClientInterface)) {
//            if (property_exists($this, 'app') && $this->app['http_client']) {
//                $this->httpClient = $this->app['http_client'];
//            } else {
//                $this->httpClient = new Client();
//            }
//        }
//        return $this->httpClient;
    }

    /**
     * 请求时需要额外传递的参数
     * @var array
     */
    protected $options = [];

    /**
     * 设置options参数
     * @param string $key
     * @param array $value
     * @return $this
     */
    protected function setOptions(string $key, array $value)
    {
        $this->options[$key] = $value;
        return $this;
    }

    /**
     * 获取请求时需要额外传递的参数
     * @return array
     */
    protected function getOptions(): array
    {
        return $this->options;
    }

    /**
     * 设置query参数
     * @param array $query
     * @return $this
     */
    protected function setQuery(array $query): self
    {
        $this->options[RequestOptions::QUERY] = $query;
        return $this;
    }

    /**
     * 设置body参数
     * @param array $body
     * @return $this
     */
    protected function setBody(array $body): self
    {
        $this->options[RequestOptions::BODY] = $body;
        return $this;
    }

    /**
     * 设置form_params参数
     * @param array $formParams
     * @return $this
     */
    protected function setFormParams(array $formParams): self
    {
        $this->options[RequestOptions::FORM_PARAMS] = $formParams;
        return $this;
    }

    /**
     * 设置header参数
     * @param array $header
     * @return $this
     */
    protected function setHeader(array $header): self
    {
        $this->options[RequestOptions::HEADERS] = $header;
        return $this;
    }

    /**
     * Get请求
     * @param string $uri
     * @return array 结果集
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function requestGet(string $uri)
    {
        return $this->request($uri, "GET");
    }

    /**
     * POST 请求
     *
     * @param string $url
     * @return array 结果集
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function requestPost(string $url)
    {
        return $this->request($url, 'POST');
    }

    /**
     * Make a request.
     *
     * @param string $url
     * @param string $method
     * @return array|null 结果集
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request($url, $method = 'GET'): ?array
    {
        try {
            $response = $this->getClient()->request(strtoupper($method), $url, $this->getOptions());
            $response->getBody()->rewind();
            if ($response->getStatusCode() != 200) {
                throw new \Exception(sprintf("请求有误！返回的响应不正确！%s", $response->getBody()->getContents()));
            }
            return (array)jsonDecode($response->getBody()->getContents());
        } catch (\Exception $exception) {
            $this->errorLog([
                "message" => $exception->getMessage(),
                "file" => $exception->getFile(),
                "line" => $exception->getLine()
            ]);
            app('api.exception')->report($exception);
            return null;
        }
    }

    /**
     * 把请求有误的信息记录到日志中
     * @param array|null $data
     */
    protected function errorLog(?array $data)
    {
        app("log")->error(sprintf("%s_error:%s", static::class, json_encode($data, JSON_UNESCAPED_UNICODE)));
    }
}
