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
 * TaskBrokerage 任务制返佣
 *
 * @ORM\Table(name="popularize_task_brokerage", options={"comment":"任务制返佣"})
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\TaskBrokerageRepository")
 */
class TaskBrokerage
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
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", nullable=true, length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="buy_user_id", type="bigint")
     */
    private $buy_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", type="text", nullable=true, options={"comment":"商品规格描述"})
     */
    private $item_spec_desc;

    /**
     * @var $price
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"价格"})
     */
    private $price;

    /**
     * @var $price
     *
     * @ORM\Column(name="num", type="integer", options={"comment":"销售数量"})
     */
    private $num;

    /**
     * @var $status
     *
     * @ORM\Column(name="status", type="string", options={"comment":"状态"})
     */
    private $status;

    /**
     * @var $plan_date
     *
     * @ORM\Column(name="plan_date", type="string", options={"comment":"计划结算时间"})
     */
    private $plan_date;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return TaskBrokerage
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return TaskBrokerage
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
     * Set orderId
     *
     * @param string $orderId
     *
     * @return TaskBrokerage
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set buyUserId
     *
     * @param integer $buyUserId
     *
     * @return TaskBrokerage
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TaskBrokerage
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
     * Set status
     *
     * @param string $status
     *
     * @return TaskBrokerage
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set planDate
     *
     * @param string $planDate
     *
     * @return TaskBrokerage
     */
    public function setPlanDate($planDate)
    {
        $this->plan_date = $planDate;

        return $this;
    }

    /**
     * Get planDate
     *
     * @return string
     */
    public function getPlanDate()
    {
        return $this->plan_date;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return TaskBrokerage
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
     * @return TaskBrokerage
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
     * Set price
     *
     * @param integer $price
     *
     * @return TaskBrokerage
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return TaskBrokerage
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
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return TaskBrokerage
     */
    public function setItemSpecDesc($itemSpecDesc)
    {
        $this->item_spec_desc = $itemSpecDesc;

        return $this;
    }

    /**
     * Get itemSpecDesc
     *
     * @return string
     */
    public function getItemSpecDesc()
    {
        return $this->item_spec_desc;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return TaskBrokerage
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
}
