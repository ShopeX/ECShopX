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

namespace ThirdPartyBundle\Middleware;

use Closure;

class DadaApiCheck
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
        // 验证达达同城配回打信息
        $data = $request->all();
        app('log')->debug('dadaCallback request handle=>:'.var_export($data, 1)."\n");
        // 验证签名
        $signature = $data['signature'] ?? '';
        // 组织需要签名的字段数据
        $sign_data = [
            'client_id' => $data['client_id'] ?? '',
            'order_id' => $data['order_id'] ?? '',
            'update_time' => $data['update_time'] ?? '',
        ];

        if (!$signature) {
            // 回调地址验证
            return response()->json(['status' => 'ok']);
        }

        if (!$signature || $signature != $this->_sign($sign_data)) {
            app('log')->debug('dadaCallback request sign error');
            return response()->json(['status' => 'fail']);
        }
        return $next($request);
    }

    /**
     * 签名生成signature
     */
    public function _sign($data)
    {

        // 第一步：将参与签名的字段的值进行升序排列
        asort($data, SORT_STRING);
        // 第二步：将排序过后的参数，进行字符串拼接
        $args = '';
        foreach ($data as $value) {
            $args .= $value;
        }
        // 第三步：对第二步连接的字符串进行md5加密
        $sign = md5($args);
        return $sign;
    }
}
