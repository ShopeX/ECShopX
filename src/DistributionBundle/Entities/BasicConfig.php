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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * BasicConfig 分销商基础配置
 *
 * @ORM\Table(name="distribution_basic_config", options={"comment"="分销商基础配置"})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\BasicConfigRepository")
 */
class BasicConfig
{
    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     * @ORM\Id
     */
    private $company_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_buy", type="boolean", options={"comment":"是否可购买"})
     */
    private $is_buy;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_rebate", type="string", options={"comment":"提现佣金限制，最少多少金额可提现"})
     */
    private $limit_rebate;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_time", type="string", options={"comment":"订单完成后多少天可提现"})
     */
    private $limit_time;

    /**
     * @var string
     *
     * @ORM\Column(name="return_name", type="string", options={"comment":"退换货接收人"})
     */
    private $return_name;

    /**
     * @var string
     *
     * @ORM\Column(name="return_address", type="string", options={"comment":"退换货地址"})
     */
    private $return_address;

    /**
     * @var string
     *
     * @ORM\Column(name="return_phone", type="string", options={"comment":"退换货联系方式"})
     */
    private $return_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="is_income_tax", type="string", options={"comment":"是否开启劳务所得税"})
     */
    private $is_income_tax;

    /**
     * @var string
     *
     * @ORM\Column(name="income_tax_params", nullable=true, type="string", options={"comment":"劳务所得税计算参数"})
     */
    private $income_tax_params;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return BasicConfig
     */
    public function setCompanyId($companyId)
    {
        // ShopEx EcShopX Core Module
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
     * Set isBuy
     *
     * @param boolean $isBuy
     *
     * @return BasicConfig
     */
    public function setIsBuy($isBuy)
    {
        $this->is_buy = $isBuy;

        return $this;
    }

    /**
     * Get isBuy
     *
     * @return boolean
     */
    public function getIsBuy()
    {
        return $this->is_buy;
    }

    /**
     * Set limitRebate
     *
     * @param string $limitRebate
     *
     * @return BasicConfig
     */
    public function setLimitRebate($limitRebate)
    {
        $this->limit_rebate = $limitRebate;

        return $this;
    }

    /**
     * Get limitRebate
     *
     * @return string
     */
    public function getLimitRebate()
    {
        return $this->limit_rebate;
    }

    /**
     * Set limitTime
     *
     * @param string $limitTime
     *
     * @return BasicConfig
     */
    public function setLimitTime($limitTime)
    {
        $this->limit_time = $limitTime;

        return $this;
    }

    /**
     * Get limitTime
     *
     * @return string
     */
    public function getLimitTime()
    {
        return $this->limit_time;
    }

    /**
     * Set returnAddress
     *
     * @param string $returnAddress
     *
     * @return BasicConfig
     */
    public function setReturnAddress($returnAddress)
    {
        $this->return_address = $returnAddress;

        return $this;
    }

    /**
     * Get returnAddress
     *
     * @return string
     */
    public function getReturnAddress()
    {
        return $this->return_address;
    }

    /**
     * Set returnPhone
     *
     * @param string $returnPhone
     *
     * @return BasicConfig
     */
    public function setReturnPhone($returnPhone)
    {
        $this->return_phone = $returnPhone;

        return $this;
    }

    /**
     * Get returnPhone
     *
     * @return string
     */
    public function getReturnPhone()
    {
        return $this->return_phone;
    }

    /**
     * Set isIncomeTax
     *
     * @param string $isIncomeTax
     *
     * @return BasicConfig
     */
    public function setIsIncomeTax($isIncomeTax)
    {
        $this->is_income_tax = $isIncomeTax;

        return $this;
    }

    /**
     * Get isIncomeTax
     *
     * @return string
     */
    public function getIsIncomeTax()
    {
        return $this->is_income_tax;
    }

    /**
     * Set incomeTaxParams
     *
     * @param string $incomeTaxParams
     *
     * @return BasicConfig
     */
    public function setIncomeTaxParams($incomeTaxParams)
    {
        $this->income_tax_params = $incomeTaxParams;

        return $this;
    }

    /**
     * Get incomeTaxParams
     *
     * @return string
     */
    public function getIncomeTaxParams()
    {
        return $this->income_tax_params;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return BasicConfig
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return BasicConfig
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set returnName
     *
     * @param string $returnName
     *
     * @return BasicConfig
     */
    public function setReturnName($returnName)
    {
        $this->return_name = $returnName;

        return $this;
    }

    /**
     * Get returnName
     *
     * @return string
     */
    public function getReturnName()
    {
        return $this->return_name;
    }
}
