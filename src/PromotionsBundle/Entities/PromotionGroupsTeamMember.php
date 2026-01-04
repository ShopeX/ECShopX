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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PromotionGroupsTeamMember 参团表
 *
 * @ORM\Table(name="promotion_groups_team_member", options={"comment":"参团表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PromotionGroupsTeamMemberRepository")
 */
class PromotionGroupsTeamMember
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="team_id", type="string", length=100, options={"comment":"团id号"})
     */
    private $team_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_id", type="bigint", options={"comment":"活动ID号"})
     */
    private $act_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="member_id", type="bigint", nullable=true, options={"comment":"会员ID, 0为拼团机器人"})
     */
    private $member_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="join_time", type="bigint", options={"comment":"参团时间"})
     */
    private $join_time;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", nullable=true, length=100, options={"comment":"订单编号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="group_goods_type", type="string", length=255, options={"comment":"团购活动商品类型", "default":"services"})
     */
    private $group_goods_type = 'services';

    /**
     * @var string
     *
     * @ORM\Column(name="member_info", type="string", nullable=true, length=255, options={"comment":"拼团机器人"})
     */
    private $member_info;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", nullable=true, options={"comment":"是否禁用 true=禁用,false=启用", "default": false})
     */
    private $disabled;

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
     * Set teamId
     *
     * @param string $teamId
     *
     * @return PromotionGroupsTeamMember
     */
    public function setTeamId($teamId)
    {
        $this->team_id = $teamId;

        return $this;
    }

    /**
     * Get teamId
     *
     * @return string
     */
    public function getTeamId()
    {
        return $this->team_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PromotionGroupsTeamMember
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
     * Set actId
     *
     * @param integer $actId
     *
     * @return PromotionGroupsTeamMember
     */
    public function setActId($actId)
    {
        $this->act_id = $actId;

        return $this;
    }

    /**
     * Get actId
     *
     * @return integer
     */
    public function getActId()
    {
        return $this->act_id;
    }

    /**
     * Set memberId
     *
     * @param integer $memberId
     *
     * @return PromotionGroupsTeamMember
     */
    public function setMemberId($memberId)
    {
        $this->member_id = $memberId;

        return $this;
    }

    /**
     * Get memberId
     *
     * @return integer
     */
    public function getMemberId()
    {
        return $this->member_id;
    }

    /**
     * Set joinTime
     *
     * @param integer $joinTime
     *
     * @return PromotionGroupsTeamMember
     */
    public function setJoinTime($joinTime)
    {
        $this->join_time = $joinTime;

        return $this;
    }

    /**
     * Get joinTime
     *
     * @return integer
     */
    public function getJoinTime()
    {
        return $this->join_time;
    }

    /**
     * Set orderId
     *
     * @param string $orderId
     *
     * @return PromotionGroupsTeamMember
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
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return PromotionGroupsTeamMember
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set memberInfo
     *
     * @param string $memberInfo
     *
     * @return PromotionGroupsTeamMember
     */
    public function setMemberInfo($memberInfo)
    {
        $this->member_info = $memberInfo;

        return $this;
    }

    /**
     * Get memberInfo
     *
     * @return string
     */
    public function getMemberInfo()
    {
        return $this->member_info;
    }

    /**
     * Set groupGoodsType
     *
     * @param string $groupGoodsType
     *
     * @return PromotionGroupsTeamMember
     */
    public function setGroupGoodsType($groupGoodsType)
    {
        $this->group_goods_type = $groupGoodsType;

        return $this;
    }

    /**
     * Get groupGoodsType
     *
     * @return string
     */
    public function getGroupGoodsType()
    {
        return $this->group_goods_type;
    }
}
