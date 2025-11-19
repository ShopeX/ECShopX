<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services\Wxapp\TemplateCheck;

/**
 * 源源客源源客助力
 * 参数配置类
 */
class Cutdown
{
    /**
     * 保存配置参数
     */
    public function check($authorizerAppId, $wxaAppId, $templateName, $wxaName)
    {
        return true;
    }

    public function checkPermission($authorizerAppId)
    {
        return true;
    }
}
