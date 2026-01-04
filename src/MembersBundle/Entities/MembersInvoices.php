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
 * MembersInvoices(会员发票信息表)
 *
 * @ORM\Table(name="members_invoices", options={"comment":"会员发票信息表"})
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersInvoicesRepository")
 */
class MembersInvoices
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="invoices_id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $invoices_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="invoices_type", type="string", length=15, options={"comment": "类型 personal 个人 ；corporate 企业 "})
     */
    private $invoices_type;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string",  nullable=true, length=20, options={"comment":"电话号码"})
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_number", type="string", nullable=true, options={"comment":"税号"})
     */
    private $tax_number;

    /**
     * @var string
     *
     * @ORM\Column(name="business_address", type="string", nullable=true, options={"comment":"单位地址"})
     */
    private $business_address;

    /**
     * @var string
     *
     * @ORM\Column(name="bank", type="string", nullable=true, options={"comment":"开户银行"})
     */
    private $bank;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account", type="string", nullable=true, options={"comment":"银行账号"})
     */
    private $bank_account;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_def", type="boolean", options={"comment": "是否默认", "default": 0})
     */
    private $is_def = 0;

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
     * Get invoicesId
     *
     * @return integer
     */
    public function getInvoicesId()
    {
        return $this->invoices_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MembersInvoices
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return MembersInvoices
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set invoicesType
     *
     * @param string $invoicesType
     *
     * @return MembersInvoices
     */
    public function setInvoicesType($invoicesType)
    {
        $this->invoices_type = $invoicesType;

        return $this;
    }

    /**
     * Get invoicesType
     *
     * @return string
     */
    public function getInvoicesType()
    {
        return $this->invoices_type;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return MembersInvoices
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     *
     * @return MembersInvoices
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set taxNumber
     *
     * @param string $taxNumber
     *
     * @return MembersInvoices
     */
    public function setTaxNumber($taxNumber)
    {
        $this->tax_number = $taxNumber;

        return $this;
    }

    /**
     * Get taxNumber
     *
     * @return string
     */
    public function getTaxNumber()
    {
        return $this->tax_number;
    }

    /**
     * Set businessAddress
     *
     * @param string $businessAddress
     *
     * @return MembersInvoices
     */
    public function setBusinessAddress($businessAddress)
    {
        $this->business_address = $businessAddress;

        return $this;
    }

    /**
     * Get businessAddress
     *
     * @return string
     */
    public function getBusinessAddress()
    {
        return $this->business_address;
    }

    /**
     * Set bank
     *
     * @param string $bank
     *
     * @return MembersInvoices
     */
    public function setBank($bank)
    {
        $this->bank = $bank;

        return $this;
    }

    /**
     * Get bank
     *
     * @return string
     */
    public function getBank()
    {
        return $this->bank;
    }

    /**
     * Set bankAccount
     *
     * @param string $bankAccount
     *
     * @return MembersInvoices
     */
    public function setBankAccount($bankAccount)
    {
        $this->bank_account = $bankAccount;

        return $this;
    }

    /**
     * Get bankAccount
     *
     * @return string
     */
    public function getBankAccount()
    {
        return $this->bank_account;
    }

    /**
     * Set isDef
     *
     * @param boolean $isDef
     *
     * @return MembersInvoices
     */
    public function setIsDef($isDef)
    {
        $this->is_def = $isDef;

        return $this;
    }

    /**
     * Get isDef
     *
     * @return boolean
     */
    public function getIsDef()
    {
        return $this->is_def;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MembersInvoices
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
     * @return MembersInvoices
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
}
