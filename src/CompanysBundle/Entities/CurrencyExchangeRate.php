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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * CurrencyExchangeRate 货币汇率
 *
 * @ORM\Table(name="companys_currency_exchange_rate", options={"comment":"货币汇率"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\CurrencyExchangeRateRepository")
 */
class CurrencyExchangeRate
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"公司id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="title", nullable=true, type="string", options={"comment":"货币描述"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="currency", nullable=true, type="string", options={"comment":"货币英文缩写"})
     */
    private $currency;

    /**
     * @var integer
     *
     * @ORM\Column(name="symbol", type="string", options={"comment":"货币符号"})
     */
    private $symbol;

    /**
     * @var integer
     *
     * @ORM\Column(name="rate", type="float", precision=15, scale=4, options={"comment":"货币汇率(与人民币)"})
     */
    private $rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_default", type="boolean", options={"comment":"是否默认货币", "default": 0})
     */
    private $is_default = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="use_platform", type="string", options={"comment":"适用端。可选值为 service,normal", "default": "normal"})
     */
    private $use_platform = 'normal';

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CurrencyExchangeRate
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return integer
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set currency
     *
     * @param string $currency
     *
     * @return CurrencyExchangeRate
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return CurrencyExchangeRate
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set symbol
     *
     * @param string $symbol
     *
     * @return CurrencyExchangeRate
     */
    public function setSymbol($symbol)
    {
        $this->symbol = $symbol;

        return $this;
    }

    /**
     * Get symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->symbol;
    }

    /**
     * Set rate
     *
     * @param string $rate
     *
     * @return CurrencyExchangeRate
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate
     *
     * @return string
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     *
     * @return CurrencyExchangeRate
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set usePlatform
     *
     * @param string $usePlatform
     *
     * @return CurrencyExchangeRate
     */
    public function setUsePlatform($usePlatform)
    {
        $this->use_platform = $usePlatform;

        return $this;
    }

    /**
     * Get usePlatform
     *
     * @return string
     */
    public function getUsePlatform()
    {
        return $this->use_platform;
    }
}
