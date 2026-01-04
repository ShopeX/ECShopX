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
 * StatementDetails 结算单明细
 *
 * @ORM\Table(name="statement_details", options={"comment":"结算单明细"},
 *     indexes={
 *         @ORM\Index(name="idx_statement_id", columns={"statement_id"}),
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *         @ORM\Index(name="idx_created", columns={"created"}),
 *     })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\StatementDetailsRepository")
 */
class StatementDetails
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
     * @var integer
     *
     * @ORM\Column(name="statement_id", type="bigint", options={"comment":"结算单ID"})
     */
    private $statement_id;

    /**
     * @var string
     *
     * @ORM\Column(name="statement_no", type="string", length=20, options={"comment":"结算单号"})
     */
    private $statement_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

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
     * @var string
     *
     * @ORM\Column(name="pay_type", type="string", options={ "comment":"支付方式"})
     */
    private $pay_type;

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
     * @ORM\Column(name="num", type="integer", nullable=true, options={"comment":"购买数量"})
     */
    private $num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_fee", type="integer", nullable=true, options={"comment":"销售总金额，以分为单位"})
     */
    private $item_fee = 0;

     /**
     * @var integer
     *
     * @ORM\Column(name="commission_fee", type="integer", nullable=true, options={"comment":"佣金金额，以分为单位"})
     */
    private $commission_fee = 0;
    
    /**
     * @var integer
     *
     * @ORM\Column(name="cost_fee", type="integer", nullable=true, options={"comment":"结算金额，以分为单位"})
     */
    private $cost_fee = 0;

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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * Set statementId
     *
     * @param string $statementId
     *
     * @return StatementDetails
     */
    public function setStatementId($statementId)
    {
        $this->statement_id = $statementId;

        return $this;
    }

    /**
     * Get statementId
     *
     * @return string
     */
    public function getStatementId()
    {
        return $this->statement_id;
    }

    /**
     * Set statementNo
     *
     * @param string $statementNo
     *
     * @return StatementDetails
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
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return StatementDetails
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return StatementDetails
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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * Set payType
     *
     * @param integer $payType
     *
     * @return StatementDetails
     */
    public function setPayType($payType)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType
     *
     * @return integer
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return StatementDetails
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
     * @return StatementDetails
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
     * @return StatementDetails
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
     * Set num
     *
     * @param integer $num
     *
     * @return StatementDetails
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set itemFee
     *
     * @param integer $itemFee
     *
     * @return StatementDetails
     */
    public function setItemFee($itemFee)
    {
        $this->item_fee = $itemFee;

        return $this;
    }

    /**
     * Get itemFee
     *
     * @return integer
     */
    public function getItemFee()
    {
        return $this->item_fee;
    }

    /**
     * Set commissionFee
     *
     * @param integer $commissionFee
     *
     * @return StatementDetails
     */
    public function setCommissionFee($commissionFee)
    {
        $this->commission_fee = $commissionFee;

        return $this;
    }

    /**
     * Get commissionFee
     *
     * @return integer
     */
    public function getCommissionFee()
    {
        return $this->commission_fee;
    }

    /**
     * Set costFee
     *
     * @param integer $costFee
     *
     * @return StatementDetails
     */
    public function setCostFee($costFee)
    {
        $this->cost_fee = $costFee;

        return $this;
    }

    /**
     * Get costFee
     *
     * @return integer
     */
    public function getCostFee()
    {
        return $this->cost_fee;
    }

    /**
     * Set pointFee
     *
     * @param integer $pointFee
     *
     * @return StatementDetails
     */
    public function setPointFee($pointFee)
    {
        $this->point_fee = $pointFee;

        return $this;
    }

    /**
     * Get pointFee
     *
     * @return integer
     */
    public function getPointFee()
    {
        return $this->point_fee;
    }

    /**
     * Set refundNum
     *
     * @param integer $refundNum
     *
     * @return StatementDetails
     */
    public function setRefundNum($refundNum)
    {
        $this->refund_num = $refundNum;

        return $this;
    }

    /**
     * Get refundNum
     *
     * @return integer
     */
    public function getRefundNum()
    {
        return $this->refund_num;
    }

    /**
     * Set refundPoint
     *
     * @param integer $refundPoint
     *
     * @return StatementDetails
     */
    public function setRefundPoint($refundPoint)
    {
        $this->refund_point = $refundPoint;

        return $this;
    }

    /**
     * Get refundPoint
     *
     * @return integer
     */
    public function getRefundPoint()
    {
        return $this->refund_point;
    }

    /**
     * Set refundCostFee
     *
     * @param integer $refundCostFee
     *
     * @return StatementDetails
     */
    public function setRefundCostFee($refundCostFee)
    {
        $this->refund_cost_fee = $refundCostFee;

        return $this;
    }

    /**
     * Get refundCostFee
     *
     * @return integer
     */
    public function getRefundCostFee()
    {
        return $this->refund_cost_fee;
    }
}