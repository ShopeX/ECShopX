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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * PromotionGroupsTeam 开团表
 *
 * @ORM\Table(name="promotion_groups_team", options={"comment":"开团表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PromotionGroupsTeamRepository")
 */
class PromotionGroupsTeam
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
     * @ORM\Column(name="head_mid", type="bigint", options={"comment":"团长会员ID"})
     */
    private $head_mid;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="bigint", options={"comment":"开团时间"})
     */
    private $begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", options={"comment":"结束时间(根据成团时效和活动结束时间算出来的)"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="join_person_num", type="bigint", options={"default": 0, "comment":"参与人数"})
     */
    private $join_person_num;

    /**
     * @var string
     *
     * @ORM\Column(name="group_goods_type", type="string", length=255, options={"comment":"团购活动商品类型", "default":"services"})
     */
    private $group_goods_type = 'services';

    /**
     * @var integer
     *
     * @ORM\Column(name="team_status", type="bigint", options={"default": 1, "comment":"状态:1.进行中2.成功3.失败"})
     */
    private $team_status;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", nullable=true, options={"comment":"是否禁用 true=禁用,false=启用", "default": false})
     */
    private $disabled;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated;

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
     * @return PromotionGroupsTeam
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
     * @return PromotionGroupsTeam
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
     * @return PromotionGroupsTeam
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
     * Set headMid
     *
     * @param integer $headMid
     *
     * @return PromotionGroupsTeam
     */
    public function setHeadMid($headMid)
    {
        $this->head_mid = $headMid;

        return $this;
    }

    /**
     * Get headMid
     *
     * @return integer
     */
    public function getHeadMid()
    {
        return $this->head_mid;
    }

    /**
     * Set beginTime
     *
     * @param integer $beginTime
     *
     * @return PromotionGroupsTeam
     */
    public function setBeginTime($beginTime)
    {
        $this->begin_time = $beginTime;

        return $this;
    }

    /**
     * Get beginTime
     *
     * @return integer
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return PromotionGroupsTeam
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set joinPersonNum
     *
     * @param integer $joinPersonNum
     *
     * @return PromotionGroupsTeam
     */
    public function setJoinPersonNum($joinPersonNum)
    {
        $this->join_person_num = $joinPersonNum;

        return $this;
    }

    /**
     * Get joinPersonNum
     *
     * @return integer
     */
    public function getJoinPersonNum()
    {
        return $this->join_person_num;
    }

    /**
     * Set teamStatus
     *
     * @param integer $teamStatus
     *
     * @return PromotionGroupsTeam
     */
    public function setTeamStatus($teamStatus)
    {
        $this->team_status = $teamStatus;

        return $this;
    }

    /**
     * Get teamStatus
     *
     * @return integer
     */
    public function getTeamStatus()
    {
        return $this->team_status;
    }

    /**
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return PromotionGroupsTeam
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
     * Set created
     *
     * @param integer $created
     *
     * @return PromotionGroupsTeam
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
     * @return PromotionGroupsTeam
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
     * Set groupGoodsType
     *
     * @param string $groupGoodsType
     *
     * @return PromotionGroupsTeam
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
