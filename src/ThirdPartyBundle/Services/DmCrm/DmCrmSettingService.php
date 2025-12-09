<?php

namespace ThirdPartyBundle\Services\DmCrm;

class DmCrmSettingService
{
    /**
     * 设置达摩CRM配置
     */
    public function setDmCrmSetting($companyId, $data)
    {
        return app('redis')->set($this->genReidsId($companyId), json_encode($data));
    }

    /**
     * 获取达摩CRM配置
     */
    public function getDmCrmSetting($companyId, $redisId = '')
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
        return 'DmCrmSetting:';
    }

    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return $this->getRedisPrefix(). sha1($companyId);
    }
}
