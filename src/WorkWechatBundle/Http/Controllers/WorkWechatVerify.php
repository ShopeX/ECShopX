<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WorkWechatBundle\Http\Controllers;

use WorkWechatBundle\Services\WorkWechatVerifyDomainService;

class WorkWechatVerify
{
    public function domain($verify_name)
    {
        $verify_domain_service = new WorkWechatVerifyDomainService();
        $verify_info = $verify_domain_service->getVerifyInfoByName($verify_name);

        if (!$verify_info) {
            return response('', 404);
        }

        return response($verify_info['contents'], 200)
            ->header('Content-Type', 'text/plain');
    }
}
