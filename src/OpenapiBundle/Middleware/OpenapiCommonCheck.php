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
