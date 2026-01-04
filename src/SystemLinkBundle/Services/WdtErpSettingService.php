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
