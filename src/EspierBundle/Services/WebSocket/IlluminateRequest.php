<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Services\WebSocket;

use Illuminate\Http\Request as Request;
use Swoole\Http\Request as SwooleRequest;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class IlluminateRequest
{
    /**
     * Convert SwooleRequest to IlluminateRequest
     * @param array $rawServer
     * @param array $rawEnv
     * @return IlluminateRequest
     */
    public function toIlluminateRequest($swooleRequest)
    {
        $symfonyRequest = $this->handleRequest($swooleRequest);

        // Initialize laravel request
        Request::enableHttpMethodParameterOverride();
        $request = Request::createFromBase($symfonyRequest);

        return $request;
    }

    /**
     * convert swoole request to symfony request
     *
     * @param swoole_http_request $request
     *
     * @return Request
     * */
    protected function handleRequest(SwooleRequest $swooleRequest)
    {
        clearstatcache();

        $get = isset($swooleRequest->get) ? $swooleRequest->get : [];
        $post = isset($swooleRequest->post) ? $swooleRequest->post : [];
        $attributes = [];
        $files = isset($swooleRequest->files) ? $swooleRequest->files : [];
        $cookie = isset($swooleRequest->cookie) ? $swooleRequest->cookie : [];
        $server = isset($swooleRequest->server) ? array_change_key_case($swooleRequest->server, CASE_UPPER) : [];

        if (isset($swooleRequest->header)) {
            foreach ($swooleRequest->header as $key => $value) {
                $newKey = 'HTTP_' . strtoupper(str_replace('-', '_', $key));
                $server[$newKey] = $value;
            }
        }

        $content = null;

        $symfonyRequest = new SymfonyRequest($get, $post, $attributes, $cookie, $files, $server, $content);

        return $symfonyRequest;
    }
}
