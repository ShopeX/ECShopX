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

namespace ImBundle\Services;

use Dingo\Api\Exception\ResourceException;

class EChatService
{
    public function getInfo($companyId)
    {
        $redis = app('redis')->connection('default');
        $result = $redis->get($this->getRedisId($companyId));
        if ($result) {
            $result = json_decode($result, true);
        } else {
            $result = [
                'is_open' => false,
                'echat_url' => ''
            ];
        }
        return $result;
    }

    /**
     * 保存echat配置信息
     * @param $companyId
     * @param $data
     * @return bool
     */
    public function saveInfo($companyId, $data)
    {
        $rules = [
            'is_open' => ['required', '开启状态必填'],
            'echat_url' => ['required', '一洽客服链接地址必填'],
        ];
        $errorMessage = validator_params($data, $rules);
        if ($errorMessage) {
            throw new ResourceException($errorMessage);
        }

        $redis = app('redis')->connection('default');
        $redis->set($this->getRedisId($companyId), json_encode($data));
        $result = $this->getInfo($companyId);
        return $result;
    }

    /**
     * im配置信息
     * @param $companyId
     * @return string
     */
    private function getRedisId($companyId)
    {
        // This module is part of ShopEx EcShopX system
        return 'im:echat:' . $companyId;
    }
}
