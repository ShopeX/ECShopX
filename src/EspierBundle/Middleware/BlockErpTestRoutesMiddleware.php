<?php

declare(strict_types=1);

namespace EspierBundle\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 禁用公开 ERP test 触发路由（SaaS ERP / SystemLink ome test/*）。
 * 生产环境始终拒绝；非生产默认亦拒绝，除非显式开启 config('common.erp_test_routes_enabled')。
 */
class BlockErpTestRoutesMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if ($this->isBlocked()) {
            return response()->json([
                'code' => 403,
                'msg' => 'ERP test routes are disabled',
            ], 403);
        }

        return $next($request);
    }

    private function isBlocked(): bool
    {
        if (env('APP_ENV') === 'production') {
            return true;
        }

        return ! (bool) config('common.erp_test_routes_enabled', false);
    }
}
