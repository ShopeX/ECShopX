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
use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * OrderProfitSharing  订单资金分账
 *
 * @ORM\Table(name="order_profit_sharing", options={"comment":"订单资金分账"}, indexes={
 *    @ORM\Index(name="idx_company_id_order_id", columns={"company_id", "order_id"}),
 * })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderProfitSharingRepository")
 */
class OrderProfitSharing
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_profit_sharing_id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $order_profit_sharing_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", nullable=true, type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", nullable=true, type="bigint", options={"unsigned":true, "default":0, "comment":"用户id"})
     */
    private $user_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_type", nullable=true, type="string", options={ "comment":"支付方式", "default": ""})
     */
    private $pay_type;

    /**
     * @var string
     *
     * @ORM\Column(name="channel_id", nullable=true, type="string", options={ "comment":"渠道分账账户id", "default": ""})
     */
    private $channel_id;

    /**
     * @var string
     *
     * @ORM\Column(name="channel_acct_id", nullable=true, type="string", options={ "comment":"渠道分账账户号", "default": ""})
     */
    private $channel_acct_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="integer", nullable=true, options={"comment":"分账金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", nullable=true, type="integer", options={"comment":"订单状态。可选值有 0 1 成功 2失败", "default": 0})
     */
    private $status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_id", type="string", nullable=true, options={"comment":"汇付接口请求id"})
     */
    private $hf_order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hf_order_date", type="string", nullable=true, options={"comment":"汇付接口请求日期"})
     */
    private $hf_order_date;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_code", type="string", nullable=true, options={"comment":"汇付接口请求响应码"})
     */
    private $resp_code;

    /**
     * @var string
     *
     * @ORM\Column(name="resp_desc", type="string", nullable=true, options={"comment":"汇付接口请求描述"})
     */
    private $resp_desc;

    /**
     * Get orderProfitSharingId.
     *
     * @return int
     */
    public function getOrderProfitSharingId()
    {
        return $this->order_profit_sharing_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrderProfitSharing
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
     * @param int $orderId
     *
     * @return OrderProfitSharing
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set distributorId.
     *
     * @param int|null $distributorId
     *
     * @return OrderProfitSharing
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return OrderProfitSharing
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set payType.
     *
     * @param string|null $payType
     *
     * @return OrderProfitSharing
     */
    public function setPayType($payType = null)
    {
        $this->pay_type = $payType;

        return $this;
    }

    /**
     * Get payType.
     *
     * @return string|null
     */
    public function getPayType()
    {
        return $this->pay_type;
    }

    /**
     * Set channelId.
     *
     * @param string|null $channelId
     *
     * @return OrderProfitSharing
     */
    public function setChannelId($channelId = null)
    {
        $this->channel_id = $channelId;

        return $this;
    }

    /**
     * Get channelId.
     *
     * @return string|null
     */
    public function getChannelId()
    {
        return $this->channel_id;
    }

    /**
     * Set totalFee.
     *
     * @param int $totalFee
     *
     * @return OrderProfitSharing
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee.
     *
     * @return int
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return OrderProfitSharing
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set channelAcctId.
     *
     * @param string|null $channelAcctId
     *
     * @return OrderProfitSharing
     */
    public function setChannelAcctId($channelAcctId = null)
    {
        $this->channel_acct_id = $channelAcctId;

        return $this;
    }

    /**
     * Get channelAcctId.
     *
     * @return string|null
     */
    public function getChannelAcctId()
    {
        return $this->channel_acct_id;
    }

    /**
     * Set hfOrderId.
     *
     * @param string|null $hfOrderId
     *
     * @return OrderProfitSharing
     */
    public function setHfOrderId($hfOrderId = null)
    {
        $this->hf_order_id = $hfOrderId;

        return $this;
    }

    /**
     * Get hfOrderId.
     *
     * @return string|null
     */
    public function getHfOrderId()
    {
        return $this->hf_order_id;
    }

    /**
     * Set hfOrderDate.
     *
     * @param string|null $hfOrderDate
     *
     * @return OrderProfitSharing
     */
    public function setHfOrderDate($hfOrderDate = null)
    {
        $this->hf_order_date = $hfOrderDate;

        return $this;
    }

    /**
     * Get hfOrderDate.
     *
     * @return string|null
     */
    public function getHfOrderDate()
    {
        return $this->hf_order_date;
    }

    /**
     * Set respCode.
     *
     * @param string|null $respCode
     *
     * @return OrderProfitSharing
     */
    public function setRespCode($respCode = null)
    {
        $this->resp_code = $respCode;

        return $this;
    }

    /**
     * Get respCode.
     *
     * @return string|null
     */
    public function getRespCode()
    {
        return $this->resp_code;
    }

    /**
     * Set respDesc.
     *
     * @param string|null $respDesc
     *
     * @return OrderProfitSharing
     */
    public function setRespDesc($respDesc = null)
    {
        $this->resp_desc = $respDesc;

        return $this;
    }

    /**
     * Get respDesc.
     *
     * @return string|null
     */
    public function getRespDesc()
    {
        return $this->resp_desc;
    }
}
