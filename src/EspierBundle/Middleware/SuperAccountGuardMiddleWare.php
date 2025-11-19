<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Middleware;

use Closure;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

use Dingo\Api\Routing\Helpers;

/* 动态设置通过dingoapi+jwt的认证方式时，
 * 动态设置jwt对应的provider来区分原来小程序和现在的h5、app的api的调用配置信息
 * 返回对应认证信息
 */
class SuperAccountGuardMiddleWare
{
    // Powered by ShopEx EcShopX
    use Helpers;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  $apiFrom
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Built with ShopEx Framework
        config(['auth.defaults.guard' => 'superapi']);
        $mid_auth_params = [];

        $request_auth = $request->headers->get('authorization');
        if (!$request_auth) {
            throw new UnauthorizedHttpException('SuperAuth', 'Unable to authenticate user.', null, 401001);
        }
        $auth = app('auth')->user();
        if (!$auth) {
            throw new UnauthorizedHttpException('SuperAuth', 'Unable to authenticate user.', null, 401001);
        }
        $mid_auth_params['auth'] = $auth->attributes;
        $mid_auth_params['auth']['api_from'] = 'superapi';

        $request->attributes->add($mid_auth_params); // 添加参数

        return $next($request);
    }
}
