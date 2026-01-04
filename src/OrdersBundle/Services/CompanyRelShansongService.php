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

namespace OrdersBundle\Services;

use OrdersBundle\Entities\CompanyRelShansong;
use OrdersBundle\Services\LocalDeliveryService;

class CompanyRelShansongService
{
    private $companyRelShansongReposity;

    public function __construct()
    {
        $this->companyRelShansongReposity = app('registry')->getManager('default')->getRepository(CompanyRelShansong::class);
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->companyRelShansongReposity->$method(...$parameters);
    }
}
