<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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

        if (!isset($data['sign']) || !$data['sign'])
        {
            $data['sign'] = '';
        }

        $sign = trim($data['sign']);
        
        unset($data['sign']);
        $partnerkey = 'erp';

        app('log')->debug('JushuitanCheck_sign:' . self::gen_sign($data,$partnerkey));
        // app('log')->debug('JushuitanCheck_request:' . var_export($request, 1));
        if (!$sign || $sign != self::gen_sign($data,$partnerkey) )
        {
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
