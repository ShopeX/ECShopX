<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
