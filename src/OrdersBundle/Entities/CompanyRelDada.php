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
 * CompanyRelDada 商城关联达达同城配表
 *
 * @ORM\Table(name="company_rel_dada", options={"comment":"商城关联达达同城配表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CompanyRelDadaRepository")
 */
class CompanyRelDada
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
     * @var string
     *
     * @ORM\Column(name="source_id", type="string", nullable=true, options={"comment":"商户编号"})
     */
    private $source_id;

    /**
     * @var string
     *
     * @ORM\Column(name="enterprise_name", type="string", nullable=true, options={"comment":"企业全称"})
     */
    private $enterprise_name;

    /**
     * @var string
     *
     * @ORM\Column(name="enterprise_address", type="string", nullable=true, options={"comment":"企业地址"})
     */
    private $enterprise_address;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", nullable=true, options={"comment":"商户手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="city_name", type="string", nullable=true, options={"comment":"商户城市名称"})
     */
    private $city_name;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_name", type="string", nullable=true, options={"comment":"联系人姓名"})
     */
    private $contact_name;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_phone", type="string", nullable=true, options={"comment":"联系人电话"})
     */
    private $contact_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true,  options={"comment":"邮箱地址"})
     */
    private $email;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight_type", type="boolean", options={"default": 0, "comment":"运费承担方:0:商家承担，1:买家承担"})
     */
    private $freight_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="boolean", options={"default": 0, "comment":"开通状态:0:未开通过，1:已开通过"})
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_open", type="boolean", options={"default": 0, "comment":"是否开启:0:未开启，1:已开启"})
     */
    private $is_open;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created", type="integer", nullable=true,  options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var integer
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated", type="integer", nullable=true,  options={"comment":"更新时间"})
     */
    private $updated;

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
     * @return CompanyRelDada
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
     * Set sourceId.
     *
     * @param string|null $sourceId
     *
     * @return CompanyRelDada
     */
    public function setSourceId($sourceId = null)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return string|null
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set enterpriseName.
     *
     * @param string|null $enterpriseName
     *
     * @return CompanyRelDada
     */
    public function setEnterpriseName($enterpriseName = null)
    {
        $this->enterprise_name = $enterpriseName;

        return $this;
    }

    /**
     * Get enterpriseName.
     *
     * @return string|null
     */
    public function getEnterpriseName()
    {
        return $this->enterprise_name;
    }

    /**
     * Set enterpriseAddress.
     *
     * @param string|null $enterpriseAddress
     *
     * @return CompanyRelDada
     */
    public function setEnterpriseAddress($enterpriseAddress = null)
    {
        $this->enterprise_address = $enterpriseAddress;

        return $this;
    }

    /**
     * Get enterpriseAddress.
     *
     * @return string|null
     */
    public function getEnterpriseAddress()
    {
        return $this->enterprise_address;
    }

    /**
     * Set mobile.
     *
     * @param string|null $mobile
     *
     * @return CompanyRelDada
     */
    public function setMobile($mobile = null)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string|null
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set cityName.
     *
     * @param string|null $cityName
     *
     * @return CompanyRelDada
     */
    public function setCityName($cityName = null)
    {
        $this->city_name = $cityName;

        return $this;
    }

    /**
     * Get cityName.
     *
     * @return string|null
     */
    public function getCityName()
    {
        return $this->city_name;
    }

    /**
     * Set contactName.
     *
     * @param string|null $contactName
     *
     * @return CompanyRelDada
     */
    public function setContactName($contactName = null)
    {
        $this->contact_name = $contactName;

        return $this;
    }

    /**
     * Get contactName.
     *
     * @return string|null
     */
    public function getContactName()
    {
        return $this->contact_name;
    }

    /**
     * Set contactMobile.
     *
     * @param string|null $contactPhone
     *
     * @return CompanyRelDada
     */
    public function setContactPhone($contactPhone = null)
    {
        $this->contact_phone = $contactPhone;

        return $this;
    }

    /**
     * Get contactMobile.
     *
     * @return string|null
     */
    public function getContactPhone()
    {
        return $this->contact_phone;
    }

    /**
     * Set email.
     *
     * @param string|null $email
     *
     * @return CompanyRelDada
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
     * Set freightType.
     *
     * @param int $freightType
     *
     * @return CompanyRelDada
     */
    public function setFreightType($freightType)
    {
        $this->freight_type = $freightType;

        return $this;
    }

    /**
     * Get freightType.
     *
     * @return int
     */
    public function getFreightType()
    {
        return $this->freight_type;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return CompanyRelDada
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set is_open.
     *
     * @param int $is_open
     *
     * @return CompanyRelDada
     */
    public function setIsOpen($is_open)
    {
        $this->is_open = $is_open;

        return $this;
    }

    /**
     * Get is_open.
     *
     * @return int
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set created.
     *
     * @param int|null $created
     *
     * @return CompanyRelDada
     */
    public function setCreated($created = null)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int|null
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
     * @return CompanyRelDada
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
}
