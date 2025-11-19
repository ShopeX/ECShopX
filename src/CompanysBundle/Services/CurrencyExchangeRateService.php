<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
