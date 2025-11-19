<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SystemLinkBundle\Services;

class WdtErpSettingService
{
    /**
     * 设置旺店通ERP配置
     */
    public function setWdtErpSetting($companyId, $data)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 获取旺店通ERP配置
     */
    public function getWdtErpSetting($companyId, $redisId = '')
    {
        $redisKey = $redisId ?: $this->genReidsId($companyId);
        $data = app('redis')->get($redisKey);
        if ($data) {
            $data = json_decode($data, true);
            return $data;
        } else {
            return ['is_open' => false];
        }
    }

    /**
     * 获取前缀
     * @return string
     */
    public function getRedisPrefix()
    {
        return 'WdtErpSetting:';
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return $this->getRedisPrefix(). sha1($companyId);
    }
}
