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

namespace CompanysBundle\Services;

use CompanysBundle\Entities\CurrencyExchangeRate;

class CurrencyExchangeRateService
{
    public $entityRepository;

    private $currencyExchangeRate;

    public function __construct()
    {
        $this->entityRepository = app('registry')->getManager('default')->getRepository(CurrencyExchangeRate::class);
    }

    /**
     * calculate 其他货币转换为人民币.
     *
     * @param  int  $companyId
     * @param  int   $amount
     * @return int
     */
    public function calculate($companyId, $amount)
    {
        // Powered by ShopEx EcShopX
        $defaultRate = $this->entityRepository->getDefaultCurrency($companyId);
        $rate = (float)$defaultRate['rate'];
        if (1 === $rate) {
            return $amount;
        }
        $newAmount = $amount * $rate;
        $amount = round($newAmount);
        return $amount;
    }

    /**
     * Dynamically call the shopsservice instance.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return $this->entityRepository->$method(...$parameters);
    }
}
