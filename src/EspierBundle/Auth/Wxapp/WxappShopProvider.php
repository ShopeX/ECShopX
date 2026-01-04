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

namespace EspierBundle\Auth\Wxapp;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Contract\Auth\Provider;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

use CommunityBundle\Services\CommunityService;

class WxappShopProvider implements Provider
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
            throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.', null, 401001);
        }
        try {
            if (!$user = $this->getUser($sessionVal)) {
                throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.');
            }
        } catch (\Exception $exception) {
            throw new UnauthorizedHttpException('WxappAuth', $exception->getMessage(), $exception, 401001);
        }

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
        $request_session = $request->headers->get('x-wxapp-session');
        if (!$request_session) {
            throw new BadRequestHttpException();
        }
        $local_session = app('redis')->connection('wechat')->get('shopSession3rd:' . $request_session);
        if (!$local_session) {
            return false;
        }
        return json_decode($local_session, 1);
    }

    /**
     * Get the user from sessionval
     *
     * @param array $sessionVal
     *
     * @return array
     */
    protected function getUser($sessionVal)
    {
        $communityService = new CommunityService();
        $communityData = $communityService->getInfo(['open_id' => $sessionVal['open_id'], 'company_id' => $sessionVal['company_id']]);
        if (!$communityData) {
            throw new UnauthorizedHttpException('账号异常，请联系运营商');
        }

        if ($communityData['status'] == 'close') {
            throw new UnauthorizedHttpException('账号被冻结，请联系运营商');
        }

        if ($communityData['status'] == 'loading') {
            throw new UnauthorizedHttpException('账号审核中，请稍后');
        }

        if ($communityData['status'] == 'refuse') {
            throw new UnauthorizedHttpException('账号审核未通过，请联系运营商');
        }

        // 判断以前的session是否还有效，社区已经停用
        return $sessionVal;
    }
}
