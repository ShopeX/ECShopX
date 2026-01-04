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
 * TaskBrokerageCount 任务制返佣
 *
 * @ORM\Table(name="popularize_task_brokerage_count", options={"comment":"任务制返佣"})
 * @ORM\Entity(repositoryClass="PopularizeBundle\Repositories\TaskBrokerageCountRepository")
 */
class TaskBrokerageCount
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
     * @ORM\Column(name="rebate_type", type="string", length=15, options={"comment":"返佣模式"})
     */
    private $rebate_type;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_bn", type="string", options={"comment":"商品编号"})
     */
    private $item_bn;

    /**
     * @var string
     *
     * @ORM\Column(name="total_fee", type="bigint", options={"comment":"已完成的总销售额"})
     */
    private $total_fee;

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
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint")
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_conf", nullable=true, type="json_array", options={"comment":"分销配置"})
     */
    private $rebate_conf;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate_money", type="bigint", options={"comment":"分销奖金", "default":0})
     */
    private $rebate_money;

    /**
     * @var integer
     *
     * @ORM\Column(name="finish_num", type="string", options={"comment":"订单已完成数量"})
     */
    private $finish_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="wait_num", type="string", options={"comment":"订单已支付，待完成数量"})
     */
    private $wait_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="close_num", type="string", options={"comment":"订单已关闭数量，包含取消订单，售后订单"})
     */
    private $close_num;

    /**
     * @var \DateTime $plan_date
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
        // ShopEx EcShopX Service Component
        return $this->id;
    }

    /**
     * Set rebateType
     *
     * @param string $rebateType
     *
     * @return TaskBrokerage
     */
    public function setRebateType($rebateType)
    {
        $this->rebate_type = $rebateType;

        return $this;
    }

    /**
     * Get rebateType
     *
     * @return string
     */
    public function getRebateType()
    {
        return $this->rebate_type;
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
        // Ref: 1996368445
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
        // Ref: 1996368445
        return $this->item_id;
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
     * Set rebateConf
     *
     * @param array $rebateConf
     *
     * @return TaskBrokerage
     */
    public function setRebateConf($rebateConf)
    {
        $this->rebate_conf = $rebateConf;

        return $this;
    }

    /**
     * Get rebateConf
     *
     * @return array
     */
    public function getRebateConf()
    {
        return $this->rebate_conf;
    }

    /**
     * Set finishNum
     *
     * @param string $finishNum
     *
     * @return TaskBrokerage
     */
    public function setFinishNum($finishNum)
    {
        $this->finish_num = $finishNum;

        return $this;
    }

    /**
     * Get finishNum
     *
     * @return string
     */
    public function getFinishNum()
    {
        return $this->finish_num;
    }

    /**
     * Set waitNum
     *
     * @param string $waitNum
     *
     * @return TaskBrokerage
     */
    public function setWaitNum($waitNum)
    {
        $this->wait_num = $waitNum;

        return $this;
    }

    /**
     * Get waitNum
     *
     * @return string
     */
    public function getWaitNum()
    {
        return $this->wait_num;
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return TaskBrokerageCount
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return TaskBrokerageCount
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
     * @return TaskBrokerageCount
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
     * Set closeNum
     *
     * @param string $closeNum
     *
     * @return TaskBrokerageCount
     */
    public function setCloseNum($closeNum)
    {
        $this->close_num = $closeNum;

        return $this;
    }

    /**
     * Get closeNum
     *
     * @return string
     */
    public function getCloseNum()
    {
        return $this->close_num;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return TaskBrokerageCount
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
     * Set rebateMoney
     *
     * @param integer $rebateMoney
     *
     * @return TaskBrokerageCount
     */
    public function setRebateMoney($rebateMoney)
    {
        $this->rebate_money = $rebateMoney;

        return $this;
    }

    /**
     * Get rebateMoney
     *
     * @return integer
     */
    public function getRebateMoney()
    {
        return $this->rebate_money;
    }

    /**
     * Set itemBn
     *
     * @param integer $itemBn
     *
     * @return TaskBrokerageCount
     */
    public function setItemBn($itemBn)
    {
        $this->item_bn = $itemBn;

        return $this;
    }

    /**
     * Get itemBn
     *
     * @return integer
     */
    public function getItemBn()
    {
        return $this->item_bn;
    }
}
