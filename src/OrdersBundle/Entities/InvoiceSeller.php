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
 * InvoiceSeller 发票销售方表
 *
 * @ORM\Table(name="invoice_seller", options={"comment":"发票销售方表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\InvoiceSellerRepository")
 */
class InvoiceSeller
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"自增id"})
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
     * @ORM\Column(name="seller_name", type="string", length=64, options={"comment":"开票人"})
     */
    private $seller_name;

    /**
     * @var string
     * @ORM\Column(name="payee", type="string", length=64, options={"comment":"收款人"})
     */
    private $payee;

    /**
     * @var string
     * @ORM\Column(name="reviewer", type="string", length=64, options={"comment":"复核人"})
     */
    private $reviewer;

    /**
     * @var string
     * @ORM\Column(name="seller_company_name", type="string", length=128, options={"comment":"销售方名称"})
     */
    private $seller_company_name;

    /**
     * @var string
     * @ORM\Column(name="seller_tax_no", type="string", length=32, options={"comment":"销售方税号"})
     */
    private $seller_tax_no;

    /**
     * @var string
     * @ORM\Column(name="seller_bank_name", type="string", length=128, options={"comment":"销售方开户行"})
     */
    private $seller_bank_name;

    /**
     * @var string
     * @ORM\Column(name="seller_bank_account", type="string", length=64, options={"comment":"销售方银行账号"})
     */
    private $seller_bank_account;

    /**
     * @var string
     * @ORM\Column(name="seller_phone", type="string", length=32, options={"comment":"销售方电话"})
     */
    private $seller_phone;

    /**
     * @var string
     * @ORM\Column(name="seller_address", type="string", length=255, options={"comment":"销售方地址"})
     */
    private $seller_address;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="created_at", type="integer", options={"comment":"创建时间"})
     */
    private $created_at;

    /**
     * @var integer
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="updated_at", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $updated_at;

    // getter/setter
    public function getId() { return $this->id; }
    
    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return InvoiceSeller
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
    
    public function setSellerName($seller_name) { $this->seller_name = $seller_name; return $this; }
    public function getSellerName() { return $this->seller_name; }
    public function setPayee($payee) { $this->payee = $payee; return $this; }
    public function getPayee() { return $this->payee; }
    public function setReviewer($reviewer) { $this->reviewer = $reviewer; return $this; }
    public function getReviewer() { return $this->reviewer; }
    public function setSellerCompanyName($seller_company_name) { $this->seller_company_name = $seller_company_name; return $this; }
    public function getSellerCompanyName() { return $this->seller_company_name; }
    public function setSellerTaxNo($seller_tax_no) { $this->seller_tax_no = $seller_tax_no; return $this; }
    public function getSellerTaxNo() { return $this->seller_tax_no; }
    public function setSellerBankName($seller_bank_name) { $this->seller_bank_name = $seller_bank_name; return $this; }
    public function getSellerBankName() { return $this->seller_bank_name; }
    public function setSellerBankAccount($seller_bank_account) { $this->seller_bank_account = $seller_bank_account; return $this; }
    public function getSellerBankAccount() { return $this->seller_bank_account; }
    public function setSellerPhone($seller_phone) { $this->seller_phone = $seller_phone; return $this; }
    public function getSellerPhone() { return $this->seller_phone; }
    public function setSellerAddress($seller_address) { $this->seller_address = $seller_address; return $this; }
    public function getSellerAddress() { return $this->seller_address; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; return $this; }
    public function getCreatedAt() { return $this->created_at; }
    public function setUpdatedAt($updated_at) { $this->updated_at = $updated_at; return $this; }
    public function getUpdatedAt() { return $this->updated_at; }
} 