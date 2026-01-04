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

namespace ThirdPartyBundle\Services;

use ThirdPartyBundle\Entities\SaasErpLog;

class SaasErpLogService
{
    public $entityRepository;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(SaasErpLog::class);
    }

    // 如果可以直接调取Repositories中的方法，则直接调用
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }

    public function saveLog($companyId, $method, $inputData = [], $id = 0)
    {
        $logParams = [
            'company_id' => $companyId,
            'api_type' => 'request',
            'worker' => $method,
            'status' => 'start',
        ];
        if ($inputData['result'] ?? null) {
            $logParams['result'] = $inputData['result'];
        }
        if ($inputData['params'] ?? null) {
            $logParams['params'] = $inputData['params'];
        }
        if ($inputData['status'] ?? null) {
            $logParams['status'] = $inputData['status'];
        }
        if ($inputData['runtime'] ?? null) {
            $logParams['runtime'] = $inputData['runtime'];
        }
        if ($id) {
            $filter['id'] = $id;
            $result = $this->entityRepository->updateOneBy($filter, $logParams);
        } else {
            $result = $this->entityRepository->create($logParams);
        }
        return $result;
    }
}
