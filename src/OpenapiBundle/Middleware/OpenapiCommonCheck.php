<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OpenapiBundle\Middleware;

use Closure;
use Exception;
use OpenapiBundle\Constants\ErrorCode;

class OpenapiCommonCheck
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $data = $request->toArray();

        app('log')->debug("openapi-requestData===>:" . var_export($data, 1) . "\n");
        try {
            $rules = [
                'version' => ['required', '版本号必填'],
                'timestamp' => ['required', 'timestamp必填'],
            ];
            $error = validator_params($data, $rules);
            if ($error) {
                throw new Exception($error, ErrorCode::VALIDATION_MISSING_PARAMS);
            }
            // 开启debug后，不校验签名
            if ((int)config('openapi.debug') === 1) {
                return $next($request);
            }

            //判断timestamp是否在合法时间范围内 允许最大时间误差10分钟
            $timestamp = strtotime($data['timestamp']);
            if (abs($timestamp - time()) > 600) {
                throw new Exception('timestamp 不合法', ErrorCode::VALIDATION_TIMESTAMP_ERROR);
            }

            if (!isset($data['sign']) || !$data['sign']) {
                throw new Exception('缺少 sign', ErrorCode::SIGN_ERROR);
            }

            $sign = trim($data['sign']);

            unset($data['sign']);

            $token = config('openapi.common_token');

            if (!$sign || $sign != OpenapiCheck::gen_sign($data, $token)) {
                throw new Exception('sign 不合法', ErrorCode::SIGN_ERROR);
            }

            return $next($request);
        } catch (\Exception $e) {
            return response()->json(['status' => 'fail', 'code' => 'E' . $e->getCode(), 'message' => $e->getMessage(), 'data' => $data]);
        }
    }
}
