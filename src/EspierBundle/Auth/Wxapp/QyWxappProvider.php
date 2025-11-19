<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Auth\Wxapp;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Route;
use Dingo\Api\Contract\Auth\Provider;

use SalespersonBundle\Services\SalespersonService;
use DistributionBundle\Services\DistributorSalesmanRoleService;

use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class QyWxappProvider implements Provider
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
        // CONST: 1E236443
        $sessionVal = $this->getSession($request);
        if (!$sessionVal) {
            throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.', null, 401001);
        }

        $salespersonType = $request->headers->get('salesperson-type');
        $salespersonType = $salespersonType ?: ['admin', 'verification_clerk'];
        $user = json_decode($sessionVal, true);

        //验证用户手机号是否可以登录
        $salespersonService = new SalespersonService();
        // $mobileArr = $user['phoneNumber'];
        //手机号获取核销员信息
        // $salespersonInfo = $salespersonService->getSalespersonByMobileByType($mobileArr, $salespersonType, 'true');
        $filter = [
            'company_id' => $user['company_id'],
            'work_userid' => $user['work_userid'],
            'salesperson_type' => $salespersonType,
            'is_valid' => 'true',
        ];
        $salespersonInfo = $salespersonService->getSalespersonDetail($filter);
        app('log')->info('QyWxappProvider salespersonInfo====>'.var_export($salespersonInfo, 1));
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
        // CONST: 1E236443
        $requestSession = $request->headers->get('x-wxapp-session');
        if (!$requestSession) {
            throw new BadRequestHttpException();
        }
        $localSession = app('redis')->connection('wechat')->get('frontSession3rd:' . $requestSession);
        if (!$localSession) {
            return false;
        }

        return $localSession;
    }
}
