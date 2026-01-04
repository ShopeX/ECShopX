<?php
/**
 * Copyright 2019-2026 ShopeX
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
