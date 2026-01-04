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

namespace PopularizeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PromoterBrokerageStatistics 分销佣金统计
 *
 * @ORM\Table(name="popularize_brokerage_statistics", options={"comment":"分销佣金统计"}, indexes={
 *    @ORM\Index(name="ix_user_id", columns={"user_id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\PromoterBrokerageStatisticsRepository")
 */
class PromoterBrokerageStatistics
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_total_price", type="bigint", nullable=true, options={"comment":"分销商品总金额", "default": 0})
     */
    private $item_total_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_total", type="bigint", nullable=true, options={"comment":"分销佣金总金额", "default": 0})
     */
    private $rebate_total;


    /**
     * @var integer
     *
     * @ORM\Column(name="point_total", type="bigint", nullable=false, options={"comment":"分销佣金总积分", "default": 0})
     */
    private $point_total;

    /**
     * @var integer
     *
     * @ORM\Column(name="no_close_rebate", type="bigint", nullable=true, options={"comment":"未结算佣金", "default": 0})
     */
    private $no_close_rebate;

    /**
     * @var integer
     *
     * @ORM\Column(name="no_close_point", type="bigint", nullable=false, options={"comment":"未结算佣金积分", "default": 0})
     */
    private $no_close_point;

    /**
     * @var integer
     *
     * @ORM\Column(name="cash_withdrawal_rebate", type="bigint", nullable=true, options={"comment":"可提现佣金", "default": 0})
     */
    private $cash_withdrawal_rebate;


    /**
     * @var integer
     *
     * @ORM\Column(name="cash_withdrawal_point", type="bigint", nullable=false, options={"comment":"订单返佣中可使用积分", "default": 0})
     */
    private $cash_withdrawal_point;

    /**
     * @var integer
     *
     * @ORM\Column(name="freeze_cash_withdrawal_rebate", type="bigint", nullable=true, options={"comment":"申请提现佣金，冻结提现佣金", "default": 0})
     */
    private $freeze_cash_withdrawal_rebate;

    /**
     * @var integer
     *
     * @ORM\Column(name="payed_rebate", type="bigint", nullable=true, options={"comment":"已提现佣金", "default": 0})
     */
    private $payed_rebate;

    /**
     * @var integer
     *
     * @ORM\Column(name="recharge_rebate", type="bigint", nullable=true, options={"comment":"充值返佣", "default": 0})
     */
    private $recharge_rebate;


    /**
     * @var integer
     *
     * @ORM\Column(name="recharge_point", type="bigint", nullable=false, options={"comment":"充值返佣金积分", "default": 0})
     */
    private $recharge_point;


    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return PromoterBrokerageStatistics
     */
    public function setUserId($userId)
    {
        // ShopEx EcShopX Service Component
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PromoterBrokerageStatistics
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
     * Set itemTotalPrice.
     *
     * @param int|null $itemTotalPrice
     *
     * @return PromoterBrokerageStatistics
     */
    public function setItemTotalPrice($itemTotalPrice = null)
    {
        $this->item_total_price = $itemTotalPrice;

        return $this;
    }

    /**
     * Get itemTotalPrice.
     *
     * @return int|null
     */
    public function getItemTotalPrice()
    {
        return $this->item_total_price;
    }

    /**
     * Set rebateTotal.
     *
     * @param int|null $rebateTotal
     *
     * @return PromoterBrokerageStatistics
     */
    public function setRebateTotal($rebateTotal = null)
    {
        $this->rebate_total = $rebateTotal;

        return $this;
    }

    /**
     * Get rebateTotal.
     *
     * @return int|null
     */
    public function getRebateTotal()
    {
        return $this->rebate_total;
    }

    /**
     * Set pointTotal.
     *
     * @param int $pointTotal
     *
     * @return PromoterBrokerageStatistics
     */
    public function setPointTotal($pointTotal)
    {
        $this->point_total = $pointTotal;

        return $this;
    }

    /**
     * Get pointTotal.
     *
     * @return int
     */
    public function getPointTotal()
    {
        return $this->point_total;
    }

    /**
     * Set noCloseRebate.
     *
     * @param int|null $noCloseRebate
     *
     * @return PromoterBrokerageStatistics
     */
    public function setNoCloseRebate($noCloseRebate = null)
    {
        $this->no_close_rebate = $noCloseRebate;

        return $this;
    }

    /**
     * Get noCloseRebate.
     *
     * @return int|null
     */
    public function getNoCloseRebate()
    {
        return $this->no_close_rebate;
    }

    /**
     * Set noClosePoint.
     *
     * @param int $noClosePoint
     *
     * @return PromoterBrokerageStatistics
     */
    public function setNoClosePoint($noClosePoint)
    {
        $this->no_close_point = $noClosePoint;

        return $this;
    }

    /**
     * Get noClosePoint.
     *
     * @return int
     */
    public function getNoClosePoint()
    {
        return $this->no_close_point;
    }

    /**
     * Set cashWithdrawalRebate.
     *
     * @param int|null $cashWithdrawalRebate
     *
     * @return PromoterBrokerageStatistics
     */
    public function setCashWithdrawalRebate($cashWithdrawalRebate = null)
    {
        $this->cash_withdrawal_rebate = $cashWithdrawalRebate;

        return $this;
    }

    /**
     * Get cashWithdrawalRebate.
     *
     * @return int|null
     */
    public function getCashWithdrawalRebate()
    {
        return $this->cash_withdrawal_rebate;
    }

    /**
     * Set cashWithdrawalPoint.
     *
     * @param int $cashWithdrawalPoint
     *
     * @return PromoterBrokerageStatistics
     */
    public function setCashWithdrawalPoint($cashWithdrawalPoint)
    {
        $this->cash_withdrawal_point = $cashWithdrawalPoint;

        return $this;
    }

    /**
     * Get cashWithdrawalPoint.
     *
     * @return int
     */
    public function getCashWithdrawalPoint()
    {
        return $this->cash_withdrawal_point;
    }

    /**
     * Set freezeCashWithdrawalRebate.
     *
     * @param int|null $freezeCashWithdrawalRebate
     *
     * @return PromoterBrokerageStatistics
     */
    public function setFreezeCashWithdrawalRebate($freezeCashWithdrawalRebate = null)
    {
        $this->freeze_cash_withdrawal_rebate = $freezeCashWithdrawalRebate;

        return $this;
    }

    /**
     * Get freezeCashWithdrawalRebate.
     *
     * @return int|null
     */
    public function getFreezeCashWithdrawalRebate()
    {
        return $this->freeze_cash_withdrawal_rebate;
    }

    /**
     * Set payedRebate.
     *
     * @param int|null $payedRebate
     *
     * @return PromoterBrokerageStatistics
     */
    public function setPayedRebate($payedRebate = null)
    {
        $this->payed_rebate = $payedRebate;

        return $this;
    }

    /**
     * Get payedRebate.
     *
     * @return int|null
     */
    public function getPayedRebate()
    {
        return $this->payed_rebate;
    }

    /**
     * Set rechargeRebate.
     *
     * @param int|null $rechargeRebate
     *
     * @return PromoterBrokerageStatistics
     */
    public function setRechargeRebate($rechargeRebate = null)
    {
        $this->recharge_rebate = $rechargeRebate;

        return $this;
    }

    /**
     * Get rechargeRebate.
     *
     * @return int|null
     */
    public function getRechargeRebate()
    {
        return $this->recharge_rebate;
    }

    /**
     * Set rechargePoint.
     *
     * @param int $rechargePoint
     *
     * @return PromoterBrokerageStatistics
     */
    public function setRechargePoint($rechargePoint)
    {
        $this->recharge_point = $rechargePoint;

        return $this;
    }

    /**
     * Get rechargePoint.
     *
     * @return int
     */
    public function getRechargePoint()
    {
        return $this->recharge_point;
    }
}
