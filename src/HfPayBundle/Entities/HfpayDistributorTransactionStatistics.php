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
 * HfpayDistributorTransactionStatistics 店铺交易统计统计
 *
 * @ORM\Table(name="hfpay_distributor_transaction_statistics", options={"comment":"店铺交易统计统计"}, indexes={
 *    @ORM\Index(name="idx_company_id_distributor_id", columns={"company_id", "distributor_id"}),
 * })
 * @ORM\Entity(repositoryClass="HfPayBundle\Repositories\HfpayDistributorTransactionStatisticsRepository")
 */

class HfpayDistributorTransactionStatistics
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"店铺交易统计统计"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

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
     * @ORM\Column(name="order_count", type="integer", options={"comment":"交易总笔数", "default":0})
     */
    private $order_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_total_fee", type="integer", options={"comment":"总计交易金额", "default":0})
     */
    private $order_total_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_refund_count", type="integer", options={"comment":"已退款总笔数", "default":0})
     */
    private $order_refund_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_refund_total_fee", type="integer", options={"comment":"退款总金额", "default":0})
     */
    private $order_refund_total_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_refunding_count", type="integer", options={"comment":"在退总笔数", "default":0})
     */
    private $order_refunding_count = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_refunding_total_fee", type="integer", options={"comment":"在退总金额", "default":0})
     */
    private $order_refunding_total_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_profit_sharing_charge", type="integer", options={"comment":"已结算手续费", "default":0})
     */
    private $order_profit_sharing_charge = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_total_charge", type="integer", options={"comment":"总手续费", "default":0})
     */
    private $order_total_charge = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_refund_total_charge", type="integer", options={"comment":"总退款手续费", "default":0})
     */
    private $order_refund_total_charge = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_un_profit_sharing_total_charge", type="integer", options={"comment":"未结算手续费（包含已退款）", "default":0})
     */
    private $order_un_profit_sharing_total_charge = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_un_profit_sharing_refund_total_charge", type="integer", options={"comment":"未结算已退款手续费", "default":0})
     */
    private $order_un_profit_sharing_refund_total_charge = 0;

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
     * @return HfpayDistributorTransactionStatistics
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
     * @return HfpayDistributorTransactionStatistics
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
     * @return HfpayDistributorTransactionStatistics
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
     * Set orderCount.
     *
     * @param int $orderCount
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderCount($orderCount)
    {
        $this->order_count = $orderCount;

        return $this;
    }

    /**
     * Get orderCount.
     *
     * @return int
     */
    public function getOrderCount()
    {
        return $this->order_count;
    }

    /**
     * Set orderTotalFee.
     *
     * @param int $orderTotalFee
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderTotalFee($orderTotalFee)
    {
        $this->order_total_fee = $orderTotalFee;

        return $this;
    }

    /**
     * Get orderTotalFee.
     *
     * @return int
     */
    public function getOrderTotalFee()
    {
        return $this->order_total_fee;
    }

    /**
     * Set orderRefundCount.
     *
     * @param int $orderRefundCount
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderRefundCount($orderRefundCount)
    {
        $this->order_refund_count = $orderRefundCount;

        return $this;
    }

    /**
     * Get orderRefundCount.
     *
     * @return int
     */
    public function getOrderRefundCount()
    {
        return $this->order_refund_count;
    }

    /**
     * Set orderRefundTotalFee.
     *
     * @param int $orderRefundTotalFee
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderRefundTotalFee($orderRefundTotalFee)
    {
        $this->order_refund_total_fee = $orderRefundTotalFee;

        return $this;
    }

    /**
     * Get orderRefundTotalFee.
     *
     * @return int
     */
    public function getOrderRefundTotalFee()
    {
        return $this->order_refund_total_fee;
    }

    /**
     * Set orderRefundingCount.
     *
     * @param int $orderRefundingCount
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderRefundingCount($orderRefundingCount)
    {
        $this->order_refunding_count = $orderRefundingCount;

        return $this;
    }

    /**
     * Get orderRefundingCount.
     *
     * @return int
     */
    public function getOrderRefundingCount()
    {
        return $this->order_refunding_count;
    }

    /**
     * Set orderRefundingTotalFee.
     *
     * @param int $orderRefundingTotalFee
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderRefundingTotalFee($orderRefundingTotalFee)
    {
        $this->order_refunding_total_fee = $orderRefundingTotalFee;

        return $this;
    }

    /**
     * Get orderRefundingTotalFee.
     *
     * @return int
     */
    public function getOrderRefundingTotalFee()
    {
        return $this->order_refunding_total_fee;
    }

    /**
     * Set orderProfitSharingCharge.
     *
     * @param int $orderProfitSharingCharge
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderProfitSharingCharge($orderProfitSharingCharge)
    {
        $this->order_profit_sharing_charge = $orderProfitSharingCharge;

        return $this;
    }

    /**
     * Get orderProfitSharingCharge.
     *
     * @return int
     */
    public function getOrderProfitSharingCharge()
    {
        return $this->order_profit_sharing_charge;
    }

    /**
     * Set orderTotalCharge.
     *
     * @param int $orderTotalCharge
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderTotalCharge($orderTotalCharge)
    {
        $this->order_total_charge = $orderTotalCharge;

        return $this;
    }

    /**
     * Get orderTotalCharge.
     *
     * @return int
     */
    public function getOrderTotalCharge()
    {
        return $this->order_total_charge;
    }

    /**
     * Set orderRefundTotalCharge.
     *
     * @param int $orderRefundTotalCharge
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderRefundTotalCharge($orderRefundTotalCharge)
    {
        $this->order_refund_total_charge = $orderRefundTotalCharge;

        return $this;
    }

    /**
     * Get orderRefundTotalCharge.
     *
     * @return int
     */
    public function getOrderRefundTotalCharge()
    {
        return $this->order_refund_total_charge;
    }

    /**
     * Set orderUnProfitSharingTotalCharge.
     *
     * @param int $orderUnProfitSharingTotalCharge
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderUnProfitSharingTotalCharge($orderUnProfitSharingTotalCharge)
    {
        $this->order_un_profit_sharing_total_charge = $orderUnProfitSharingTotalCharge;

        return $this;
    }

    /**
     * Get orderUnProfitSharingTotalCharge.
     *
     * @return int
     */
    public function getOrderUnProfitSharingTotalCharge()
    {
        return $this->order_un_profit_sharing_total_charge;
    }

    /**
     * Set orderUnProfitSharingRefundTotalCharge.
     *
     * @param int $orderUnProfitSharingRefundTotalCharge
     *
     * @return HfpayDistributorTransactionStatistics
     */
    public function setOrderUnProfitSharingRefundTotalCharge($orderUnProfitSharingRefundTotalCharge)
    {
        $this->order_un_profit_sharing_refund_total_charge = $orderUnProfitSharingRefundTotalCharge;

        return $this;
    }

    /**
     * Get orderUnProfitSharingRefundTotalCharge.
     *
     * @return int
     */
    public function getOrderUnProfitSharingRefundTotalCharge()
    {
        return $this->order_un_profit_sharing_refund_total_charge;
    }
}
