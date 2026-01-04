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

/* 接口签名
 */
class ServiceSignMiddleWare
{
    use Helpers;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $serviceSign = $request->input('ServiceSign', $request->header('ServiceSign'));
        if (!$serviceSign) {
            throw new \Exception('签名不能为空');
        }
        $signData = explode(' ', $serviceSign);
        if (count($signData) != 2) {
            throw new \Exception('签名格式不不正确');
        }
        list($serviceName, $sign) = $signData;
        $localSign = config('services.'.$serviceName.'.sign');
        if (!$localSign) {
            throw new \Exception('签名不存在');
        }
        if ($localSign != $sign) {
            throw new \Exception('签名不正确');
        }
        return $next($request);
    }
}
