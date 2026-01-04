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
 * Rights 权益表
 *
 * @ORM\Table(name="orders_rights", options={"comment":"权益表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\RightsRepository")
 */
class Rights
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="rights_id", type="bigint", options={"comment":"权益ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
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
     * @var boolean
     *
     * @ORM\Column(name="can_reservation", type="boolean", options={"comment":"是否可预约","default":false})
     */
    private $can_reservation = false;

    /**
     * @var string
     *
     * @ORM\Column(name="rights_name", type="string", length=255, options={"comment":"权益标题"})
     */
    private $rights_name;

    /**
     * @var string
     *
     * @ORM\Column(name="rights_subname", nullable=true, type="string", length=255, options={"comment":"权益子标题"})
     */
    private $rights_subname;

    /**
     * @var string
     *
     * @ORM\Column(name="rights_from", nullable=true, type="string", length=255, options={"comment":"权益来源"})
     */
    private $rights_from;

    /**
     * @var string
     *
     * @ORM\Column(name="operator_desc", nullable=true, type="string", length=255, options={"comment":"操作员信息"})
     */
    private $operator_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_num", type="bigint", options={"comment":"服务商品原始总次数,0标示无限制", "default": 0})
     */
    private $total_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_consum_num", type="bigint", options={"default": 0, "comment":"总消耗次数"})
     */
    private $total_consum_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", length=11, options={"comment":"权益开始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", length=11, options={"comment":"权益结束时间"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", nullable=true, length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="label_infos", type="text", nullable=true, options={"comment":"权益的物料信息json结构"})
     */
    private $label_infos;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"权益状态; valid:有效的, expire:过期的; invalid:失效的", "default": "valid"})
     */
    private $status = 'valid';

    /**
     * @var integer
     *
     * @ORM\Column(name="is_not_limit_num", type="integer", options={"comment":"限制核销次数,1:不限制；2:限制", "default": 2})
     */
    private $is_not_limit_num = 2;

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
     * Get rightsId
     *
     * @return integer
     */
    public function getRightsId()
    {
        return $this->rights_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Rights
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
     * Set totalNum
     *
     * @param integer $totalNum
     *
     * @return Rights
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
     * @return Rights
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
     * @return Rights
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
     * @return Rights
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
     * @return Rights
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
     * @return Rights
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
     * @return Rights
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
     * Set rightsName
     *
     * @param string $rightsName
     *
     * @return Rights
     */
    public function setRightsName($rightsName)
    {
        $this->rights_name = $rightsName;

        return $this;
    }

    /**
     * Get rightsName
     *
     * @return string
     */
    public function getRightsName()
    {
        return $this->rights_name;
    }

    /**
     * Set rightsSubname
     *
     * @param string $rightsSubname
     *
     * @return Rights
     */
    public function setRightsSubname($rightsSubname)
    {
        $this->rights_subname = $rightsSubname;

        return $this;
    }

    /**
     * Get rightsSubname
     *
     * @return string
     */
    public function getRightsSubname()
    {
        return $this->rights_subname;
    }

    /**
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return Rights
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
     * Set canReservation
     *
     * @param boolean $canReservation
     *
     * @return Rights
     */
    public function setCanReservation($canReservation)
    {
        $this->can_reservation = $canReservation;

        return $this;
    }

    /**
     * Get canReservation
     *
     * @return boolean
     */
    public function getCanReservation()
    {
        return $this->can_reservation;
    }

    /**
     * Set labelInfos
     *
     * @param string $labelInfos
     *
     * @return Rights
     */
    public function setLabelInfos($labelInfos)
    {
        $this->label_infos = $labelInfos;

        return $this;
    }

    /**
     * Get labelInfos
     *
     * @return string
     */
    public function getLabelInfos()
    {
        return $this->label_infos;
    }

    /**
     * Set rightsFrom
     *
     * @param string $rightsFrom
     *
     * @return Rights
     */
    public function setRightsFrom($rightsFrom)
    {
        $this->rights_from = $rightsFrom;

        return $this;
    }

    /**
     * Get rightsFrom
     *
     * @return string
     */
    public function getRightsFrom()
    {
        return $this->rights_from;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Rights
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
     * Set operatorDesc
     *
     * @param string $operatorDesc
     *
     * @return Rights
     */
    public function setOperatorDesc($operatorDesc)
    {
        $this->operator_desc = $operatorDesc;

        return $this;
    }

    /**
     * Get operatorDesc
     *
     * @return string
     */
    public function getOperatorDesc()
    {
        return $this->operator_desc;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return Rights
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
     * Set isNotLimitNum
     *
     * @param integer $isNotLimitNum
     *
     * @return Rights
     */
    public function setIsNotLimitNum($isNotLimitNum)
    {
        $this->is_not_limit_num = $isNotLimitNum;

        return $this;
    }

    /**
     * Get isNotLimitNum
     *
     * @return integer
     */
    public function getIsNotLimitNum()
    {
        return $this->is_not_limit_num;
    }
}
