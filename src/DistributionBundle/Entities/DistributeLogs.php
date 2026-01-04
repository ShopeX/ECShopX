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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Doctrine\ORM\Mapping\UniqueConstraint;

/**
 * DistributeLogs 分销商佣金记录表
 *
 * @ORM\Table(name="distribution_distribute_logs", options={"comment"="分销商佣金记录表"}, uniqueConstraints={@UniqueConstraint(name="rebatelog_idx", columns={"order_id", "distributor_id", "item_id"})})
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributeLogsRepository")
 */
class DistributeLogs
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
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"分销商id"})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_mobile", type="string", length=32)
     */
    private $distributor_mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=32, nullable=true, options={"comment":"分销商手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="pic", type="string", nullable=true,  options={"comment":"商品图片"})
     */
    private $pic;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", nullable=false, options={"unsigned":true, "comment":"购买商品数量"})
     */
    private $num;

    /**
     * @var integer
     *
     * 分销单价
     *
     * @ORM\Column(name="rebate", type="integer", nullable=false, options={"unsigned":true, "default":0, "comment":"单个分销金额，以分为单位"})
     */
    private $rebate;

    /**
     * @var integer
     *
     * 分销总金额
     *
     * @ORM\Column(name="total_rebate", type="integer", nullable=false, options={"unsigned":true, "default":0, "comment":"总分销金额，以分为单位"})
     */
    private $total_rebate;

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
     * @ORM\Column(type="integer", options={"comment":"订单更新时间"})
     */
    private $update_time;

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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return DistributeLogs
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
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return DistributeLogs
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return DistributeLogs
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return DistributeLogs
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return DistributeLogs
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
     * @return DistributeLogs
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
     * Set pic
     *
     * @param string $pic
     *
     * @return DistributeLogs
     */
    public function setPic($pic)
    {
        $this->pic = $pic;

        return $this;
    }

    /**
     * Get pic
     *
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return DistributeLogs
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
     * Set rebate
     *
     * @param integer $rebate
     *
     * @return DistributeLogs
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
     * Set totalRebate
     *
     * @param integer $totalRebate
     *
     * @return DistributeLogs
     */
    public function setTotalRebate($totalRebate)
    {
        $this->total_rebate = $totalRebate;

        return $this;
    }

    /**
     * Get totalRebate
     *
     * @return integer
     */
    public function getTotalRebate()
    {
        return $this->total_rebate;
    }

    /**
     * Set isClose
     *
     * @param boolean $isClose
     *
     * @return DistributeLogs
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
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return DistributeLogs
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
     * @return DistributeLogs
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
     * Set mobile
     *
     * @param string $mobile
     *
     * @return DistributeLogs
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set planCloseTime
     *
     * @param integer $planCloseTime
     *
     * @return DistributeLogs
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
     * Set distributorMobile
     *
     * @param string $distributorMobile
     *
     * @return DistributeLogs
     */
    public function setDistributorMobile($distributorMobile)
    {
        $this->distributor_mobile = $distributorMobile;

        return $this;
    }

    /**
     * Get distributorMobile
     *
     * @return string
     */
    public function getDistributorMobile()
    {
        return $this->distributor_mobile;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return DistributeLogs
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
}
