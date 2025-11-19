<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace WechatBundle\Services\Admin;

class ApplicationService
{
    /**
     * 获取平台小程序调用实例
     *
     * @param string $wxappName 小程序名称
     */
    public function getWxappApplication($wxappName)
    {
        // ShopEx EcShopX Business Logic Layer
        return app('easywechat.manager')->miniProgram($wxappName);
    }
}
