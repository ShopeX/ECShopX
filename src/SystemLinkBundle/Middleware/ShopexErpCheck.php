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

namespace SystemLinkBundle\Middleware;

use Closure;

class ShopexErpCheck
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
        // 验证商派erp回打信息
        $data = $request->toArray();

        if (!isset($data['sign']) || !$data['sign']) {
            $data['sign'] = '';
        }

        $sign = trim($data['sign']);

        unset($data['sign']);

        //$token = config('common.erp_gy_token');
        $token = config('common.oms_token');//20201130 oms那边用自己的token加密

        app('log')->debug('ShopexErpCheck_token:' . $token);
        app('log')->debug('ShopexErpCheck_ome_sign:' . $sign);
        app('log')->debug('ShopexErpCheck_sign:' . self::gen_sign($data, $token));
        //app('log')->debug('ShopexErpCheck_request:' . var_export($request, 1));

        if (!$sign || $sign != self::gen_sign($data, $token)) {
            return response()->json(['rsp' => 'fail', 'code' => 0, 'err_msg' => 'sign error', 'data' => json_encode($data, 256)]);
        }

        return $next($request);
    }

    public static function gen_sign($params, $token)
    {
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$token));
        ;
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
