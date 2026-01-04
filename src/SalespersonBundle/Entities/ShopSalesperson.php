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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Comments 门店人员
 *
 * @ORM\Table(name="shop_salesperson", options={"comment"="门店人员"})
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\ShopSalespersonRepository")
 */
class ShopSalesperson
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"门店人员ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $salesperson_id;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=500, options={"comment":"姓名"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="created_time", type="string", options={"comment":"创建时间"})
     */
    private $created_time;

    /**
     * @var string
     *
     * @ORM\Column(name="salesperson_type", type="string", options={"comment":"人员类型 admin: 管理员; verification_clerk:核销员; shopping_guide:导购员", "default":"admin"})
     */
    private $salesperson_type = 'admin';

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_id", type="integer", options={"comment":"关联会员id", "default": 0})
     */
    private $user_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="child_count", type="integer", options={"comment":"导购员引入的会员数", "default": 0})
     */
    private $child_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="is_valid", type="string", options={"comment":"是否有效", "default":"true"})
     */
    private $is_valid = 'true';

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", nullable=true, type="string", options={"comment":"门店id"})
     */
    private $shop_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_name", nullable=true, type="string", options={"comment":"门店名称"})
     */
    private $shop_name;

    /**
     * @var string
     *
     * @ORM\Column(name="number", type="string", nullable=true, length=50, options={"comment":"导购员编号"})
     */
    private $number;

    /**
     * @ORM\Column(name="friend_count", type="integer", options={"comment":"导购员会员好友数", "default": 0})
     */
    private $friend_count = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="work_userid", type="string", nullable=true, options={"comment":"企业微信userid[如果是内部应用则是明文，如果是第三方应用则是密文]"})
     */
    private $work_userid;

    /**
     * @var string
     *
     * @ORM\Column(name="work_clear_userid", type="string", nullable=true, options={"comment":"企业微信userid[用于对接导购存储明文userid]"})
     */
    private $work_clear_userid;

    /**
     * @var string
     *
     * @ORM\Column(name="avatar", type="string", nullable=true, options={"comment":"企业微信头像"})
     */
    private $avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="work_configid", type="string", nullable=true, options={"comment":"企业微信userid"})
     */
    private $work_configid;

    /**
     * @var string
     *
     * @ORM\Column(name="work_qrcode_configid", type="string", nullable=true, options={"comment":"企业微信userid"})
     */
    private $work_qrcode_configid;

    /**
     * @var string
     *
     * @ORM\Column(name="role", nullable=true, type="string", options={"comment":"导购权限集合"})
     */
    private $role;

    /**
     * @var string
     *
     * @ORM\Column(name="salesperson_job", nullable=true, type="string", length=50, options={"comment":"职务", "default": ""})
     */
    private $salesperson_job = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_status", type="integer", options={"comment":"员工类型 [1 员工] [2 编外]", "default": 1})
     */
    private $employee_status = 1;

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
     * Get salespersonId.
     *
     * @return int
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set name.
     *
     * @param string $name
     *
     * @return ShopSalesperson
     */
    public function setName($name)
    {
        $this->name = fixedencrypt($name);

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return fixeddecrypt($this->name);
    }

    /**
     * Set mobile.
     *
     * @param string $mobile
     *
     * @return ShopSalesperson
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set createdTime.
     *
     * @param string $createdTime
     *
     * @return ShopSalesperson
     */
    public function setCreatedTime($createdTime)
    {
        $this->created_time = $createdTime;

        return $this;
    }

    /**
     * Get createdTime.
     *
     * @return string
     */
    public function getCreatedTime()
    {
        return $this->created_time;
    }

    /**
     * Set salespersonType.
     *
     * @param string $salespersonType
     *
     * @return ShopSalesperson
     */
    public function setSalespersonType($salespersonType)
    {
        $this->salesperson_type = $salespersonType;

        return $this;
    }

    /**
     * Get salespersonType.
     *
     * @return string
     */
    public function getSalespersonType()
    {
        return $this->salesperson_type;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ShopSalesperson
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return ShopSalesperson
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
     * Set childCount.
     *
     * @param int $childCount
     *
     * @return ShopSalesperson
     */
    public function setChildCount($childCount)
    {
        $this->child_count = $childCount;

        return $this;
    }

    /**
     * Get childCount.
     *
     * @return int
     */
    public function getChildCount()
    {
        return $this->child_count;
    }

    /**
     * Set isValid.
     *
     * @param string $isValid
     *
     * @return ShopSalesperson
     */
    public function setIsValid($isValid)
    {
        $this->is_valid = $isValid;

        return $this;
    }

    /**
     * Get isValid.
     *
     * @return string
     */
    public function getIsValid()
    {
        return $this->is_valid;
    }

    /**
     * Set shopId.
     *
     * @param string|null $shopId
     *
     * @return ShopSalesperson
     */
    public function setShopId($shopId = null)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return string|null
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set shopName.
     *
     * @param string|null $shopName
     *
     * @return ShopSalesperson
     */
    public function setShopName($shopName = null)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName.
     *
     * @return string|null
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set number.
     *
     * @param string|null $number
     *
     * @return ShopSalesperson
     */
    public function setNumber($number = null)
    {
        $this->number = $number;

        return $this;
    }

    /**
     * Get number.
     *
     * @return string|null
     */
    public function getNumber()
    {
        return $this->number;
    }

    /**
     * Set friendCount.
     *
     * @param int $friendCount
     *
     * @return ShopSalesperson
     */
    public function setFriendCount($friendCount)
    {
        $this->friend_count = $friendCount;

        return $this;
    }

    /**
     * Get friendCount.
     *
     * @return int
     */
    public function getFriendCount()
    {
        return $this->friend_count;
    }

    /**
     * Set workUserid.
     *
     * @param string|null $workUserid
     *
     * @return ShopSalesperson
     */
    public function setWorkUserid($workUserid = null)
    {
        $this->work_userid = $workUserid;

        return $this;
    }

    /**
     * Get workUserid.
     *
     * @return string|null
     */
    public function getWorkUserid()
    {
        return $this->work_userid;
    }

    /**
     * Set avatar.
     *
     * @param string|null $avatar
     *
     * @return ShopSalesperson
     */
    public function setAvatar($avatar = null)
    {
        $this->avatar = $avatar;

        return $this;
    }

    /**
     * Get avatar.
     *
     * @return string|null
     */
    public function getAvatar()
    {
        return $this->avatar;
    }

    /**
     * Set workConfigid.
     *
     * @param string|null $workConfigid
     *
     * @return ShopSalesperson
     */
    public function setWorkConfigid($workConfigid = null)
    {
        $this->work_configid = $workConfigid;

        return $this;
    }

    /**
     * Get workConfigid.
     *
     * @return string|null
     */
    public function getWorkConfigid()
    {
        return $this->work_configid;
    }

    /**
     * Set workQrcodeConfigid.
     *
     * @param string|null $workQrcodeConfigid
     *
     * @return ShopSalesperson
     */
    public function setWorkQrcodeConfigid($workQrcodeConfigid = null)
    {
        $this->work_qrcode_configid = $workQrcodeConfigid;

        return $this;
    }

    /**
     * Get workQrcodeConfigid.
     *
     * @return string|null
     */
    public function getWorkQrcodeConfigid()
    {
        return $this->work_qrcode_configid;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return ShopSalesperson
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
     * @return ShopSalesperson
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
     * Set role.
     *
     * @param array|null $role
     *
     * @return ShopSalesperson
     */
    public function setRole($role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role.
     *
     * @return array|null
     */
    public function getRole()
    {
        return $this->role;
    }


    /**
     * Set salespersonJob.
     *
     * @param string $salespersonJob
     *
     * @return ShopSalesperson
     */
    public function setSalespersonJob($salespersonJob)
    {
        $this->salesperson_job = $salespersonJob;

        return $this;
    }

    /**
     * Get salespersonJob.
     *
     * @return string
     */
    public function getSalespersonJob()
    {
        return $this->salesperson_job;
    }

    /**
     * Set employeeStatus.
     *
     * @param int $employeeStatus
     *
     * @return ShopSalesperson
     */
    public function setEmployeeStatus($employeeStatus)
    {
        $this->employee_status = $employeeStatus;

        return $this;
    }

    /**
     * Get employeeStatus.
     *
     * @return int
     */
    public function getEmployeeStatus()
    {
        return $this->employee_status;
    }

    /**
     * Set workClearUserid.
     *
     * @param string|null $workClearUserid
     *
     * @return ShopSalesperson
     */
    public function setWorkClearUserid($workClearUserid = null)
    {
        $this->work_clear_userid = $workClearUserid;

        return $this;
    }

    /**
     * Get workClearUserid.
     *
     * @return string|null
     */
    public function getWorkClearUserid()
    {
        return $this->work_clear_userid;
    }
}
