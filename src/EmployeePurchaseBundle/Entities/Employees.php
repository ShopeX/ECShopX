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
 * Employees 企业员工表
 *
 * @ORM\Table(name="employee_purchase_employees", options={"comment"="企业员工表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="idx_mobile", columns={"mobile"}, options={"lengths": {64}}),
 *    @ORM\Index(name="idx_enterprise_userid", columns={"enterprise_id", "user_id"}),
 * }),
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\EmployeesRepository")
 */
class Employees
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
     * @ORM\Column(name="operator_id", type="integer", options={"comment":"操作id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=500, options={"comment":"姓名"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", nullable=true, length=255, options={"comment"="手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="account", type="string", nullable=true, length=500, options={"comment":"登录账号"})
     */
    private $account;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true, length=500, options={"comment":"邮箱"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_code", type="string", length=50, nullable=true,  options={"comment":"校验码"})
     */
    private $auth_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业id"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="member_mobile", type="string", length=255, nullable=true, options={"comment"="会员手机号"})
     */
    private $member_mobile;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

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
     * @return Employees
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
     * Set name.
     *
     * @param string $name
     *
     * @return Employees
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return Employees
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set account.
     *
     * @param string|null $account
     *
     * @return Employees
     */
    public function setAccount($account = null)
    {
        $this->account = $account;

        return $this;
    }

    /**
     * Get account.
     *
     * @return string|null
     */
    public function getAccount()
    {
        return $this->account;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return Employees
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email.
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set authCode.
     *
     * @param string|null $authCode
     *
     * @return Employees
     */
    public function setAuthCode($authCode = null)
    {
        $this->auth_code = $authCode;

        return $this;
    }

    /**
     * Get authCode.
     *
     * @return string|null
     */
    public function getAuthCode()
    {
        return $this->auth_code;
    }

    /**
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return Employees
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
     * @return Employees
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
     * @return Employees
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
     * Set created.
     *
     * @param int $created
     *
     * @return Employees
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
     * Set updated.
     *
     * @param int $updated
     *
     * @return Employees
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Employees
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
     * @return Employees
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

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return Employees
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }
}
