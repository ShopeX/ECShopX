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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use LaravelDoctrine\Extensions\SoftDeletes\SoftDeletes;

/**
 * Companys 公司表
 *
 * @ORM\Table(name="companys", options={"comment":"公司表"},
 *     indexes={
 *         @ORM\Index(name="idx_pc_domain", columns={"pc_domain"}),
 *         @ORM\Index(name="idx_h5_domain", columns={"h5_domain"}),
 *     },
 *    uniqueConstraints={
 *         @ORM\UniqueConstraint(name="idx_passportuid", columns={"passport_uid"}),
 *     })
 * )
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\CompanysRepository")
 */
class Companys
{
    use SoftDeletes;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_name", type="string", nullable=true, length=255, options={"comment":"公司名称"})
     */
    private $company_name;

    /**
     * @var string
     *
     * @ORM\Column(name="pc_domain", type="string", nullable=true, length=200, options={"comment":"PC域名"})
     */
    private $pc_domain;

    /**
     * @var string
     *
     * @ORM\Column(name="h5_domain", type="string", nullable=true, length=200, options={"comment":"H5域名"})
     */
    private $h5_domain;

    /**
     * @var string
     *
     * @ORM\Column(name="eid", type="string", nullable=true)
     */
    private $eid;

    /**
     * @var string
     *
     * @ORM\Column(name="passport_uid", type="string", nullable=true)
     */
    private $passport_uid;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_admin_operator_id", type="bigint", options={"comment":"公司管理员id"})
     */
    private $company_admin_operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="industry", type="string", nullable=true, options={"comment":"所属行业"})
     */
    private $industry;

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
     * @var integer
     *
     * @ORM\Column(name="expiredAt", type="bigint", nullable=true, options={"comment":"过期时间"})
     */
    private $expiredAt;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_disabled", type="boolean", options={"comment":"是否禁用", "default": 0})
     */
    private $is_disabled = 0;

    /**
     * @var json_array
     *
     * @ORM\Column(name="third_params", type="json_array", nullable=true, options={"comment":"第三方特殊字段存储"})
     */
    private $third_params;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesman_limit", type="integer", options={"comment":"导购员数量", "default": 20})
     */
    private $salesman_limit = 20;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_open_pc_template", type="integer", nullable=true, options={"comment":"是否开启pc模板 1 开启 2不开启", "default": 1})
     */
    private $is_open_pc_template = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_open_domain_setting", type="integer", nullable=true, options={"comment":"是否开启域名配置 1 开启 2不开启", "default": 2})
     */
    private $is_open_domain_setting = 2;

    /**
     * @var integer
     *
     * @ORM\Column(name="menu_type", type="integer",  options={"comment":"菜单类型。2:'b2c',3:'platform',4:'standard',5:'in_purchase'", "default":3})
     */
    private $menu_type = 3;


    /**
     * Set menuType.
     *
     * @param int $menuType
     *
     * @return Companys
     */
    public function setMenuType($menuType)
    {
        $this->menu_type = $menuType;

        return $this;
    }

    /**
     * Get menuType.
     *
     * @return int
     */
    public function getMenuType()
    {
        return $this->menu_type;
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
     * Set companyName
     *
     * @param string $companyName
     *
     * @return Companys
     */
    public function setCompanyName($companyName)
    {
        // 456353686f7058
        $this->company_name = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * Set pcDomain
     *
     * @param string $pcDomain
     *
     * @return Companys
     */
    public function setPcDomain($pcDomain)
    {
        $this->pc_domain = $pcDomain;

        return $this;
    }

    /**
     * Get pcDomain
     *
     * @return string
     */
    public function getPcDomain()
    {
        return $this->pc_domain;
    }

    /**
     * Set h5Domain
     *
     * @param string $h5Domain
     *
     * @return Companys
     */
    public function setH5Domain($h5Domain)
    {
        $this->h5_domain = $h5Domain;

        return $this;
    }

    /**
     * Get h5Domain
     *
     * @return string
     */
    public function getH5Domain()
    {
        return $this->h5_domain;
    }

    /**
     * Set eid
     *
     * @param string $eid
     *
     * @return Companys
     */
    public function setEid($eid)
    {
        $this->eid = $eid;

        return $this;
    }

    /**
     * Get eid
     *
     * @return string
     */
    public function getEid()
    {
        return $this->eid;
    }

    /**
     * Set passportUid
     *
     * @param string $passportUid
     *
     * @return Companys
     */
    public function setPassportUid($passportUid)
    {
        $this->passport_uid = $passportUid;

        return $this;
    }

    /**
     * Get passportUid
     *
     * @return string
     */
    public function getPassportUid()
    {
        return $this->passport_uid;
    }

    /**
     * Set companyAdminOperatorId
     *
     * @param integer $companyAdminOperatorId
     *
     * @return Companys
     */
    public function setCompanyAdminOperatorId($companyAdminOperatorId)
    {
        $this->company_admin_operator_id = $companyAdminOperatorId;

        return $this;
    }

    /**
     * Get companyAdminOperatorId
     *
     * @return integer
     */
    public function getCompanyAdminOperatorId()
    {
        return $this->company_admin_operator_id;
    }

    /**
     * Set industry
     *
     * @param string $industry
     *
     * @return Companys
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * Get industry
     *
     * @return string
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Companys
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
     * @return Companys
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
     * Set expiredAt
     *
     * @param integer $expiredAt
     *
     * @return Companys
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expiredAt = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt
     *
     * @return integer
     */
    public function getExpiredAt()
    {
        return $this->expiredAt;
    }

    /**
     * Set isDisabled
     *
     * @param boolean $isDisabled
     *
     * @return Companys
     */
    public function setIsDisabled($isDisabled)
    {
        $this->is_disabled = $isDisabled;

        return $this;
    }

    /**
     * Get isDisabled
     *
     * @return boolean
     */
    public function getIsDisabled()
    {
        return $this->is_disabled;
    }

    /**
     * Set thirdParams
     *
     * @param array $thirdParams
     *
     * @return Companys
     */
    public function setThirdParams($thirdParams)
    {
        $this->third_params = $thirdParams;

        return $this;
    }

    /**
     * Get thirdParams
     *
     * @return array
     */
    public function getThirdParams()
    {
        return $this->third_params;
    }


    /**
     * Set salesmanLimit.
     *
     * @param int $salesmanLimit
     *
     * @return Companys
     */
    public function setSalesmanLimit($salesmanLimit)
    {
        $this->salesman_limit = $salesmanLimit;

        return $this;
    }

    /**
     * Get salesmanLimit.
     *
     * @return int
     */
    public function getSalesmanLimit()
    {
        return $this->salesman_limit;
    }

    /**
     * Set isOpenPcTemplate.
     *
     * @param int|null $isOpenPcTemplate
     *
     * @return Companys
     */
    public function setIsOpenPcTemplate($isOpenPcTemplate = null)
    {
        $this->is_open_pc_template = $isOpenPcTemplate;

        return $this;
    }

    /**
     * Get isOpenPcTemplate.
     *
     * @return int|null
     */
    public function getIsOpenPcTemplate()
    {
        return $this->is_open_pc_template;
    }

    /**
     * Set isOpenDomainSetting.
     *
     * @param int|null $isOpenDomainSetting
     *
     * @return Companys
     */
    public function setIsOpenDomainSetting($isOpenDomainSetting = null)
    {
        $this->is_open_domain_setting = $isOpenDomainSetting;

        return $this;
    }

    /**
     * Get isOpenDomainSetting.
     *
     * @return int|null
     */
    public function getIsOpenDomainSetting()
    {
        return $this->is_open_domain_setting;
    }
}
