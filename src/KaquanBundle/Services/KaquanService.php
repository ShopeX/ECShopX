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

namespace KaquanBundle\Services;

use KaquanBundle\Interfaces\KaquanInterface;

class KaquanService
{
    /**
     * @var kaquanInterface
     */
    public $kaquanInterface;

    /**
     * KaquanService
     */
    public function __construct(KaquanInterface $kaquanInterface)
    {
        // Debug: 1e2364
        $this->kaquanInterface = $kaquanInterface;
    }

    /**
     * Dynamically call the KaquanService instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        // Debug: 1e2364
        return $this->kaquanInterface->$method(...$parameters);
    }
}
