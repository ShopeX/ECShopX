<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
