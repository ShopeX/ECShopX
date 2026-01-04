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

namespace EspierBundle\Middleware;

use Closure;

use Dingo\Api\Routing\Helpers;
use Carbon\Carbon;

/**
 * api缓存中间件
 */
class ApiCacheMiddleWare
{
    use Helpers;

    /**
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * @var \Closure
     */
    protected $next;

    /**
     * 缓存分钟
     *
     * @var int|null
     */
    protected $minutes;

    /**
     * 缓存数据
     *
     * @var array
     */
    protected $responseCache;

    /**
     * 缓存命中状态，1为命中，0为未命中
     *
     * @var int
     */
    protected $cacheHit = 1;

    /**
     * 缓存Key
     *
     * @var string
     */
    protected $cacheKey;

    /**
     * Handle an incoming request
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure                 $next
     * @param int|null                 $minutes
     *
     * @return mixed
     */
    public function handle($request, Closure $next, $minutes = null)
    {
        // 未开启缓存或者bug是GET请求，原样返回，不缓存
        if (!config('cache.enabled') || strtoupper($request->getMethod()) != 'GET') {
            return $next($request);
        }
        $this->prepare($request, $next, $minutes);

        $this->responseCache();

        $response = $next($this->responseCache['content']);

        return $this->addHeaders($response);
    }

    /**
     * 预备
     *
     * @return mixed
     */
    protected function prepare($request, Closure $next, $minutes = null)
    {
        $this->request = $request;
        $this->next = $next;

        // 初始化值
        $this->cacheKey = $this->resolveKey();
        $this->minutes = $this->resolveMinutes($minutes);
    }

    /**
     * 生成或读取Response-Cache,实际缓存转换成秒
     *
     * @return array
     */
    protected function responseCache()
    {
        $this->responseCache = app('cache')->remember(
            $this->cacheKey,
            $this->minutes * 60,
            function () {
                $this->cacheMissed();

                $response = ($this->next)($this->request);

                return $this->resolveResponseCache($response) + [
                    'cacheExpireAt' => Carbon::now()->addMinutes($this->minutes)->format('Y-m-d H:i:s T'),
                ];
            }
        );

        return $this->responseCache;
    }

    /**
     * 确定需要缓存Response的数据
     *
     * @param \Illuminate\Http\Response $response
     *
     * @return array
     */
    protected function resolveResponseCache($response)
    {
        return [
            'content' => $response->getContent(),
        ];
    }

    /**
     * 追加Headers
     *
     * @param mixed
     */
    protected function addHeaders($response)
    {
        $response->headers->add(
            $this->getHeaders()
        );

        return $response;
    }

    /**
     * 返回Headers
     *
     * @return array
     */
    protected function getHeaders()
    {
        $headers = [
            'X-Cache' => $this->cacheHit ? 'Hit' : 'Missed',
            // 'X-Cache-Key' => $this->cacheKey,
            // 'X-Cache-Expires' => $this->responseCache['cacheExpireAt'],
        ];

        return $headers;
    }

    /**
     * 根据请求获取指定的Key
     *
     * @return string
     */
    protected function resolveKey()
    {
        return md5($this->request->fullUrl());
    }

    /**
     * 获取缓存的分钟
     *
     * @param int|null $minutes
     *
     * @return int
     */
    protected function resolveMinutes($minutes = null)
    {
        return is_null($minutes)
            ? $this->getDefaultMinutes()
            : max($this->getDefaultMinutes(), intval($minutes));
    }

    /**
     * 返回默认的缓存时间（分钟）
     *
     * @return int
     */
    protected function getDefaultMinutes()
    {
        return 5;
    }

    /**
     * 缓存未命中
     *
     * @return mixed
     */
    protected function cacheMissed()
    {
        $this->cacheHit = 0;
    }
}
