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
use OpenapiBundle\Entities\OpenapiDeveloper;
use OpenapiBundle\Constants\ErrorCode;

class OpenapiCheck
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $data = $request->toArray();

        app('log')->debug("openapi-requestData:::===>:".json_encode($data)."\n");
        try {
            $rules = [
                'version' => ['required', '版本号必填'],
                'timestamp' => ['required', 'timestamp必填'],
                'app_key' => ['required', 'app_key必填'],
            ];
            $error = validator_params($data, $rules);
            if ($error) {
                throw new Exception($error, ErrorCode::VALIDATION_MISSING_PARAMS);
            }
            // 开启debug后，不校验签名
            if ((int)config('openapi.debug') === 1) {
                $mid_auth_params['auth']['company_id'] = config('common.system_companys_id');
                $request->attributes->add($mid_auth_params); // 添加参数
                return $next($request);
            }

            //判断timestamp是否在合法时间范围内 允许最大时间误差10分钟
            if (abs(time() - strtotime($data['timestamp'])) > 60 * 10) {
                throw new Exception('timestamp 不合法', ErrorCode::VALIDATION_TIMESTAMP_ERROR);
            }

            if (!isset($data['sign']) || !$data['sign']) {
                throw new Exception('缺少 sign', ErrorCode::SIGN_ERROR);
            }
            $developer = app('registry')->getManager('default')->getRepository(OpenapiDeveloper::class)->getInfo(['app_key' => $data['app_key']]);
            if (empty($developer)) {
                throw new Exception('app_key 不正确', ErrorCode::VALIDATION_APPKEY_ERROR);
            }

            $sign = trim($data['sign']);

            unset($data['sign']);

            $token = $developer['app_secret']; //'aaaa';

            if (!$sign || $sign != self::gen_sign($data, $token)) {
                throw new Exception('sign 不合法', ErrorCode::SIGN_ERROR);
            }
            
            $mid_auth_params = [];
            $mid_auth_params['auth']['company_id'] = $developer['company_id'];

            $request->attributes->add($mid_auth_params); // 添加参数

            return $next($request);
        } catch (\Exception $e) {
            $error = ['status' => 'fail', 'code' => 'E'.$e->getCode(), 'message' => $e->getMessage(), 'data' => $data];
            app('log')->debug("openapi-error:::===>:".json_encode($error)."\n");
            return response()->json($error);
        }
    }

    public static function gen_sign($params, $token)
    {
        return strtoupper(md5($token.self::assemble($params).$token));
    }

    public static function assemble($params)
    {
        if (!is_array($params)) {
            return null;
        }

        ksort($params, SORT_STRING);

        $sign = '';

        foreach ($params as $key => $val) {
            if (is_null($val)) {
                continue;
            }
            if (is_bool($val)) {
                $val = ($val) ? 1 : 0;
            }
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }
}
