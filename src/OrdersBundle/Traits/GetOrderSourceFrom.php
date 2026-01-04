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

namespace OrdersBundle\Traits;

use CompanysBundle\Entities\Companys;

trait GetOrderSourceFrom
{
    public function getOrderSourceFrom($request)
    {
        $authInfo = $request->get('auth');

        if (isset($authInfo['wxapp_appid']) && $authInfo['wxapp_appid']) {
            return 'wxapp';
        }

        if (isset($authInfo['alipay_appid']) && $authInfo['alipay_appid']) {
            return 'aliapp';
        }

        $host = $request->header('origin');
        $host = str_replace(['http://', 'https://'], '', $host);
        if (!$host) {
            return 'unknow';
        }

        $pcSuffix = config('common.pc_domain_suffix');
        preg_match("/^s(\d+)".$pcSuffix."$/", $host, $match);
        if ($match) {
            return 'pc';
        }

        $h5Suffix = config('common.h5_domain_suffix');
        preg_match("/^m(\d+)".$h5Suffix."$/", $host, $match);
        if ($match) {
            return 'h5';
        }

        $companysRepository = app('registry')->getManager('default')->getRepository(Companys::class);
        $exist = $companysRepository->count(['company_id' => $authInfo['company_id'], 'pc_domain' => $host]);
        if ($exist) {
            return 'pc';
        }

        $exist = $companysRepository->count(['company_id' => $authInfo['company_id'], 'h5_domain' => $host]);
        if ($exist) {
            return 'h5';
        }

        return 'unknow';
    }
}
