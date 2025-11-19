<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Traits;

use WechatBundle\Entities\Weapp;
use Dingo\Api\Exception\ResourceException;

Trait AuthorizerWxapp
{
    public function getAuthorizerAppId($templateName, $companyId)
    {
        $weappinfo = app('registry')->getManager('default')->getRepository(Weapp::class)->findOneBy(['company_id' => $companyId, 'template_name' => $templateName]);
        if (empty($weappinfo)) {
            throw new ResourceException("未绑定小程序,稍后配置");
        }
        return $weappinfo->getAuthorizerAppid();
    }
}
