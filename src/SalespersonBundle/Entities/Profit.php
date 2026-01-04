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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Profit 分润记录表
 *
 * @ORM\Table(name="companys_profit", options={"comment":"分润记录表"})
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\ProfitRepository")
 */
class Profit
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, nullable=true, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_user_id", type="bigint", options={"comment":"分润类型id"})
     */
    private $profit_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_user_type", type="bigint", options={"comment":"1 用户 2 店铺 3 区域经销商 4 总部"})
     */
    private $profit_user_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="bigint", nullable=true, options={"comment":"分润金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="frozen_fee", type="bigint", nullable=true, options={"comment":"冻结金额，以分为单位"})
     */
    private $frozen_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="withdrawals_fee", type="bigint", nullable=true, options={"comment":"可以提现金额，以分为单位"})
     */
    private $withdrawals_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="cashed_fee", type="bigint", nullable=true, options={"comment":"已提现金额，以分为单位"})
     */
    private $cashed_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="commissions", type="bigint", nullable=true, options={"comment":"拉新提成（导购｜门店）"})
     */
    private $commissions;

    /**
     * @var integer
     *
     * @ORM\Column(name="popularize_commissions", type="bigint", nullable=true, options={"comment":"推广提成（导购）"})
     */
    private $popularize_commissions;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_amount", type="bigint", nullable=true, options={"comment":"货款（发货门店｜总部）"})
     */
    private $goods_amount;

    /**
     * @var integer
     *
     * @ORM\Column(name="subsidy", type="bigint", nullable=true, options={"comment":"补贴（经销商）"})
     */
    private $subsidy;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Profit
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
     * Set orderId.
     *
     * @param string|null $orderId
     *
     * @return Profit
     */
    public function setOrderId($orderId = null)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string|null
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set profitUserId.
     *
     * @param int $profitUserId
     *
     * @return Profit
     */
    public function setProfitUserId($profitUserId)
    {
        $this->profit_user_id = $profitUserId;

        return $this;
    }

    /**
     * Get profitUserId.
     *
     * @return int
     */
    public function getProfitUserId()
    {
        return $this->profit_user_id;
    }

    /**
     * Set profitUserType.
     *
     * @param int $profitUserType
     *
     * @return Profit
     */
    public function setProfitUserType($profitUserType)
    {
        $this->profit_user_type = $profitUserType;

        return $this;
    }

    /**
     * Get profitUserType.
     *
     * @return int
     */
    public function getProfitUserType()
    {
        return $this->profit_user_type;
    }

    /**
     * Set totalFee.
     *
     * @param int|null $totalFee
     *
     * @return Profit
     */
    public function setTotalFee($totalFee = null)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee.
     *
     * @return int|null
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set frozenFee.
     *
     * @param int|null $frozenFee
     *
     * @return Profit
     */
    public function setFrozenFee($frozenFee = null)
    {
        $this->frozen_fee = $frozenFee;

        return $this;
    }

    /**
     * Get frozenFee.
     *
     * @return int|null
     */
    public function getFrozenFee()
    {
        return $this->frozen_fee;
    }

    /**
     * Set withdrawalsFee.
     *
     * @param int|null $withdrawalsFee
     *
     * @return Profit
     */
    public function setWithdrawalsFee($withdrawalsFee = null)
    {
        $this->withdrawals_fee = $withdrawalsFee;

        return $this;
    }

    /**
     * Get withdrawalsFee.
     *
     * @return int|null
     */
    public function getWithdrawalsFee()
    {
        return $this->withdrawals_fee;
    }

    /**
     * Set cashedFee.
     *
     * @param int|null $cashedFee
     *
     * @return Profit
     */
    public function setCashedFee($cashedFee = null)
    {
        $this->cashed_fee = $cashedFee;

        return $this;
    }

    /**
     * Get cashedFee.
     *
     * @return int|null
     */
    public function getCashedFee()
    {
        return $this->cashed_fee;
    }

    /**
     * Set commissions.
     *
     * @param int|null $commissions
     *
     * @return Profit
     */
    public function setCommissions($commissions = null)
    {
        $this->commissions = $commissions;

        return $this;
    }

    /**
     * Get commissions.
     *
     * @return int|null
     */
    public function getCommissions()
    {
        return $this->commissions;
    }

    /**
     * Set popularizeCommissions.
     *
     * @param int|null $popularizeCommissions
     *
     * @return Profit
     */
    public function setPopularizeCommissions($popularizeCommissions = null)
    {
        $this->popularize_commissions = $popularizeCommissions;

        return $this;
    }

    /**
     * Get popularizeCommissions.
     *
     * @return int|null
     */
    public function getPopularizeCommissions()
    {
        return $this->popularize_commissions;
    }

    /**
     * Set goodsAmount.
     *
     * @param int|null $goodsAmount
     *
     * @return Profit
     */
    public function setGoodsAmount($goodsAmount = null)
    {
        $this->goods_amount = $goodsAmount;

        return $this;
    }

    /**
     * Get goodsAmount.
     *
     * @return int|null
     */
    public function getGoodsAmount()
    {
        return $this->goods_amount;
    }

    /**
     * Set subsidy.
     *
     * @param int|null $subsidy
     *
     * @return Profit
     */
    public function setSubsidy($subsidy = null)
    {
        $this->subsidy = $subsidy;

        return $this;
    }

    /**
     * Get subsidy.
     *
     * @return int|null
     */
    public function getSubsidy()
    {
        return $this->subsidy;
    }
}
