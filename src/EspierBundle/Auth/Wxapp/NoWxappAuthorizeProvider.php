<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
