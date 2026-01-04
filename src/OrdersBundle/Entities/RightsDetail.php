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
 * RightsDetail 权益详细ID
 *
 * @ORM\Table(name="orders_rights_detail", options={"comment":"权益详细ID"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\RightsDetailRepository")
 */
class RightsDetail
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="rights_id", type="bigint", options={"comment":"权益ID"})
     */
    private $rights_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
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
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", length=64, options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_id", type="bigint", options={"comment":"数值属性ID"})
     */
    private $label_id;

    /**
     * @var string
     *
     * @ORM\Column(name="label_name", type="string", length=255, options={"comment":"数值属性名称"})
     */
    private $label_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_num", type="bigint", options={"comment":"服务商品原始总次数"})
     */
    private $total_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_consum_num", type="bigint", options={"default": 0, "comment":"总消耗次数"})
     */
    private $total_consum_num;

    /**
     * @var string
     *
     * @ORM\Column(name="start_time", type="string", options={"comment":"权益开始时间"})
     */
    private $start_time;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="string", options={"comment":"权益结束时间"})
     */
    private $end_time;

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
     * Set rightsId
     *
     * @param integer $rightsId
     *
     * @return RightsDetail
     */
    public function setRightsId($rightsId)
    {
        $this->rights_id = $rightsId;

        return $this;
    }

    /**
     * Get rightsId
     *
     * @return integer
     */
    public function getRightsId()
    {
        return $this->rights_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return RightsDetail
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RightsDetail
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return RightsDetail
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
     * @return RightsDetail
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
     * Set labelId
     *
     * @param integer $labelId
     *
     * @return RightsDetail
     */
    public function setLabelId($labelId)
    {
        $this->label_id = $labelId;

        return $this;
    }

    /**
     * Get labelId
     *
     * @return integer
     */
    public function getLabelId()
    {
        return $this->label_id;
    }

    /**
     * Set labelName
     *
     * @param string $labelName
     *
     * @return RightsDetail
     */
    public function setLabelName($labelName)
    {
        $this->label_name = $labelName;

        return $this;
    }

    /**
     * Get labelName
     *
     * @return string
     */
    public function getLabelName()
    {
        return $this->label_name;
    }

    /**
     * Set totalNum
     *
     * @param integer $totalNum
     *
     * @return RightsDetail
     */
    public function setTotalNum($totalNum)
    {
        $this->total_num = $totalNum;

        return $this;
    }

    /**
     * Get totalNum
     *
     * @return integer
     */
    public function getTotalNum()
    {
        return $this->total_num;
    }

    /**
     * Set totalConsumNum
     *
     * @param integer $totalConsumNum
     *
     * @return RightsDetail
     */
    public function setTotalConsumNum($totalConsumNum)
    {
        $this->total_consum_num = $totalConsumNum;

        return $this;
    }

    /**
     * Get totalConsumNum
     *
     * @return integer
     */
    public function getTotalConsumNum()
    {
        return $this->total_consum_num;
    }

    /**
     * Set startTime
     *
     * @param string $startTime
     *
     * @return RightsDetail
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return string
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param string $endTime
     *
     * @return RightsDetail
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return string
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return RightsDetail
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
     * @return RightsDetail
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
}
