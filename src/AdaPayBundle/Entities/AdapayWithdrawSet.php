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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * AdapayWithdrawSet 提现设置表
 *
 * @ORM\Table(name="adapay_withdraw_set", options={"comment":"提现设置表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayWithdrawSetRepository")
 */

class AdapayWithdrawSet
{
    // use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"提现配置表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isAuto", type="boolean", options={"comment":"是否支持自动提现"})
     */
    private $isAuto;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;
    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"经销商 id"})
     */

    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="cash_amt", type="string",nullable=true, options={"comment":"取现金额，必须大于0，人民币为元"})
     */
    private $cash_amt;

    /**
     * @var string
     *
     * @ORM\Column(name="cash_type", type="string", options={"comment":"取现方式 T0：T0取现; T1：T1取现 D1：D1取现"})
     */
    private $cash_type;

    /**
     * @var string
     *
     * @ORM\Column(name="rule", type="array", options={"comment":"提现规则"})
     */
    private $rule;


    /**
     * Get Id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set isAuto.
     *
     * @param int $isAuto
     *
     * @return AdapayWithdrawSet
     */
    public function setIsAuto($isAuto)
    {
        $this->isAuto = $isAuto;

        return $this;
    }

    /**
     * Get isAuto.
     *
     * @return int
     */
    public function getIsAuto()
    {
        return $this->isAuto;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AdapayWithdrawSet
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
     * Set distributorId
     *
     * @param string $userId
     *
     * @return PromoterCashWithdrawal
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return string
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set cashAmt.
     *
     * @param string $cacheAmt
     *
     * @return AdapayWithdrawSet
     */
    public function setCashAmt($cashAmt)
    {
        $this->cash_amt = $cashAmt;

        return $this;
    }

    /**
     * Get cashAmt.
     *
     * @return string
     */
    public function getCashAmt()
    {
        return $this->cash_amt;
    }

    /**
     * Set cashType.
     *
     * @param bool $cashType
     *
     * @return AdapayWithdrawSet
     */
    public function setCashType($cashType)
    {
        $this->cash_type = $cashType;

        return $this;
    }

    /**
     * Get cashType.
     *
     * @return bool
     */
    public function getCashType()
    {
        return $this->cash_type;
    }

    /**
     * Set rule.
     *
     * @param bool rule
     *
     * @return Rule
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Get rule.
     *
     * @return bool
     */
    public function getRule()
    {
        return $this->rule;
    }
}
