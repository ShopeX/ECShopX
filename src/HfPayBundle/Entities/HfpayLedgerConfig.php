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

namespace HfPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * HfpayLedgerConfig 分账配置表
 *
 * @ORM\Table(name="hfpay_ledger_config", options={"comment":"分账配置表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayLedgerConfigRepository")
 */

class HfpayLedgerConfig
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_ledger_config_id", type="bigint", options={"comment":"分账配置表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_ledger_config_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_open", type="string", options={"comment":"是否开启分账","default":"false"})
     */
    private $is_open = 'false';

    /**
     * @var string
     *
     * 1 平台/总部
     * 2 店铺独立收款
     *
     * @ORM\Column(name="business_type", type="string", options={"default": 1, "comment":"分账业务模式"})
     */
    private $business_type = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="agent_number", type="string", nullable=true, options={"comment":"代理商商户号"})
     */
    private $agent_number;

    /**
     * @var string
     *
     * @ORM\Column(name="provider_number", type="string", nullable=true, options={"comment":"服务商渠道号"})
     */
    private $provider_number;

    /**
     * @var integer
     *
     * @ORM\Column(name="rate", type="integer", options={"unsigned":true, "comment":"平台服务费率"})
     */
    private $rate;

    /**
     * @var string
     *
     * @ORM\Column(name="app_id", type="string", nullable=true, options={"comment":"绑定的微信小程序appid"})
     */
    private $app_id;

    /**
     * Get hfpayLedgerConfigId.
     *
     * @return int
     */
    public function getHfpayLedgerConfigId()
    {
        return $this->hfpay_ledger_config_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayLedgerConfig
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set isOpen.
     *
     * @param string $isOpen
     *
     * @return HfpayLedgerConfig
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen.
     *
     * @return string
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set businessType.
     *
     * @param string $businessType
     *
     * @return HfpayLedgerConfig
     */
    public function setBusinessType($businessType)
    {
        $this->business_type = $businessType;

        return $this;
    }

    /**
     * Get businessType.
     *
     * @return string
     */
    public function getBusinessType()
    {
        return $this->business_type;
    }

    /**
     * Set agentNumber.
     *
     * @param string $agentNumber
     *
     * @return HfpayLedgerConfig
     */
    public function setAgentNumber($agentNumber)
    {
        $this->agent_number = $agentNumber;

        return $this;
    }

    /**
     * Get agentNumber.
     *
     * @return string
     */
    public function getAgentNumber()
    {
        return $this->agent_number;
    }

    /**
     * Set providerNumber.
     *
     * @param string $providerNumber
     *
     * @return HfpayLedgerConfig
     */
    public function setProviderNumber($providerNumber)
    {
        $this->provider_number = $providerNumber;

        return $this;
    }

    /**
     * Get providerNumber.
     *
     * @return string
     */
    public function getProviderNumber()
    {
        return $this->provider_number;
    }

    /**
     * Set rate.
     *
     * @param int $rate
     *
     * @return HfpayLedgerConfig
     */
    public function setRate($rate)
    {
        $this->rate = $rate;

        return $this;
    }

    /**
     * Get rate.
     *
     * @return int
     */
    public function getRate()
    {
        return $this->rate;
    }

    /**
     * Set appId.
     *
     * @param string $appId
     *
     * @return HfpayLedgerConfig
     */
    public function setAppId($appId)
    {
        $this->app_id = $appId;

        return $this;
    }

    /**
     * Get appId.
     *
     * @return string
     */
    public function getAppId()
    {
        return $this->app_id;
    }
}
