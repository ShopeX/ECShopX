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
 * @ORM\Table(name="order_profit_sharing_details", options={"comment":"订单资金分账详情"}, indexes={
 *    @ORM\Index(name="idx_company_id_order_id", columns={"company_id", "order_id"}),
 *    @ORM\Index(name="idx_sharing_id", columns={"sharing_id"}),
 * })
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderProfitSharingDetailsRepository")
 */
class OrderProfitSharingDetails
{
    use Timestamps;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_profit_sharing_detail_id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $order_profit_sharing_detail_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="sharing_id", type="bigint", options={"comment":"分账ID"})
     */
    private $sharing_id;

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
     * @ORM\Column(name="total_fee", type="integer", nullable=true, options={"comment":"分账金额，以分为单位", "default": 0})
     */
    private $total_fee = 0;

    /**
     * Get orderProfitSharingDetailId.
     *
     * @return int
     */
    public function getOrderProfitSharingDetailId()
    {
        return $this->order_profit_sharing_detail_id;
    }

    /**
     * Set sharingId.
     *
     * @param int $sharingId
     *
     * @return OrderProfitSharingDetails
     */
    public function setSharingId($sharingId)
    {
        $this->sharing_id = $sharingId;

        return $this;
    }

    /**
     * Get sharingId.
     *
     * @return int
     */
    public function getSharingId()
    {
        return $this->sharing_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrderProfitSharingDetails
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
     * @return OrderProfitSharingDetails
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
     * @return OrderProfitSharingDetails
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
     * Set channelId.
     *
     * @param string|null $channelId
     *
     * @return OrderProfitSharingDetails
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
     * Set channelAcctId.
     *
     * @param string|null $channelAcctId
     *
     * @return OrderProfitSharingDetails
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
     * Set totalFee.
     *
     * @param int|null $totalFee
     *
     * @return OrderProfitSharingDetails
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
}
