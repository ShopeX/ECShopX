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

namespace SelfserviceBundle\Services;

use SelfserviceBundle\Entities\FormSetting;
use Dingo\Api\Exception\ResourceException;

class FormSettingService
{
    /** @var \SelfserviceBundle\Repositories\FormSettingRepository */
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(FormSetting::class);
    }

    public function saveData($params, $filter = [])
    {
        if (isset($params['company_id'], $params['field_name'])) {
            $newfilter = [
                'company_id' => $params['company_id'],
                'field_name' => $params['field_name'],
                'status' => 1,
            ];
        }

        if ($filter['id'] ?? 0) {
            $newfilter['id|neq'] = $filter['id'];
        }
        $lists = $this->entityRepository->lists($newfilter);
        if (intval($lists['total_count'] ?? 0) > 0) {
            //前端要求改成可以重复的
            // throw new ResourceException('表单元素英文唯一标示已存在，请更换');
        }

        if (in_array($params['form_element'], ['radio', 'checkbox', 'select'])) {
            foreach ($params['options'] as $key => $value) {
                if (!$value['value']) {
                    unset($params['options'][$key]);
                }
            }
        } else {
            $params['options'] = [];
        }

        if ($filter) {
            return $this->entityRepository->updateOneBy($filter, $params);
        } else {
            return $this->entityRepository->create($params);
        }
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
