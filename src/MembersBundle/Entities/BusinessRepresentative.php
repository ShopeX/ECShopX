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
 * Members 业务员
 *
 * @ORM\Table(name="business_representative", options={"comment"="业务员"}, indexes={
 *    @ORM\Index(name="idx_user_id",   columns={"user_id"}),
 *    @ORM\Index(name="idx_user_bussiness_id",  columns={"user_id", "business_rep_id"})
 * },uniqueConstraints={
 *    @ORM\UniqueConstraint(name="user_bussiness_id", columns={"user_id", "business_rep_id"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\BusinessRepresentativeRepository")
 */
class BusinessRepresentative
{
    /**
     * 业务员ID（自增主键）
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="bigint", name="business_rep_id")
     */
    private $businessRepId;

    /**
     * 用户ID（外键，关联到用户表）
     *
     * @ORM\Column(type="bigint", name="user_id")
     */
    private $userId;

    /**
     * 姓名
     *
     * @ORM\Column(type="string", length=100, name="name")
     */
    private $name;

    /**
     * 性别
     *
     * @ORM\Column(type="string", length=6, name="gender", nullable=true)
     */
    private $gender;

    /**
     * 年龄
     *
     * @ORM\Column(type="integer", name="age", nullable=true)
     */
    private $age;

    /**
     * 入职日期
     *
     * @ORM\Column(type="date", name="hire_date")
     */
    private $hireDate;

    /**
     * 所属部门ID
     *
     * @ORM\Column(type="integer", name="department_id", nullable=true)
     */
    private $departmentId;

    /**
     * 职位
     *
     * @ORM\Column(type="string", length=50, name="job_title")
     */
    private $jobTitle;

    /**
     * 销售业绩
     *
     * @ORM\Column(type="decimal", precision=10, scale=2, name="sales_performance", options={"default" : "0.00"})
     */
    private $salesPerformance;

    /**
     * 客户关系维护得分
     *
     * @ORM\Column(type="decimal", precision=5, scale=2, name="customer_relations_score", options={"default" : "0.00"})
     */
    private $customerRelationsScore;

    /**
     * 是否在职
     *
     * @ORM\Column(type="boolean", name="is_active", options={"default" : true})
     */
    private $isActive;

    /**
     * 创建时间
     *
     * @ORM\Column(type="integer", name="create_time")
     */
    private $createTime;

    /**
     * 获取业务员ID
     *
     * @return int
     */
    public function getBusinessRepId(): ?int
    {
        return $this->businessRepId;
    }

    /**
     * 设置业务员ID（通常由系统自动设置，无需手动赋值）
     *
     * @param int $businessRepId
     * @return self
     */
    public function setBusinessRepId(int $businessRepId)
    {
        $this->businessRepId = $businessRepId;

        return $this;
    }

    /**
     * 获取用户ID
     *
     * @return int
     */
    public function getUserId(): ?int
    {
        return $this->userId;
    }

    /**
     * 设置用户ID
     *
     * @param int $userId
     * @return self
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;

        return $this;
    }

    /**
     * 获取姓名
     *
     * @return string
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * 设置姓名
     *
     * @param string $name
     * @return self
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * 获取性别
     *
     * @return string|null
     */
    public function getGender(): ?string
    {
        return $this->gender;
    }

    /**
     * 设置性别
     *
     * @param string|null $gender
     * @return self
     */
    public function setGender(?string $gender)
    {
        $this->gender = $gender;

        return $this;
    }

    /**
     * 获取年龄
     *
     * @return int|null
     */
    public function getAge(): ?int
    {
        return $this->age;
    }

    /**
     * 设置年龄
     *
     * @param int|null $age
     * @return self
     */
    public function setAge(?int $age)
    {
        $this->age = $age;

        return $this;
    }

    /**
     * 获取入职日期
     *
     * @return DateTimeInterface
     */
    public function getHireDate(): ?DateTimeInterface
    {
        return $this->hireDate;
    }

    /**
     * 设置入职日期
     *
     * @param DateTimeInterface $hireDate
     * @return self
     */
    public function setHireDate(DateTimeInterface $hireDate)
    {
        $this->hireDate = $hireDate;

        return $this;
    }

    /**
     * 获取所属部门ID
     *
     * @return int|null
     */
    public function getDepartmentId(): ?int
    {
        return $this->departmentId;
    }

    /**
     * 设置所属部门ID
     *
     * @param int|null $departmentId
     * @return self
     */
    public function setDepartmentId(?int $departmentId)
    {
        $this->departmentId = $departmentId;

        return $this;
    }

    /**
     * 获取职位
     *
     * @return string
     */
    public function getJobTitle(): ?string
    {
        return $this->jobTitle;
    }

    /**
     * 设置职位
     *
     * @param string $jobTitle
     * @return self
     */
    public function setJobTitle(string $jobTitle)
    {
        $this->jobTitle = $jobTitle;

        return $this;
    }

    /**
     * 获取销售业绩
     *
     * @return float
     */
    public function getSalesPerformance(): ?float
    {
        return $this->salesPerformance;
    }

    /**
     * 设置销售业绩
     *
     * @param float $salesPerformance
     * @return self
     */
    public function setSalesPerformance(float $salesPerformance)
    {
        $this->salesPerformance = $salesPerformance;

        return $this;
    }


    /**
     * 客户关系维护得分
     *
     * @ORM\Column(type="decimal", precision=5, scale=2, name="customer_relations_score", options={"default" : "0.00"})
     */
    /**
     * 获取客户关系维护得分
     *
     * @return float
     */
    public function getCustomerRelationsScore(): ?float
    {
        return $this->customerRelationsScore;
    }

    /**
     * 设置客户关系维护得分
     *
     * @param float $customerRelationsScore
     * @return self
     */
    public function setCustomerRelationsScore(float $customerRelationsScore)
    {
        $this->customerRelationsScore = $customerRelationsScore;

        return $this;
    }

    /**
     * 获取是否在职
     *
     * @return isActive
     */
    public function getIsActive()
    {
        return $this->isActive;
    }

    /**
     * 设置是否在职
     *
     * @param boolboolean $isActive
     * @return self
     */
    public function setIsActive($isActive)
    {
        $this->isActive = $isActive;

        return $this;
    }

     /**
     * 获取创建时间
     *
     * @return createTime
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * 设置创建时间
     *
     * @param integer $createTime
     * @return self
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;

        return $this;
    }
}
