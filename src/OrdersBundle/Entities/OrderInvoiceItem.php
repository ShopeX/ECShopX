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
 * OrderInvoiceItem 订单发票商品表
 *
 * @ORM\Table(name="orders_invoice_item", options={"comment":"订单发票商品表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_invoice_id", columns={"invoice_id"}),
 *         @ORM\Index(name="idx_invoice_apply_bn", columns={"invoice_apply_bn"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderInvoiceItemRepository")
 */
class OrderInvoiceItem
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
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="invoice_id", type="bigint", options={"comment":"关联发票表id"})
     */
    private $invoice_id;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_apply_bn", type="string", length=64, options={"comment":"发票申请单号"})
     */
    private $invoice_apply_bn;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="oid", type="string", length=64, nullable=true, options={"comment":"OMS系统订单ID"})
     */
    private $oid;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_bn", type="string", length=64, nullable=true, options={"comment":"商品编码"})
     */
    private $item_bn;

    /**
     * @var string
     *
     * @ORM\Column(name="main_img", type="string", length=255, nullable=true, options={"comment":"商品主图"})
     */
    private $main_img;

    /**
     * @var string
     *
     * @ORM\Column(name="spec_info", type="string", nullable=true, length=255, options={"comment":"商品规格"})
     */
    private $spec_info;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", type="text", nullable=true, options={"comment":"商品规格描述"})
     */
    private $item_spec_desc;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"comment":"数量"})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="amount", type="integer", options={"comment":"金额，以分为单位"})
     */
    private $amount;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_tax_rate", type="string", length=16, nullable=true, options={"comment":"发票税率，如13%"})
     */
    private $invoice_tax_rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_num", type="integer", nullable=true, options={"comment":"原始数量"})
     */
    private $original_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="original_amount", type="integer", nullable=true, options={"comment":"原始金额，以分为单位"})
     */
    private $original_amount;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return OrderInvoiceItem
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return OrderInvoiceItem
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
     * Set invoiceId
     *
     * @param integer $invoiceId
     *
     * @return OrderInvoiceItem
     */
    public function setInvoiceId($invoiceId)
    {
        $this->invoice_id = $invoiceId;

        return $this;
    }

    /**
     * Get invoiceId
     *
     * @return integer
     */
    public function getInvoiceId()
    {
        return $this->invoice_id;
    }

    /**
     * Set invoiceApplyBn
     *
     * @param string $invoiceApplyBn
     *
     * @return OrderInvoiceItem
     */
    public function setInvoiceApplyBn($invoiceApplyBn)
    {
        $this->invoice_apply_bn = $invoiceApplyBn;

        return $this;
    }

    /**
     * Get invoiceApplyBn
     *
     * @return string
     */
    public function getInvoiceApplyBn()
    {
        return $this->invoice_apply_bn;
    }

    /**
     * Set orderId
     *
     * @param string $orderId
     *
     * @return OrderInvoiceItem
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set oid
     *
     * @param string $oid
     *
     * @return OrderInvoiceItem
     */
    public function setOid($oid = null)
    {
        $this->oid = $oid;

        return $this;
    }

    /**
     * Get oid
     *
     * @return string
     */
    public function getOid()
    {
        return $this->oid;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return OrderInvoiceItem
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set itemBn
     *
     * @param string $itemBn
     *
     * @return OrderInvoiceItem
     */
    public function setItemBn($itemBn = null)
    {
        $this->item_bn = $itemBn;

        return $this;
    }

    /**
     * Get itemBn
     *
     * @return string
     */
    public function getItemBn()
    {
        return $this->item_bn;
    }

    /**
     * Set mainImg
     *
     * @param string $mainImg
     *
     * @return OrderInvoiceItem
     */
    public function setMainImg($mainImg = null)
    {
        $this->main_img = $mainImg;

        return $this;
    }

    /**
     * Get mainImg
     *
     * @return string
     */
    public function getMainImg()
    {
        return $this->main_img;
    }

    /**
     * Set specInfo
     *
     * @param string $specInfo
     *
     * @return OrderInvoiceItem
     */
    public function setSpecInfo($specInfo)
    {
        $this->spec_info = $specInfo;

        return $this;
    }

    /**
     * Get specInfo
     *
     * @return string
     */
    public function getSpecInfo()
    {
        return $this->spec_info;
    }

    /**
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return OrderInvoiceItem
     */
    public function setItemSpecDesc($itemSpecDesc = null)
    {
        $this->item_spec_desc = $itemSpecDesc;

        return $this;
    }

    /**
     * Get itemSpecDesc
     *
     * @return string
     */
    public function getItemSpecDesc()
    {
        return $this->item_spec_desc;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return OrderInvoiceItem
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set amount
     *
     * @param integer $amount
     *
     * @return OrderInvoiceItem
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * Get amount
     *
     * @return integer
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set invoiceTaxRate
     *
     * @param string $invoiceTaxRate
     *
     * @return OrderInvoiceItem
     */
    public function setInvoiceTaxRate($invoiceTaxRate = null)
    {
        $this->invoice_tax_rate = $invoiceTaxRate;

        return $this;
    }

    /**
     * Get invoiceTaxRate
     *
     * @return string
     */
    public function getInvoiceTaxRate()
    {
        return $this->invoice_tax_rate;
    }

    /**
     * Set originalNum
     *
     * @param integer $originalNum
     *
     * @return OrderInvoiceItem
     */
    public function setOriginalNum($originalNum = null)
    {
        $this->original_num = $originalNum;

        return $this;
    }

    /**
     * Get originalNum
     *
     * @return integer
     */
    public function getOriginalNum()
    {
        return $this->original_num;
    }

    /**
     * Set originalAmount
     *
     * @param integer $originalAmount
     *
     * @return OrderInvoiceItem
     */
    public function setOriginalAmount($originalAmount = null)
    {
        $this->original_amount = $originalAmount;

        return $this;
    }

    /**
     * Get originalAmount
     *
     * @return integer
     */
    public function getOriginalAmount()
    {
        return $this->original_amount;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     *
     * @return OrderInvoiceItem
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return \DateTime
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param \DateTime $updateTime
     *
     * @return OrderInvoiceItem
     */
    public function setUpdateTime($updateTime = null)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return \DateTime
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }
}
