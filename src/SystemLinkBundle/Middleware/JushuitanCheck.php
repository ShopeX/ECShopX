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
use Exception;
use SystemLinkBundle\Services\JushuitanSettingService;

class JushuitanCheck
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
        // 验证中间件回打信息
        $data = $request->query();
        app('log')->debug('jushuitan::callback::JushuitanCheck::data:', $data);
        if (!isset($data['sign']) || !$data['sign'])
        {
            $data['sign'] = '';
        }

        $sign = trim($data['sign']);
        
        unset($data['sign']);
        $partnerkey = 'erp';

        app('log')->debug('jushuitan::callback::JushuitanCheck::sign:' . self::gen_sign($data,$partnerkey));
        if (!$sign || $sign != self::gen_sign($data,$partnerkey) )
        {
            app('log')->debug('jushuitan::callback::JushuitanCheck::sign error');
            return response()->json(['code' => 0, 'msg' => 'sign error']);
        }

        return $next($request);
    }

    static function gen_sign($params,$token){
        $method = trim($params['method']);
        $partnerid = trim($params['partnerid']);
        unset($params['method'], $params['partnerid']);
        return md5($method.$partnerid.self::assemble($params).$token);
    }

    static function assemble($params)
    {
        if(!is_array($params)) return null;
        ksort($params, SORT_STRING);
        $sign = '';
        foreach($params AS $key=>$val){
            $sign .= $key . (is_array($val) ? self::assemble($val) : $val);
        }
        return $sign;
    }//End Function
}
