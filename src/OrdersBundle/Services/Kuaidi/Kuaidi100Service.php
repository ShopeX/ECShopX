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

namespace OrdersBundle\Services\Kuaidi;

use OrdersBundle\Interfaces\Kuaidi;

class Kuaidi100Service implements Kuaidi
{
    public function setKuaidiSetting($companyId, $params)
    {
        if (isset($params['is_open']) && $params['is_open'] == 'true') {
            app('redis')->set('kuaidiTypeOpenConfig:'. sha1($companyId), 'kuaidi100');
        } else {
            app('redis')->del('kuaidiTypeOpenConfig:'. sha1($companyId));
        }
        return app('redis')->set($this->genReidsId($companyId), json_encode($params));
    }

    public function getKuaidiSetting($companyId)
    {
        $data = app('redis')->get($this->genReidsId($companyId));
        if ($data) {
            $data = json_decode($data, true);
            $kuaidiType = app('redis')->get('kuaidiTypeOpenConfig:' . sha1($companyId));
            if ($kuaidiType == 'kuaidi100') {
                $data['is_open'] = true;
            } else {
                $data['is_open'] = false;
            }
            return $data;
        } else {
            return [];
        }
    }


    /**
     * 获取redis存储的ID
     */
    private function genReidsId($companyId)
    {
        return 'kuaidi100KuaidiSetting:' . sha1($companyId);
    }
}
