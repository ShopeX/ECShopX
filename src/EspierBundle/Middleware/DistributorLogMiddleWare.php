<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Middleware;

use SalespersonBundle\Services\SalespersonOperatorLogService;

use Closure;
use Dingo\Api\Routing\Helpers;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;

/**
 * 商家操作日志
 */
class DistributorLogMiddleWare
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
        $response = $next($request);
        try {
            $salespersonType = $request->headers->get('salesperson-type', null);
            if ($salespersonType == 'shopping_guide' && strtoupper($request->getMethod()) != 'GET') {
                $requestSession = $request->headers->get('x-wxapp-session');
                if (!$requestSession) {
                    throw new BadRequestHttpException();
                }
                $sessionVal = $this->getSession($requestSession);
                if (!$sessionVal) {
                    throw new UnauthorizedHttpException('WxappAuth', 'Unable to authenticate wxapp user.', null, 401001);
                }
                $companyId = $sessionVal['company_id'];
                $operatorId = $sessionVal['salesperson_id'];
                $operatorName = $sessionVal['salesperson_name'];
                $distributorId = $sessionVal['distributor_id'] ?? 0;
                $params['company_id'] = $companyId;
                $params['distributor_id'] = $distributorId;
                $params['operator_id'] = $operatorId;
                $params['operator_name'] = $operatorName;
                $params['request_uri'] = $request->path();
                $realIp = explode(',', $request->server('HTTP_X_FORWARDED_FOR'))[0];
                $params['ip'] = $realIp ?: $request->getClientIp();
                $params['params'] = $request->input();
                $operatorLogsService = new SalespersonOperatorLogService();
                $operatorLogsService->addLogs($params);
            }
        } catch (\Exception $e) {
            // 什么都不用做
        }
        return $response;
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
    protected function getSession($requestSession)
    {
        $localSession = app('redis')->connection('wechat')->get('adminSession3rd:' . $requestSession);
        if (!$localSession) {
            return false;
        }
        $localSession = json_decode($localSession, true);
        return $localSession;
    }
}
