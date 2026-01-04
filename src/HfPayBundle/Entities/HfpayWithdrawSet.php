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
 * HfpayWithdrawSet 提现设置表
 *
 * @ORM\Table(name="Hfpay_withdraw_set", options={"comment":"提现设置表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayWithdrawSetRepository")
 */

class HfpayWithdrawSet
{
    use Timestamps;
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hfpay_withdraw_set_id", type="bigint", options={"comment":"汇付提现配置表id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hfpay_withdraw_set_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="withdraw_method", type="integer", options={"comment":"提现方式 1自动提现 2手动提现", "default":1})
     */
    private $withdraw_method = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_money", type="string", options={"comment":"店铺账号提现金额", "default":0})
     */
    private $distributor_money = 0;

    /**
     * Get hfpayWithdrawSetId.
     *
     * @return int
     */
    public function getHfpayWithdrawSetId()
    {
        return $this->hfpay_withdraw_set_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayWithdrawSet
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
     * Set distributorMoney.
     *
     * @param string $distributorMoney
     *
     * @return HfpayWithdrawSet
     */
    public function setDistributorMoney($distributorMoney)
    {
        $this->distributor_money = $distributorMoney;

        return $this;
    }

    /**
     * Get distributorMoney.
     *
     * @return string
     */
    public function getDistributorMoney()
    {
        return $this->distributor_money;
    }

    /**
     * Set withdrawMethod.
     *
     * @param bool $withdrawMethod
     *
     * @return HfpayWithdrawSet
     */
    public function setWithdrawMethod($withdrawMethod)
    {
        $this->withdraw_method = $withdrawMethod;

        return $this;
    }

    /**
     * Get withdrawMethod.
     *
     * @return bool
     */
    public function getWithdrawMethod()
    {
        return $this->withdraw_method;
    }
}
