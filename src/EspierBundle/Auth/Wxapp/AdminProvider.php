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

use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorSalesmanRoleService;

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
            throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.', null, 401001);
        }

        $salespersonType = $request->headers->get('salesperson-type');
        $salespersonType = $salespersonType ?: ['admin', 'verification_clerk'];
        $user = json_decode($sessionVal, true);

        //验证用户手机号是否可以登录
        $salespersonService = new SalespersonService();
        $mobileArr = $user['phoneNumber'];
        //手机号获取核销员信息
        $salespersonInfo = $salespersonService->getSalespersonByMobileByType($mobileArr, $salespersonType, 'true');
        if (!$salespersonInfo) {
            throw new BadRequestHttpException('当前手机号无权限', null, 401001);
        }

        $distributorSalesmanRoleService = new DistributorSalesmanRoleService();
        if (!$distributorSalesmanRoleService->checkSalespersonRole($salespersonInfo['salesperson_id'], $request->route())) {
            throw new BadRequestHttpException('当前接口暂无权限', null, 401001);
        }

        //如果用户权限改变，则验证不通过
        if (isset($salespersonInfo['salesperson_type']) && $salespersonInfo['salesperson_type']) {
            $salespersonType = $salespersonInfo['salesperson_type'];
        } elseif (isset($salespersonInfo['salespersonType']) && $salespersonInfo['salespersonType']) {
            $salespersonType = $salespersonInfo['salespersonType'];
        }

        if ($user['salesperson_type'] != $salespersonType) {
            throw new BadRequestHttpException();
        }
        $user['distributor_id'] = $salespersonInfo['distributor_id'] ?? 0;
        $user['shop_ids'] = $salespersonInfo['shop_ids'];
        $user['distributor_ids'] = $salespersonInfo['distributor_ids'];
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
        $requestSession = $request->headers->get('x-wxapp-session');
        if (!$requestSession) {
            throw new BadRequestHttpException();
        }
        $localSession = app('redis')->connection('wechat')->get('adminSession3rd:' . $requestSession);
        if (!$localSession) {
            return false;
        }

        return $localSession;
    }
}
