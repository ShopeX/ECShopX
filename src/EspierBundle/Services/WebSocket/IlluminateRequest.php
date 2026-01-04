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
