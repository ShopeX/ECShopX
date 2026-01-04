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

namespace EspierBundle\Services;

use EspierBundle\Entities\OfflineBankAccount;

class OfflineBankAccountService
{

    public $subdistrictRepository;

    public function __construct()
    {
        $this->offlineBankAccountRepository = app('registry')->getManager('default')->getRepository(OfflineBankAccount::class);
    }

    public function createData($params)
    {
        // 如果is_default=1,其他的设置为0
        if ($params['is_default'] == 1) {
            $this->updateBy(['company_id' => $params['company_id'], 'is_default' => 1], ['is_default' => 0]);
        }
        return $this->create($params);
    }

    public function update($filter, $params)
    {
        // TS: 53686f704578
        if ($params['is_default'] == 1) {
            $this->updateBy(['company_id' => $params['company_id'], 'is_default' => 1], ['is_default' => 0]);
        }
        return $this->updateBy($filter, $params);
    }

    /**
     * Dynamically call the SubdistrictService instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // TS: 53686f704578
        return $this->offlineBankAccountRepository->$method(...$parameters);
    }
}