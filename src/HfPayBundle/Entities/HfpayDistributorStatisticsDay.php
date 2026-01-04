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
 * HfpayDistributorStatisticsDay 店铺分账数据统计
 *
 * @ORM\Table(name="hfpay_distributor_statistics_day", options={"comment":"店铺分账数据统计"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayDistributorStatisticsDayRepository")
 */

class HfpayDistributorStatisticsDay
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="hf_distributor_day_id", type="bigint", options={"comment":"汇付店铺分账数据统计"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $hf_distributor_day_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"company_id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="date", type="integer", options={"comment":"日期"})
     */
    private $date;

    /**
     * @var integer
     *
     * @ORM\Column(name="income", type="integer", nullable=true, options={"comment":"总计收入"})
     */
    private $income;

    /**
     * @var integer
     *
     * @ORM\Column(name="disburse", type="integer", nullable=true, options={"comment":"总计支出"})
     */
    private $disburse;

    /**
     * @var integer
     *
     * @ORM\Column(name="withdrawal", type="integer", nullable=true, options={"comment":"总计提现"})
     */
    private $withdrawal;

    /**
     * @var integer
     *
     * @ORM\Column(name="balance", type="integer", nullable=true, options={"comment":"余额"})
     */
    private $balance;

    /**
     * @var integer
     *
     * @ORM\Column(name="withdrawal_balance", type="integer", nullable=true, options={"comment":"可提现余额"})
     */
    private $withdrawal_balance;

    /**
     * @var integer
     *
     * @ORM\Column(name="unsettled_funds", type="integer", nullable=true, options={"comment":"未结算资金"})
     */
    private $unsettled_funds;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund", type="integer", nullable=true, options={"comment":"合计退款"})
     */
    private $refund;

    /**
     * @var integer
     *
     * @ORM\Column(name="settlement_funds", type="integer", nullable=true, options={"comment":"已结算资金"})
     */
    private $settlement_funds;

    /**
     * Get hfDistributorDayId.
     *
     * @return int
     */
    public function getHfDistributorDayId()
    {
        return $this->hf_distributor_day_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return HfpayDistributorStatisticsDay
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set date.
     *
     * @param int $date
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date.
     *
     * @return int
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set income.
     *
     * @param int|null $income
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setIncome($income = null)
    {
        $this->income = $income;

        return $this;
    }

    /**
     * Get income.
     *
     * @return int|null
     */
    public function getIncome()
    {
        return $this->income;
    }

    /**
     * Set disburse.
     *
     * @param int|null $disburse
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setDisburse($disburse = null)
    {
        $this->disburse = $disburse;

        return $this;
    }

    /**
     * Get disburse.
     *
     * @return int|null
     */
    public function getDisburse()
    {
        return $this->disburse;
    }

    /**
     * Set withdrawal.
     *
     * @param int|null $withdrawal
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setWithdrawal($withdrawal = null)
    {
        $this->withdrawal = $withdrawal;

        return $this;
    }

    /**
     * Get withdrawal.
     *
     * @return int|null
     */
    public function getWithdrawal()
    {
        return $this->withdrawal;
    }

    /**
     * Set balance.
     *
     * @param int|null $balance
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setBalance($balance = null)
    {
        $this->balance = $balance;

        return $this;
    }

    /**
     * Get balance.
     *
     * @return int|null
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * Set withdrawalBalance.
     *
     * @param int|null $withdrawalBalance
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setWithdrawalBalance($withdrawalBalance = null)
    {
        $this->withdrawal_balance = $withdrawalBalance;

        return $this;
    }

    /**
     * Get withdrawalBalance.
     *
     * @return int|null
     */
    public function getWithdrawalBalance()
    {
        return $this->withdrawal_balance;
    }

    /**
     * Set unsettledFunds.
     *
     * @param int|null $unsettledFunds
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setUnsettledFunds($unsettledFunds = null)
    {
        $this->unsettled_funds = $unsettledFunds;

        return $this;
    }

    /**
     * Get unsettledFunds.
     *
     * @return int|null
     */
    public function getUnsettledFunds()
    {
        return $this->unsettled_funds;
    }

    /**
     * Set refund.
     *
     * @param int|null $refund
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setRefund($refund = null)
    {
        $this->refund = $refund;

        return $this;
    }

    /**
     * Get refund.
     *
     * @return int|null
     */
    public function getRefund()
    {
        return $this->refund;
    }

    /**
     * Set settlementFunds.
     *
     * @param int|null $settlementFunds
     *
     * @return HfpayDistributorStatisticsDay
     */
    public function setSettlementFunds($settlementFunds = null)
    {
        $this->settlement_funds = $settlementFunds;

        return $this;
    }

    /**
     * Get settlementFunds.
     *
     * @return int|null
     */
    public function getSettlementFunds()
    {
        return $this->settlement_funds;
    }
}
