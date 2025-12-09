<?php

namespace ThirdPartyBundle\Services\DmCrm;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Exception\RequestException;

class Request
{
    /**
     * 发送POST请求
     *
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param int $maxRetries
     * @param int $timeout
     * @return array
     * @throws \RuntimeException
     */
    public function requestApiPost($url, $data = [], $headers = [], $maxRetries = 3, $timeout = 15)
    {
        // 1. 创建重试中间件
        $stack = HandlerStack::create();
        $stack->push(Middleware::retry( function($retries, $request, $response = null, $exception = null) use ($maxRetries) {
                // 超过最大重试次数
                if ($retries >= $maxRetries) {
                    return false;
                }
                // 服务器错误 (5xx)
                if ($response && $response->getStatusCode() >= 500) {
                    return true;
                }
                // 连接超时/网络错误
                if ($exception instanceof \GuzzleHttp\Exception\ConnectException) {
                    return true;
                }
                // 429 请求过多
                if ($response && $response->getStatusCode() === 429) {
                    return true;
                }
                return false;
            }, function($retries) {
                return 1000 * pow(2, $retries);
            }
        ));

        // 2. 创建HTTP客户端
        $client = new Client([
            'handler' => $stack,
            'timeout' => $timeout,
            'connect_timeout' => 5,
            'http_errors' => false, // 自行处理错误
            'headers' => array_merge([
                'User-Agent' => 'RetryClient/1.0',
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ], $headers)
        ]);

        // 3. 添加请求ID用于追踪
        $requestId = bin2hex(random_bytes(4));
        $options = [
            'json' => $data,
            'headers' => ['X-Request-ID' => $requestId]
        ];

        try {
            // 4. 发送请求
            $response = $client->post($url, $options);

            $status = $response->getStatusCode();
            $body = $response->getBody()->getContents();

            // 5. 处理非成功响应
            if ($status < 200 || $status >= 300) {
                throw new \RuntimeException("API请求失败: HTTP $status - $url", $status);
            }

            return [
                'status' => $status,
                'body' => $body,
                'request_id' => $requestId
            ];

        } catch (RequestException $e) {
            // 6. 处理异常
            $errorMessage = $e->getMessage();

            if ($e->hasResponse()) {
                $response = $e->getResponse();
                $status = $response->getStatusCode();
                $body = $response->getBody()->getContents();

                $errorMessage = "HTTP $status 错误: " . substr($body, 0, 200);
            }

            throw new \RuntimeException("请求失败($requestId): $errorMessage", $e->getCode(), $e);
        }

    }

}
