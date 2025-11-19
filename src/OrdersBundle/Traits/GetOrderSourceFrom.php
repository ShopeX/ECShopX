<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
