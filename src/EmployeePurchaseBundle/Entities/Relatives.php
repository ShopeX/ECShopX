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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Relatives 企业员工家属表
 *
 * @ORM\Table(name="employee_purchase_relatives", options={"comment"="企业员工家属表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="employee_user_id", columns={"employee_user_id"}),
 *    @ORM\Index(name="activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="user_id", columns={"user_id"}),
 *    @ORM\Index(name="idx_member_mobile", columns={"member_mobile"}, options={"lengths": {64}}),
 *    @ORM\Index(name="idx_enterprise_userid_actid", columns={"enterprise_id", "user_id", "activity_id"}),
 * }),
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\RelativesRepository")
 */
class Relatives
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;
    
    /**
     * @var integer
     *  为0时表示为商城的员工
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示为商城的员工", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业id"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="member_mobile", type="string", length=255, options={"comment"="会员手机号"})
     */
    private $member_mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment"="活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_id", type="bigint", options={"comment"="员工id"})
     */
    private $employee_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_user_id", type="bigint", options={"comment"="员工关联用户id"})
     */
    private $employee_user_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"失效", "default": 0})
     */
    private $disabled = 0;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Relatives
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return Relatives
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return int
     */
    public function getEnterpriseId()
    {
        return $this->enterprise_id;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return Relatives
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set memberMobile.
     *
     * @param string $memberMobile
     *
     * @return Relatives
     */
    public function setMemberMobile($memberMobile)
    {
        $this->member_mobile = $memberMobile;

        return $this;
    }

    /**
     * Get memberMobile.
     *
     * @return string
     */
    public function getMemberMobile()
    {
        return $this->member_mobile;
    }

    /**
     * Set activityId.
     *
     * @param int $activityId
     *
     * @return Relatives
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId.
     *
     * @return int
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set employeeId.
     *
     * @param int $employeeId
     *
     * @return Relatives
     */
    public function setEmployeeId($employeeId)
    {
        $this->employee_id = $employeeId;

        return $this;
    }

    /**
     * Get employeeId.
     *
     * @return int
     */
    public function getEmployeeId()
    {
        return $this->employee_id;
    }

    /**
     * Set employeeUserId.
     *
     * @param int $employeeUserId
     *
     * @return Relatives
     */
    public function setEmployeeUserId($employeeUserId)
    {
        $this->employee_user_id = $employeeUserId;

        return $this;
    }

    /**
     * Get employeeUserId.
     *
     * @return int
     */
    public function getEmployeeUserId()
    {
        return $this->employee_user_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Relatives
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Relatives
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return bool
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return Relatives
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }
}
