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

namespace KaquanBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * VipGradeOrder(付费会员等级卡)
 *
 * @ORM\Table(name="kaquan_vip_grade_order", options={"comment":"付费会员等级卡"})
 * @ORM\Entity(repositoryClass="KaquanBundle\Repositories\VipGradeOrderRepository")
 */
class VipGradeOrder
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="vip_grade_id", type="integer", options={"comment":"付费会员卡等级ID"})
     */
    private $vip_grade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="lv_type", type="string", options={"comment":"等级类型"})
     */
    private $lv_type;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="integer", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="mobile", type="string", nullable=true, options={"comment":"用户手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="title", nullable=true, type="string", options={"comment":"会员卡标题"})
     */
    private $title;

    /**
     * @var int
     *
     * @ORM\Column(name="price", type="integer", options={"unsigned":true, "comment":"付款金额"})
     */
    private $price;

    /**
     * @var json_array
     *
     * @ORM\Column(name="card_type", type="json_array", options={"comment":"购买的卡片类型,可选值有  monthly:30天月度卡;quarter:30天季度卡;year:365天年度卡"})
     */
    private $card_type;

    /**
     * @var string
     *
     * @ORM\Column(name="discount", type="integer", options={"comment":"折扣额度","default":0})
     */
    private $discount = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", options={"unsigned":true, "comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"订单来源id"})
     */
    private $source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="monitor_id", type="bigint", nullable=true, options={"comment":"订单监控页面id"})
     */
    private $monitor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_status", type="string", nullable=true, options={"comment":"订单状态,可选值有 DONE—订单完成;NOTPAY—未支付;CANCEL—已取消"})
     */
    private $order_status;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

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
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", options={"comment":"订单来源,可选值有 sale:购买;receive:领取;admin:后台手动续期;gift:赠送", "default":"sale"})
     */
    private $source_type = 'sale';

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return VipGradeOrder
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
     * Set vipGradeId
     *
     * @param integer $vipGradeId
     *
     * @return VipGradeOrder
     */
    public function setVipGradeId($vipGradeId)
    {
        $this->vip_grade_id = $vipGradeId;

        return $this;
    }

    /**
     * Get vipGradeId
     *
     * @return integer
     */
    public function getVipGradeId()
    {
        return $this->vip_grade_id;
    }

    /**
     * Set lvType
     *
     * @param string $lvType
     *
     * @return VipGradeOrder
     */
    public function setLvType($lvType)
    {
        // IDX: 2367340174
        $this->lv_type = $lvType;

        return $this;
    }

    /**
     * Get lvType
     *
     * @return string
     */
    public function getLvType()
    {
        return $this->lv_type;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return VipGradeOrder
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return VipGradeOrder
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
     * Set mobile
     *
     * @param string $mobile
     *
     * @return VipGradeOrder
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set title
     *
     * @param string $title
     *
     * @return VipGradeOrder
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return VipGradeOrder
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
     * Set cardType
     *
     * @param array $cardType
     *
     * @return VipGradeOrder
     */
    public function setCardType($cardType)
    {
        $this->card_type = $cardType;

        return $this;
    }

    /**
     * Get cardType
     *
     * @return array
     */
    public function getCardType()
    {
        return $this->card_type;
    }

    /**
     * Set discount
     *
     * @param integer $discount
     *
     * @return VipGradeOrder
     */
    public function setDiscount($discount)
    {
        $this->discount = $discount;

        return $this;
    }

    /**
     * Get discount
     *
     * @return integer
     */
    public function getDiscount()
    {
        return $this->discount;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return VipGradeOrder
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return VipGradeOrder
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
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return VipGradeOrder
     */
    public function setSourceId($sourceId)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set monitorId
     *
     * @param integer $monitorId
     *
     * @return VipGradeOrder
     */
    public function setMonitorId($monitorId)
    {
        $this->monitor_id = $monitorId;

        return $this;
    }

    /**
     * Get monitorId
     *
     * @return integer
     */
    public function getMonitorId()
    {
        return $this->monitor_id;
    }

    /**
     * Set orderStatus
     *
     * @param string $orderStatus
     *
     * @return VipGradeOrder
     */
    public function setOrderStatus($orderStatus)
    {
        $this->order_status = $orderStatus;

        return $this;
    }

    /**
     * Get orderStatus
     *
     * @return string
     */
    public function getOrderStatus()
    {
        return $this->order_status;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return VipGradeOrder
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
     * Set updated
     *
     * @param integer $updated
     *
     * @return VipGradeOrder
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set feeType
     *
     * @param string $feeType
     *
     * @return VipGradeOrder
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
     * @return VipGradeOrder
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
     * @return VipGradeOrder
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
     * Set sourceType
     *
     * @param string $sourceType
     *
     * @return VipGradeOrder
     */
    public function setSourceType($sourceType)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType
     *
     * @return string
     */
    public function getSourceType()
    {
        return $this->source_type;
    }
}
