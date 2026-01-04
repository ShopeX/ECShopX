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

class OpenApiCheck
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
        $params = $request->toArray();

        if (!isset($params['sign']) || !$params['sign']) {
            $params['sign'] = '';
        }

        $sign = trim($params['sign']);

        unset($params['sign']);

        $token = config('common.openapi_gy_token');

        app('log')->debug('OpenApiCheck_token:' . $token);
        app('log')->debug('OpenApiCheck_request_sign:' . $sign);
        app('log')->debug('OpenApiCheck_sign:' . self::gen_sign($params, $token));
        app('log')->debug('OpenApiCheck_request_params:' . var_export($params, 1));

        if (!$sign || $sign != self::gen_sign($params, $token)) {
            return response()->json(['rsp' => 'fail', 'code' => 0, 'err_msg' => 'sign error', 'data' => json_encode($params)]);
        }

        return $next($request);
    }

    /**
     * 生成签名
     * -------------------------------------------------------------
     * @param   array $params 签名参数
     * @param   striing $token 签名私钥
     * @return  string
     * @todo
     * -------------------------------------------------------------
     * 例如：将函数assemble得到的字符串md5加密，然后转为大写，尾部连接密钥$token组成新的字符串，再md5,结果再转为大写
     */
    public static function gen_sign($params, $token)
    {
        return strtoupper(md5(strtoupper(md5(self::assemble($params))).$token));
    }

    /**
     * 组合签名参数
     * -------------------------------------------------------------
     * @param   array $params 签名参数
     * @return  string
     * @todo
     * -------------------------------------------------------------
     * 根据参数名称将你的所有请求参数按照字母先后顺序排序:
     * key + value .... key + value 对除签名和图片外的所有请求参数按key做的升序排列, value无需编码。
     * 例如：
     * 将foo=1,bar=2,baz=3 排序为bar=2,baz=3,foo=1 参数名和参数值链接后，得到拼装字符串bar2baz3foo1
     * -------------------------------------------------------------
     */
    public static function assemble($params)
    {
        if (!is_array($params)) {
            return null;
        }

        ksort($params, SORT_STRING);
        $sign = '';
        foreach ($params as $key => $val) {
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }
}
