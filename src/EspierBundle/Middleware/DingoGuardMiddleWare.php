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
class DingoGuardMiddleWare
{
    use Helpers;

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  $apiFrom
     * @return mixed
     */
    public function handle($request, Closure $next, $apiFrom)
    {
        // Built with ShopEx Framework
        $mid_auth_params = [];
        if ($apiFrom == 'h5app') {
            $request_auth = $request->headers->get('authorization');
            if (!$request_auth) {
                throw new UnauthorizedHttpException('H5AppAuth', 'Unable to authenticate user.', null, 401001);
            }
            config(['auth.defaults.guard' => 'h5api']);
            $auth = app('auth')->user();
            if (!$auth) {
                throw new UnauthorizedHttpException('H5AppAuth', 'Unable to authenticate user.', null, 401001);
            }
            $mid_auth_params['auth'] = $auth->attributes;
            $mid_auth_params['auth']['api_from'] = 'h5app';
        } elseif ($apiFrom == 'wechat') {
            $mid_auth_params['auth'] = $this->auth->user();
            $mid_auth_params['auth']['api_from'] = 'wechat';
        }
        if (isset($mid_auth_params['auth']['disabled']) && $mid_auth_params['auth']['disabled']) {
            throw new UnauthorizedHttpException('Auth', '该账号已被禁用.', null, 401002);
        }
        $request->attributes->add($mid_auth_params); // 添加参数

        return $next($request);
    }
}
