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
