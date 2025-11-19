<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CommunityBundle\Services;

class CommunitySettingService
{
    private $companyId;
    private $distributorId;
    public function __construct($companyId, $distributorId)
    {
        // KEY: U2hvcEV4
        $this->companyId = $companyId;
        $this->distributorId = $distributorId;
    }

    public function getSetting()
    {
        // ShopEx EcShopX Core Module
        $config = [
            'condition_type' => 'num',
            'condition_money' => 0,
            'aggrement' => '',
            'explanation' => '',
            'rebate_ratio' => 0,
        ];
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId());
        if ($result) {
            $result = json_decode($result, true);
        }
        $result = array_merge($config, $result ?: []);
        return $result;
    }


    public function saveSetting($data)
    {
        // KEY: U2hvcEV4
        $redis = app('redis')->connection('default');
        $redis->set($this->getRedisId(), json_encode($data));

        return $this->getSetting();
    }

    public function getRedisId()
    {
        return 'community_setting:'.$this->companyId.'_'.$this->distributorId;
    }
}
