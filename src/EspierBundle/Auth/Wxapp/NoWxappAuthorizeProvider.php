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
use WechatBundle\Services\OpenPlatform;

class NoWxappAuthorizeProvider implements Provider
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
        $appid = $request->headers->get('authorizer-appid');
        if (!$appid) {
            throw new UnauthorizedHttpException('WxappAuth', 'Unable to authorizer-appid.', null, 401001);
        }
        $openPlatform = new OpenPlatform();
        $companyId = $openPlatform->getCompanyId($appid);
        $woaAppid = $openPlatform->getWoaAppidByCompanyId($companyId);
        if (!$companyId) {
            throw new UnauthorizedHttpException('WxappAuth', 'Unable to company_id.', null, 401001);
        }

        return [
            'company_id' => $companyId,
            'woa_appid' => $woaAppid,
        ];
    }
}
