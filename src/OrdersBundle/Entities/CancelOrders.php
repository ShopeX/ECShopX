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
 * CancelOrders 已付钱订单取消退款记录表
 *
 * @ORM\Table(name="orders_cancel_orders", options={"comment":"已付钱订单取消退款记录表"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CancelOrdersRepository")
 */
class CancelOrders
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="cancel_id", type="bigint", options={"comment":"取消ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $cancel_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商id", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", nullable=true, type="string", options={"comment":"订单类型。可选值有 service 服务业订单;bargain 砍价订单;distribution 分销订单;normal 普通实体订单"})
     */
    private $order_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="bigint", options={"unsigned":true, "comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="progress", type="smallint", options={"default": 0, "comment":"处理进度。可选值有 0 待处理;1 已取消;2 处理中;3 已完成; 4 已驳回"})
     */
    private $progress = '0';

    /**
     * @var string
     *
     *
     * @ORM\Column(name="cancel_from", type="string", options={"default":"buyer", "comment":"取消来源。可选值有 buyer 用户取消订单;shop 商家取消订单"})
     */
    private $cancel_from = 'buyer';

    /**
     * @var string
     *
     *
     * @ORM\Column(name="cancel_reason", type="string", nullable=true, length=300, options={"comment":"取消原因"})
     */
    private $cancel_reason;

    /**
     * @var string
     *
     *
     * @ORM\Column(name="shop_reject_reason", type="string", nullable=true, length=300, options={"comment":"商家拒绝理由"})
     */
    private $shop_reject_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="refund_status", type="string",length=30, options={"comment":"退款状态。可选值有 READY 待审核;AUDIT_SUCCESS 审核成功待退款;SUCCESS 退款成功;SHOP_CHECK_FAILS 商家审核不通过;CANCEL 撤销退款;PROCESSING 已发起退款等待到账;FAILS 退款失败;","default":"READY"})
     */
    private $refund_status = 'READY';

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单更新时间"})
     */
    private $update_time;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_type", type="string", length=5, options={"comment":"货币类型", "default":"CNY"})
     */
    private $fee_type = 'CNY';

    /**
     * @var string
     *
     * @ORM\Column(name="fee_rate", type="float", precision=15, scale=4, options={"comment":"货币汇率", "default":1})
     */
    private $fee_rate = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_symbol", type="string", options={"comment":"货币符号", "default":"￥"})
     */
    private $fee_symbol = '￥';

    /**
     * @var int
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"unsigned":true, "comment":"消费积分", "default": 0})
     */
    private $point = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", nullable=true, type="string", options={ "comment":"支付方式", "default": ""})
     */
    private $pay_type = 0;


    /**
     * Get cancelId
     *
     * @return integer
     */
    public function getCancelId()
    {
        return $this->cancel_id;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return CancelOrders
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return CancelOrders
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return CancelOrders
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return CancelOrders
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return CancelOrders
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
     * Set orderType
     *
     * @param string $orderType
     *
     * @return CancelOrders
     */
    public function setOrderType($orderType)
    {
        $this->order_type = $orderType;

        return $this;
    }

    /**
     * Get orderType
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return CancelOrders
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
     * Set progress
     *
     * @param integer $progress
     *
     * @return CancelOrders
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress
     *
     * @return integer
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set cancelFrom
     *
     * @param string $cancelFrom
     *
     * @return CancelOrders
     */
    public function setCancelFrom($cancelFrom)
    {
        $this->cancel_from = $cancelFrom;

        return $this;
    }

    /**
     * Get cancelFrom
     *
     * @return string
     */
    public function getCancelFrom()
    {
        return $this->cancel_from;
    }

    /**
     * Set cancelReason
     *
     * @param string $cancelReason
     *
     * @return CancelOrders
     */
    public function setCancelReason($cancelReason)
    {
        $this->cancel_reason = $cancelReason;

        return $this;
    }

    /**
     * Get cancelReason
     *
     * @return string
     */
    public function getCancelReason()
    {
        return $this->cancel_reason;
    }

    /**
     * Set shopRejectReason
     *
     * @param string $shopRejectReason
     *
     * @return CancelOrders
     */
    public function setShopRejectReason($shopRejectReason)
    {
        $this->shop_reject_reason = $shopRejectReason;

        return $this;
    }

    /**
     * Get shopRejectReason
     *
     * @return string
     */
    public function getShopRejectReason()
    {
        return $this->shop_reject_reason;
    }

    /**
     * Set refundStatus
     *
     * @param string $refundStatus
     *
     * @return CancelOrders
     */
    public function setRefundStatus($refundStatus)
    {
        $this->refund_status = $refundStatus;

        return $this;
    }

    /**
     * Get refundStatus
     *
     * @return string
     */
    public function getRefundStatus()
    {
        return $this->refund_status;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return CancelOrders
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param integer $updateTime
     *
     * @return CancelOrders
     */
    public function setUpdateTime($updateTime)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set feeType
     *
     * @param string $feeType
     *
     * @return CancelOrders
     */
    public function setFeeType($feeType)
    {
        $this->fee_type = $feeType;

        return $this;
    }

    /**
     * Get feeType
     *
     * @return string
     */
    public function getFeeType()
    {
        return $this->fee_type;
    }

    /**
     * Set feeRate
     *
     * @param float $feeRate
     *
     * @return CancelOrders
     */
    public function setFeeRate($feeRate)
    {
        $this->fee_rate = $feeRate;

        return $this;
    }

    /**
     * Get feeRate
     *
     * @return float
     */
    public function getFeeRate()
    {
        return $this->fee_rate;
    }

    /**
     * Set feeSymbol
     *
     * @param string $feeSymbol
     *
     * @return CancelOrders
     */
    public function setFeeSymbol($feeSymbol)
    {
        $this->fee_symbol = $feeSymbol;

        return $this;
    }

    /**
     * Get feeSymbol
     *
     * @return string
     */
    public function getFeeSymbol()
    {
        return $this->fee_symbol;
    }

    /**
     * Set point
     *
     * @param integer $point
     *
     * @return CancelOrders
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point
     *
     * @return integer
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set payType
     *
     * @param string $payType
     *
     * @return CancelOrders
     */
    public function setPayType($payType)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType
     *
     * @return string
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return CancelOrders
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
}
