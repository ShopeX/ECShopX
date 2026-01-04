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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Members 业务员关联会员
 *
 * @ORM\Table(name="business_rep_user", options={"comment"="业务员关联会员"}, indexes={
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"}),
 *    @ORM\Index(name="idx_user_bussiness_id",  columns={"user_id", "business_rep_id"})
 * },uniqueConstraints={
 *    @ORM\UniqueConstraint(name="user_bussiness_id", columns={"user_id", "business_rep_id"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\BusinessRepUserRepository")
 */
class BusinessRepUser
{

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="用户id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */

    /**
     * 关系ID（自增主键）
     * @var integer
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="bigint", name="relation_id", options={"comment"="关系ID（自增主键）"})
     */
    private $relationId;

    /**
     * 获取关系ID
     *
     * @return int
     */
    public function getRelationId(): int
    {
        return $this->relationId;
    }

    // Setter is not needed because this field is managed by Doctrine's AUTO_INCREMENT strategy.

    /**
     * 业务员ID（外键，关联到业务员表）
     *
     * @ORM\Column(name="business_rep_id", nullable=false, options={"comment"="业务员ID（外键，关联到业务员表）"})
     */
    private $businessRepId;

    /**
     * 设置业务员实体
     *
     * @param BusinessRep $businessRep
     * @return self
     */
    public function setBusinessRep( $businessRepId)
    {
        $this->businessRepId = $businessRepId;
        return $this;
    }

    /**
     * 获取业务员实体
     *
     * @return businessRepId
     */
    public function getBusinessRepId()
    {
        return $this->businessRepId;
    }

    /**
     * 用户ID（外键，关联到用户表，表示会员）
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="用户id"})
     */
    private $userId;

    /**
     * 设置会员实体
     *
     * @param Members $userId
     * @return self
     */
    public function setUser($userId)
    {
        $this->userId = $userId;
        return $this;
    }

    /**
     * 获取会员实体
     *
     * @return Members
     */
    public function getuserId()
    {
        return $this->userId;
    }

    /**
     * 指定日期（即业务员开始服务该用户的日期）
     *
     * @ORM\Column(type="date", name="assigned_date", options={"comment"="指定日期（即业务员开始服务该用户的日期）"})
     */
    private $assignedDate;

    /**
     * 设置指定日期
     *
     * @param $assignedDate
     * @return self
     */
    public function setAssignedDate($assignedDate)
    {
        $this->assignedDate = $assignedDate;
        return $this;
    }

    /**
     * 获取指定日期
     *
     * @return assignedDate
     */
    public function getAssignedDate()
    {
        return $this->assignedDate;
    }

    /**
     * 关系描述（可选，用于记录特殊说明）
     *
     * @ORM\Column(type="text", name="description", nullable=true, options={"comment"="关系描述（可选，用于记录特殊说明）"})
     */
    private $description;

    /**
     * 设置关系描述
     *
     * @param string|null $description
     * @return self
     */
    public function setDescription( $description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * 获取关系描述
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @var \DateTime $createTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */    
    private $createTime;

    /**
     * 设置创建时间
     *
     * @param int $createTime
     * @return self
     */
    public function setCreateTime(int $createTime)
    {
        $this->createTime = $createTime;
        return $this;
    }

    /**
     * 获取创建时间
     *
     * @return int
     */
    public function getCreateTime(): int
    {
        return $this->createTime;
    }

    /**
     * Set businessRepId.
     *
     * @param string $businessRepId
     *
     * @return BusinessRepUser
     */
    public function setBusinessRepId($businessRepId)
    {
        $this->businessRepId = $businessRepId;

        return $this;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return BusinessRepUser
     */
    public function setUserId($userId)
    {
        $this->userId = $userId;

        return $this;
    }
}
