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

use CompanysBundle\Services\OperatorLogsService;
use CompanysBundle\Services\OperatorLogs\MysqlService;

use Closure;
use Dingo\Api\Routing\Helpers;

/* 商家操作日志
 */
class ShopLogMiddleWare
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
            if (strtoupper($request->getMethod()) != 'GET') {
                $api = app('api.router');
                $companyId = app('auth')->user()->get('company_id');
                $operatorId = app('auth')->user()->get('operator_id');
                $merchantId = app('auth')->user()->get('merchant_id', 0);
                $action = $api->current()->getAction();
                $params['company_id'] = $companyId;
                $params['operator_id'] = $operatorId;
                $params['operator_name'] = $action['name'] ?? '';
                $params['request_uri'] = $api->current()->getPath();
                $realIp = explode(',', $request->server('HTTP_X_FORWARDED_FOR'))[0];
                $params['ip'] = $realIp ?: $request->getClientIp();
                $params['params'] = $request->input();
                $params['log_type'] = 'operator';
                $params['merchant_id'] = $merchantId;
                $operatorLogsService = new OperatorLogsService(new MysqlService());
                $operatorLogsService->addLogs($params);
            }
        } catch (\Exception $e) {
            // 什么都不用做
        }
        return $response;
    }
}
