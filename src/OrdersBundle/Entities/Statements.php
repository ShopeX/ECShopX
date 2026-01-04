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

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Statements 结算单
 *
 * @ORM\Table(name="statements", options={"comment":"结算单"},
 *     indexes={
 *         @ORM\Index(name="idx_merchant_type", columns={"merchant_type"}),
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="idx_start_time", columns={"start_time"}),
 *     })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\StatementsRepository")
 */
class Statements
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
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id"})
     */
    private $merchant_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商ID", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * distributor 经销商
     * supplier 供应商
     *
     * @ORM\Column(name="merchant_type", type="string", nullable=true, length=30, options={"comment":"商户类型：distributor 经销商,supplier 供应商", "default": "distributor"})
     */
    private $merchant_type = 'distributor';

    /**
     * @var string
     *
     * @ORM\Column(name="statement_no", type="string", length=20, options={"comment":"结算单号"})
     */
    private $statement_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_num", type="integer", options={"unsigned":true, "comment":"订单数量"})
     */
    private $order_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="integer", options={"comment":"实付金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_fee", type="integer", options={"comment":"运费金额，以分为单位"})
     */
    private $freight_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="intra_city_freight_fee", type="integer", options={"comment":"同城配金额，以分为单位"})
     */
    private $intra_city_freight_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_fee", type="integer", options={"comment":"分销佣金，以分为单位"})
     */
    private $rebate_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_fee", type="integer", options={"comment":"退款金额，以分为单位"})
     */
    private $refund_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="statement_fee", type="integer", options={"comment":"结算金额，以分为单位"})
     */
    private $statement_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"结算周期开始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"结算周期结束时间"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="confirm_time", type="integer", nullable=true, options={"comment":"确认时间"})
     */
    private $confirm_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="statement_time", type="integer", nullable=true, options={"comment":"结算时间"})
     */
    private $statement_time;

    /**
     * @var string
     *
     * @ORM\Column(name="statement_status", type="string", options={"default":"ready", "comment":"结算状态 ready:待商家确认 confirmed待平台结算 done:已结算"})
     */
    private $statement_status = 'ready';

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

     /**
     * @var integer
     *
     * @ORM\Column(name="point_fee", type="integer", nullable=true, options={"comment":"积分抵扣 按分计算"})
     */
    private $point_fee = 0;

     /**
     * @var integer
     *
     * @ORM\Column(name="refund_num", type="integer", nullable=true, options={"comment":"退货数量"})
     */
    private $refund_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_point", type="integer", nullable=true, options={"comment":"退款积分"})
     */
    private $refund_point = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_cost_fee", type="integer", nullable=true, options={"comment":"退货成本"})
     */
    private $refund_cost_fee = 0;

    
    /**
     * Set pointFee.
     *
     * @param int|null $pointFee
     *
     * @return Statements
     */
    public function setPointFee($pointFee = null)
    {
        $this->point_fee = $pointFee;

        return $this;
    }

    /**
     * Get pointFee.
     *
     * @return int|null
     */
    public function getPointFee()
    {
        return $this->point_fee;
    }

    /**
     * Set refundNum.
     *
     * @param int|null $refundNum
     *
     * @return Statements
     */
    public function setRefundNum($refundNum = null)
    {
        $this->refund_num = $refundNum;

        return $this;
    }

    /**
     * Get refundNum.
     *
     * @return int|null
     */
    public function getRefundNum()
    {
        return $this->refund_num;
    }

    /**
     * Set refundPoint.
     *
     * @param int|null $refundPoint
     *
     * @return Statements
     */
    public function setRefundPoint($refundPoint = null)
    {
        $this->refund_point = $refundPoint;

        return $this;
    }

    /**
     * Get refundPoint.
     *
     * @return int|null
     */
    public function getRefundPoint()
    {
        return $this->refund_point;
    }

    /**
     * Set refundCostFee.
     *
     * @param int|null $refundCostFee
     *
     * @return Statements
     */
    public function setRefundCostFee($refundCostFee = null)
    {
        $this->refund_cost_fee = $refundCostFee;

        return $this;
    }

    /**
     * Get refundCostFee.
     *
     * @return int|null
     */
    public function getRefundCostFee()
    {
        return $this->refund_cost_fee;
    }
    
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
     * @return Statements
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
     * Set merchantId
     *
     * @param integer $merchantId
     *
     * @return Statements
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId
     *
     * @return integer
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return Statements
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set statementNo
     *
     * @param string $statementNo
     *
     * @return Statements
     */
    public function setStatementNo($statementNo)
    {
        $this->statement_no = $statementNo;

        return $this;
    }

    /**
     * Get statementNo
     *
     * @return string
     */
    public function getStatementNo()
    {
        return $this->statement_no;
    }

    /**
     * Set orderNum
     *
     * @param integer $orderNum
     *
     * @return Statements
     */
    public function setOrderNum($orderNum)
    {
        $this->order_num = $orderNum;

        return $this;
    }

    /**
     * Get orderNum
     *
     * @return integer
     */
    public function getOrderNum()
    {
        return $this->order_num;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return Statements
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee
     *
     * @return integer
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set freightFee
     *
     * @param integer $freightFee
     *
     * @return Statements
     */
    public function setFreightFee($freightFee)
    {
        $this->freight_fee = $freightFee;

        return $this;
    }

    /**
     * Get freightFee
     *
     * @return integer
     */
    public function getFreightFee()
    {
        return $this->freight_fee;
    }

    /**
     * Set intraCityFreightFee
     *
     * @param integer $intraCityFreightFee
     *
     * @return Statements
     */
    public function setIntraCityFreightFee($intraCityFreightFee)
    {
        $this->intra_city_freight_fee = $intraCityFreightFee;

        return $this;
    }

    /**
     * Get intraCityFreightFee
     *
     * @return integer
     */
    public function getIntraCityFreightFee()
    {
        return $this->intra_city_freight_fee;
    }

    /**
     * Set rebateFee
     *
     * @param integer $rebateFee
     *
     * @return Statements
     */
    public function setRebateFee($rebateFee)
    {
        $this->rebate_fee = $rebateFee;

        return $this;
    }

    /**
     * Get rebateFee
     *
     * @return integer
     */
    public function getRebateFee()
    {
        return $this->rebate_fee;
    }

    /**
     * Set refundFee
     *
     * @param integer $refundFee
     *
     * @return Statements
     */
    public function setRefundFee($refundFee)
    {
        $this->refund_fee = $refundFee;

        return $this;
    }

    /**
     * Get refundFee
     *
     * @return integer
     */
    public function getRefundFee()
    {
        return $this->refund_fee;
    }

    /**
     * Set statementFee
     *
     * @param integer $statementFee
     *
     * @return Statements
     */
    public function setStatementFee($statementFee)
    {
        $this->statement_fee = $statementFee;

        return $this;
    }

    /**
     * Get statementFee
     *
     * @return integer
     */
    public function getStatementFee()
    {
        return $this->statement_fee;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return Statements
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return Statements
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set confirmTime
     *
     * @param integer $confirmTime
     *
     * @return Statements
     */
    public function setConfirmTime($confirmTime)
    {
        $this->confirm_time = $confirmTime;

        return $this;
    }

    /**
     * Get confirmTime
     *
     * @return integer
     */
    public function getConfirmTime()
    {
        return $this->confirm_time;
    }

    /**
     * Set statementTime
     *
     * @param integer $statementTime
     *
     * @return Statements
     */
    public function setStatementTime($statementTime)
    {
        $this->statement_time = $statementTime;

        return $this;
    }

    /**
     * Get statementTime
     *
     * @return integer
     */
    public function getStatementTime()
    {
        return $this->statement_time;
    }

    /**
     * Set statementStatus
     *
     * @param string $statementStatus
     *
     * @return Statements
     */
    public function setStatementStatus($statementStatus)
    {
        $this->statement_status = $statementStatus;

        return $this;
    }

    /**
     * Get statementStatus
     *
     * @return string
     */
    public function getStatementStatus()
    {
        return $this->statement_status;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Statements
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return Statements
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return Statements
     */
    public function setSupplierId($supplierId)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set merchantType.
     *
     * @param string|null $merchantType
     *
     * @return Statements
     */
    public function setMerchantType($merchantType = null)
    {
        $this->merchant_type = $merchantType;

        return $this;
    }

    /**
     * Get merchantType.
     *
     * @return string|null
     */
    public function getMerchantType()
    {
        return $this->merchant_type;
    }
}
