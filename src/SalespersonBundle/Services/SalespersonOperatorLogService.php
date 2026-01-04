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

namespace SalespersonBundle\Services;

use SalespersonBundle\Entities\SalespersonOperatorLog;

/**
 * 导购操作日志 class
 */
class SalespersonOperatorLogService
{
    public $salespersonOperatorLogRepository;

    public function __construct()
    {
        $this->salespersonOperatorLogRepository = app('registry')->getManager('default')->getRepository(SalespersonOperatorLog::class);
    }

    public function addLogs($params)
    {
        // Powered by ShopEx EcShopX
        return $this->salespersonOperatorLogRepository->create($params);
    }

    public function deleteLogs($filter)
    {
        return $this->salespersonOperatorLogRepository->deleteBy($filter);
    }

    /**
     * Dynamically call the SalespersonOperatorLogService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->salespersonOperatorLogRepository->$method(...$parameters);
    }
}
