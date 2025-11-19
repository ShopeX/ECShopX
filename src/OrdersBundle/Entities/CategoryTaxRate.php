<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace OrdersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * CategoryTaxRate 分类税率表
 *
 * @ORM\Table(name="category_tax_rate", options={"comment":"分类税率表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CategoryTaxRateRepository")
 */
class CategoryTaxRate
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
     * @ORM\Column(name="sales_party_id", type="string", length=64, options={"comment":"销售方ID"})
     */
    private $sales_party_id;

    /**
     * @var string
     * @ORM\Column(name="tax_rate_type", type="string", length=32, options={"comment":"税率分类：ALL/SPECIFIED"})
     */
    private $tax_rate_type;

    /**
     * @var string
     * @ORM\Column(name="category_ids", type="text", nullable=true, options={"comment":"分类ID数组，json存储"})
     */
    private $category_ids;

    /**
     * @var string
     * @ORM\Column(name="invoice_tax_rate", type="string", length=16, options={"comment":"发票税率，如13%"})
     */
    private $invoice_tax_rate;

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
     * @return CategoryTaxRate
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
    
    public function setSalesPartyId($sales_party_id) { $this->sales_party_id = $sales_party_id; return $this; }
    public function getSalesPartyId() { return $this->sales_party_id; }
    public function setTaxRateType($tax_rate_type) { $this->tax_rate_type = $tax_rate_type; return $this; }
    public function getTaxRateType() { return $this->tax_rate_type; }
    public function setCategoryIds($category_ids) { $this->category_ids = $category_ids; return $this; }
    public function getCategoryIds() { return $this->category_ids; }
    public function setInvoiceTaxRate($invoice_tax_rate) { $this->invoice_tax_rate = $invoice_tax_rate; return $this; }
    public function getInvoiceTaxRate() { return $this->invoice_tax_rate; }
    public function setCreatedAt($created_at) { $this->created_at = $created_at; return $this; }
    public function getCreatedAt() { return $this->created_at; }
    public function setUpdatedAt($updated_at) { $this->updated_at = $updated_at; return $this; }
    public function getUpdatedAt() { return $this->updated_at; }
} 