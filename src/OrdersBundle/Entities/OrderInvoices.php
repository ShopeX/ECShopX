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
 * OrderInvoices 发票表
 *
 * @ORM\Table(name="orders_invoices", options={"comment":"发票表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_invoice_id", columns={"invoice_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderInvoicesRepository")
 */
class OrderInvoices
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", length=64, options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="invoice_id", type="bigint", options={"comment":"关联发票表id"})
     */
    private $invoice_id;



    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;



    /**
     * @var string
     *
     * @ORM\Column(name="invoice_type", type="string", length=20, options={"comment":"开票类型，红red,和蓝blue"})
     */
    private $invoice_type;


    /**
     * @var string
     *
     * @ORM\Column(name="invoice_no", type="string", nullable=true, options={"comment":"发票号码"})
     */
    private $invoice_no;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_code", type="string", nullable=true, options={"comment":"发票代码"})
     */
    private $invoice_code;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_time", type="string", nullable=true, options={"comment":"开票时间"})
     */
    private $invoice_time;


    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(name="create_time", type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(name="update_time", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;



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
     * Set invoiceId.
     *
     * @param int $invoiceId
     *
     * @return OrderInvoices
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoice_id = $invoiceId;

        return $this;
    }

    /**
     * Get invoiceId.
     *
     * @return int
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrderInvoices
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
     * Set invoiceType.
     *
     * @param string $invoiceType
     *
     * @return OrderInvoices
     */
    public function setInvoiceType($invoiceType)
    {
        $this->invoice_type = $invoiceType;

        return $this;
    }

    /**
     * Get invoiceType.
     *
     * @return string
     */
    public function getInvoiceType()
    {
        return $this->invoice_type;
    }

    /**
     * Set invoiceNo.
     *
     * @param string|null $invoiceNo
     *
     * @return OrderInvoices
     */
    public function setInvoiceNo($invoiceNo = null)
    {
        $this->invoice_no = $invoiceNo;

        return $this;
    }

    /**
     * Get invoiceNo.
     *
     * @return string|null
     */
    public function getInvoiceNo()
    {
        return $this->invoice_no;
    }

    /**
     * Set invoiceCode.
     *
     * @param string|null $invoiceCode
     *
     * @return OrderInvoices
     */
    public function setInvoiceCode($invoiceCode = null)
    {
        $this->invoice_code = $invoiceCode;

        return $this;
    }

    /**
     * Get invoiceCode.
     *
     * @return string|null
     */
    public function getInvoiceCode()
    {
        return $this->invoice_code;
    }

    /**
     * Set invoiceTime.
     *
     * @param string|null $invoiceTime
     *
     * @return OrderInvoices
     */
    public function setInvoiceTime($invoiceTime = null)
    {
        $this->invoice_time = $invoiceTime;

        return $this;
    }

    /**
     * Get invoiceTime.
     *
     * @return string|null
     */
    public function getInvoiceTime()
    {
        return $this->invoice_time;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return OrderInvoices
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime.
     *
     * @return int
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime.
     *
     * @param int|null $updateTime
     *
     * @return OrderInvoices
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime.
     *
     * @return int|null
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }
}
