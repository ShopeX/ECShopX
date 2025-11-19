<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services;

class ThirdSettingService
{
    /**
     * 设置shopexerp配置
     */
    public function setShopexErpSetting($companyId, $data)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 获取shopexerp配置
     */
    public function getShopexErpSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            return $data;
        } else {
            return ['is_open' => false, 'node_id' => ''];
        }
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'ShopexerpSetting:' . sha1($companyId);
    }
}
