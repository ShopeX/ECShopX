<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Auth\Super;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Contract\Auth\Provider;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class AdminProvider implements Provider
{
    /**
     * Authenticate request with a Wxapp.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Dingo\Api\Routing\Route $route
     *
     * @return mixed
     */
    public function authenticate(Request $request, Route $route)
    {
        $sessionVal = $this->getSession($request);
        if (!$sessionVal) {
            throw new UnauthorizedHttpException('superAdminAuth', '登录已失效', null, 401001);
        }

        $user = json_decode($sessionVal, true);
        return $user;
    }

    /**
     * Get the sessionvalue from the request.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Exception
     *
     * @return array
     */
    protected function getSession(Request $request)
    {
        $requestSession = $request->headers->get('Authorization');
        if (!$requestSession) {
            throw new BadRequestHttpException();
        }

        $localSession = app('redis')->connection('espier')->get('superAdminSession3rd:' . $requestSession);
        if (!$localSession) {
            return false;
        }

        return $localSession;
    }
}
