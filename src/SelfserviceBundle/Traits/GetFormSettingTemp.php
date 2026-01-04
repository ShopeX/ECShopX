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

namespace SelfserviceBundle\Traits;

use Dingo\Api\Exception\ResourceException;

trait GetFormSettingTemp
{
    public function getTempId($companyId, $formType = 'physical', $isCheck = false)
    {
        switch ($formType) {
            case 'physical':  //体测报告表单
                $key = 'settingPhysical:'.$companyId;
                $result = app('redis')->connection('companys')->get($key);
                $result = $result ? json_decode($result, true) : [];
                if ((!$result || !$result['status']) && $isCheck) {
                    throw new ResourceException('未开启体测报告功能');
                }
                $tempId = $result['temp_id'] ?? 0;
                return $tempId;
                break;
        }
    }

    public function getStatus($companyId, $formType = 'physical')
    {
        switch ($formType) {
            case 'physical':  //体测报告表单
                $key = 'settingPhysical:'.$companyId;
                $result = app('redis')->connection('companys')->get($key);
                $result = $result ? json_decode($result, true) : [];
                $status = 0;
                if ($result && $result['status']) {
                    $status = 1;
                }
                return $status;
                break;
        }
    }
}
