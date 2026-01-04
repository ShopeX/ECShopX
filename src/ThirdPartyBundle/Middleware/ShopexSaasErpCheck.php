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
use CompanysBundle\Services\CompanysService;
use ThirdPartyBundle\Services\SaasCertCentre\CertService;

class ShopexSaasErpCheck
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
        app('log')->debug('saaserp request handle=>:'.var_export($data, 1)."\n");

        if (!isset($data['ac']) || !$data['ac']) {
            $data['ac'] = '';
        }

        $nodeId = $data['to_node_id'] ?? '';
        $certService = new CertService();
        $companyId = $certService->getCompanyId($nodeId);

        $companysService = new CompanysService();
        $shopexUid = $companysService->getPassportUidByCompanyId($companyId);
        $certService = new CertService(false, $companyId, $shopexUid);
        $certSetting = $certService->getCertSetting();
        app('log')->debug("saaserp ShopexErpCheck certSetting====>" . json_encode($certSetting)."\n");
        $token = $certSetting['token'];
        $check_ac = $this->check_shopex_ac_new($data, $token);
        if (!$check_ac) {
            return response()->json(['result' => 'fail', 'code' => 0,'shopex_time' => time(), 'msg' => 'sign error', 'info' => json_encode($data)]);
        }
        return $next($request);
    }

    public function check_shopex_ac_new($params, $token)
    {
        $verfy = strtolower(trim($params['ac']));
        unset($params['ac']);

        ksort($params);
        $tmp_verfy = '';
        foreach ($params as $key => $value) {
            $tmp_verfy .= $params[$key];
        }
        $sign = strtolower(md5(trim($tmp_verfy.$token)));
        app('log')->debug("saaserp ShopexErpCheck token====>" . $token."\n");
        app('log')->debug("saaserp ShopexErpCheck_verfy:" . $verfy."\n");
        app('log')->debug("saaserp  ShopexErpCheck_sign:" . $sign."\n");
        if ($verfy && $verfy == $sign) {
            return true;
        } else {
            return false;
        }
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
