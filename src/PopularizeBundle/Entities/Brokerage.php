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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Brokerage 分销佣金表
 *
 * @ORM\Table(name="popularize_brokerage", options={"comment":"分销佣金表"})
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\BrokerageRepository")
 */
class Brokerage
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
     * @var string
     *
     * @ORM\Column(name="brokerage_type", type="string", length=15, options={"comment":"佣金类型"})
     */
    private $brokerage_type;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", nullable=true, length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="buy_user_id", type="bigint")
     */
    private $buy_user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string", length=15, options={"comment":"订单类型"})
     */
    private $order_type;

    /**
     * @var string
     *
     * @ORM\Column(name="source", type="string", length=15, options={"comment":"佣金来源 订单,邀请等"})
     */
    private $source;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"销售金额,单位为‘分’"})
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="commission_type", type="string", length=15, options={"comment":"返佣类型 money 金额 point 积分"})
     */
    private $commission_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate", type="integer", options={"comment":"返佣金额,单位为‘分’"})
     */
    private $rebate;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_point", type="string", length=15, nullable=true, options={"comment":"返佣金额,单位为‘分’", "default":"money"})
     */
    private $rebate_point;

    /**
     * @var string
     *
     * @ORM\Column(name="detail", type="text", options={"comment":"佣金计算详情"})
     */
    private $detail;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_close", type="boolean", options={"default":false, "comment":"是否已结算"})
     */
    private $is_close = false;

    /**
     * @var \DateTime $plan_close_time
     *
     * @ORM\Column(name="plan_close_time", type="integer", options={"comment":"计划结算时间"})
     */
    private $plan_close_time;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // 0x456353686f7058
        return $this->id;
    }

    /**
     * Set brokerageType
     *
     * @param string $brokerageType
     *
     * @return Brokerage
     */
    public function setBrokerageType($brokerageType)
    {
        $this->brokerage_type = $brokerageType;

        return $this;
    }

    /**
     * Get brokerageType
     *
     * @return string
     */
    public function getBrokerageType()
    {
        return $this->brokerage_type;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return Brokerage
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
     * Set source
     *
     * @param string $source
     *
     * @return Brokerage
     */
    public function setSource($source)
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource()
    {
        return $this->source;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Brokerage
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
     * Set price
     *
     * @param integer $price
     *
     * @return Brokerage
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set rebate
     *
     * @param integer $rebate
     *
     * @return Brokerage
     */
    public function setRebate($rebate)
    {
        $this->rebate = $rebate;

        return $this;
    }

    /**
     * Get rebate
     *
     * @return integer
     */
    public function getRebate()
    {
        return $this->rebate;
    }

    /**
     * Set detail
     *
     * @param string $detail
     *
     * @return Brokerage
     */
    public function setDetail($detail)
    {
        $this->detail = $detail;

        return $this;
    }

    /**
     * Get detail
     *
     * @return string
     */
    public function getDetail()
    {
        return $this->detail;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Brokerage
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return Brokerage
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
     * Set isClose
     *
     * @param boolean $isClose
     *
     * @return Brokerage
     */
    public function setIsClose($isClose)
    {
        $this->is_close = $isClose;

        return $this;
    }

    /**
     * Get isClose
     *
     * @return boolean
     */
    public function getIsClose()
    {
        return $this->is_close;
    }

    /**
     * Set planCloseTime
     *
     * @param integer $planCloseTime
     *
     * @return Brokerage
     */
    public function setPlanCloseTime($planCloseTime)
    {
        $this->plan_close_time = $planCloseTime;

        return $this;
    }

    /**
     * Get planCloseTime
     *
     * @return integer
     */
    public function getPlanCloseTime()
    {
        return $this->plan_close_time;
    }

    /**
     * Set orderType
     *
     * @param string $orderType
     *
     * @return Brokerage
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
     * Set buyUserId
     *
     * @param integer $buyUserId
     *
     * @return Brokerage
     */
    public function setBuyUserId($buyUserId)
    {
        $this->buy_user_id = $buyUserId;

        return $this;
    }

    /**
     * Get buyUserId
     *
     * @return integer
     */
    public function getBuyUserId()
    {
        return $this->buy_user_id;
    }

    /**
     * Set commissionType
     *
     * @param string $commissionType
     *
     * @return Brokerage
     */
    public function setCommissionType($commissionType)
    {
        $this->commission_type = $commissionType;

        return $this;
    }

    /**
     * Get commissionType
     *
     * @return string
     */
    public function getCommissionType()
    {
        return $this->commission_type;
    }

    /**
     * Set rebatePoint
     *
     * @param integer $rebatePoint
     *
     * @return Brokerage
     */
    public function setRebatePoint($rebatePoint)
    {
        $this->rebate_point = $rebatePoint;

        return $this;
    }

    /**
     * Get rebatePoint
     *
     * @return integer
     */
    public function getRebatePoint()
    {
        return $this->rebate_point;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return Brokerage
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
}
