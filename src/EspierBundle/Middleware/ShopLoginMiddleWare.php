<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace EspierBundle\Middleware;

use CompanysBundle\Services\OperatorLogsService;
use CompanysBundle\Services\OperatorLogs\MysqlService;
use CompanysBundle\Services\OperatorsService;
use Closure;
use Dingo\Api\Routing\Helpers;
use Illuminate\Http\JsonResponse;

/* 商家操作日志
 */
class ShopLoginMiddleWare
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
        $company_id = 0;
        $operator_id = 0;
        $merchant_id = 0;
        $operator_name = "登录失败";
        if ($response instanceof JsonResponse && $response->getData()->data->token) {
            $username = $request->input('username');
            $logintype = $request->input('logintype');
            if (in_array($logintype, ['localadmin', 'oauthadmin'])) {
                $logintype = 'admin';
            }
            $operatorService = new OperatorsService();
            $info = $operatorService->getOperatorByMobile($username, $logintype);
            if ($info) {
                $company_id = $info['company_id'];
                $operator_id = $info['operator_id'];
                $merchant_id = $info['merchant_id'];
                $operator_name = "登录成功";
            }
        }
        try {
            $api = app('api.router');
            $action = $api->current()->getAction();
            $params['company_id'] = $company_id;
            $params['operator_id'] = $operator_id;
            $params['merchant_id'] = $merchant_id ?? 0;
            $params['operator_name'] = $operator_name;
            $params['request_uri'] = $api->current()->getPath();
            $realIp = explode(',', $request->server('HTTP_X_FORWARDED_FOR'))[0];
            $params['ip'] = $realIp ?: $request->getClientIp();
            $params['params'] = $request->all('username', 'logintype');
            $params['log_type'] = 'login';
            $operatorLogsService = new OperatorLogsService(new MysqlService());
            $operatorLogsService->addLogs($params);
        } catch (\Exception $e) {
            info($e->getMessage());
        }
        return $response;
    }
}
