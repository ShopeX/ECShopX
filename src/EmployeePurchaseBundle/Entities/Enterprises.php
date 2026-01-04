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
 * Enterprises 企业表
 *
 * @ORM\Table(name="employee_purchase_enterprises", options={"comment":"企业表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *         @ORM\Index(name="idx_enterprise_sn", columns={"enterprise_sn"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\EnterprisesRepository")
 */
class Enterprises
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;
    
    /**
     * @var integer
     *  为0时表示商城的企业
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示商城的企业", "default": 0})
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
     * @ORM\Column(name="name", type="string", options={"comment":"企业名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="enterprise_sn", type="string", length=50, options={"comment":"企业编码"})
     */
    private $enterprise_sn;

    /**
     * @var string
     *
     * @ORM\Column(name="logo", nullable=true, type="text", options={"comment":"企业logo"})
     */
    private $logo;
    
    /**
     * @var string
     *
     * @ORM\Column(name="qr_code_bg_image", nullable=true, type="text", options={"comment":"二维码背景图"})
     */
    private $qr_code_bg_image;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_employee_check_enabled", type="boolean", options={"comment":"是否验证员工白名单", "default":false})
     */
    private $is_employee_check_enabled = false;

    /**
     * @var string
     *
     * @ORM\Column(name="auth_type", type="string", length=20, options={"comment":"登录类型,mobile:手机号,account:账号,email:邮箱,qr_code:二维码"})
     */
    private $auth_type = 'mobile';

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"禁用", "default": 0})
     */
    private $disabled = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"排序", "default": 0})
     */
    private $sort = 0;

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
     * @return Enterprises
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
     * @return Enterprises
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
        // KEY: U2hvcEV4
        return $this->name;
    }

    /**
     * Set enterpriseSn.
     *
     * @param string $enterpriseSn
     *
     * @return Enterprises
     */
    public function setEnterpriseSn($enterpriseSn)
    {
        $this->enterprise_sn = $enterpriseSn;

        return $this;
    }

    /**
     * Get enterpriseSn.
     *
     * @return string
     */
    public function getEnterpriseSn()
    {
        return $this->enterprise_sn;
    }

    /**
     * Set logo.
     *
     * @param string $logo
     *
     * @return Enterprises
     */
    public function setLogo($logo)
    {
        $this->logo = $logo;

        return $this;
    }

    /**
     * Get logo.
     *
     * @return string
     */
    public function getLogo()
    {
        return $this->logo;
    }

    /**
     * Set authType.
     *
     * @param string $authType
     *
     * @return Enterprises
     */
    public function setAuthType($authType)
    {
        $this->auth_type = $authType;

        return $this;
    }

    /**
     * Get authType.
     *
     * @return string
     */
    public function getAuthType()
    {
        return $this->auth_type;
    }

    /**
     * Set disabled.
     *
     * @param bool $disabled
     *
     * @return Enterprises
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
     * Set sort.
     *
     * @param int $sort
     *
     * @return Enterprises
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort.
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Enterprises
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
     * @param int|null $updated
     *
     * @return Enterprises
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return Enterprises
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
     * @return Enterprises
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

    /**
     * Set qrCodeBgImage.
     *
     * @param string|null $qrCodeBgImage
     *
     * @return Enterprises
     */
    public function setQrCodeBgImage($qrCodeBgImage = null)
    {
        $this->qr_code_bg_image = $qrCodeBgImage;

        return $this;
    }

    /**
     * Get qrCodeBgImage.
     *
     * @return string|null
     */
    public function getQrCodeBgImage()
    {
        return $this->qr_code_bg_image;
    }

    /**
     * Set isEmployeeCheckEnabled.
     *
     * @param bool $isEmployeeCheckEnabled
     *
     * @return Enterprises
     */
    public function setIsEmployeeCheckEnabled($isEmployeeCheckEnabled)
    {
        $this->is_employee_check_enabled = $isEmployeeCheckEnabled;

        return $this;
    }

    /**
     * Get isEmployeeCheckEnabled.
     *
     * @return bool
     */
    public function getIsEmployeeCheckEnabled()
    {
        return $this->is_employee_check_enabled;
    }
}
