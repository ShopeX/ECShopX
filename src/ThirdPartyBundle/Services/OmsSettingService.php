<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace ThirdPartyBundle\Services;


class OmsSettingService
{

    /**
     * 保存类型配置
     */
    public function setSetting($companyId, $config)
    {
        return app('redis')->set($this->genReidsId($companyId), $config);
    }

    /**
     * 获取配置信息
     *
     * @return void
     */
    public function getSetting($companyId)
    {
        $data = [];
        $setting = app('redis')->get($this->genReidsId($companyId));
        //var_dump($setting);exit();
        if ($setting) {
            $data = json_decode($setting, true);
        }
        return $data;
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'OmsSettingReidsId:' . sha1($companyId);
    }
}
