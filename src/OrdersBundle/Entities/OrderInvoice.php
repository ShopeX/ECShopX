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
 * OrderInvoice 订单发票表
 *
 * @ORM\Table(name="orders_invoice", options={"comment":"订单发票表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_invoice_apply_bn", columns={"invoice_apply_bn"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderInvoiceRepository")
 */
class OrderInvoice
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
     * @var string
     *
     * @ORM\Column(name="invoice_apply_bn", type="string", length=64, options={"comment":"发票申请单号"})
     */
    private $invoice_apply_bn;

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
     * @ORM\Column(name="regionauth_id", type="bigint", nullable=true, options={"comment":"区域id"})
     */
    private $regionauth_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=255, options={"comment":"订单id，多订单以英文逗号分隔"})
     */
    private $order_id;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_type", type="string", length=20, options={"comment":"开票类型，企业enterprise,和个人individual"})
     */
    private $invoice_type;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_type_code", type="string", length=20, options={"comment":"开票类型编码，01:增值税专用发票,02:增值税普通发票"})
     */
    private $invoice_type_code;

    /**
     * @var string
     *
     * @ORM\Column(name="company_title", type="string", length=255, options={"comment":"公司抬头"})
     */
    private $company_title;

    /**
     * @var string
     *
     * @ORM\Column(name="company_tax_number", type="string", length=64, nullable=true, options={"comment":"公司税号"})
     */
    private $company_tax_number;

    /**
     * @var string
     *
     * @ORM\Column(name="company_address", type="string", length=255, nullable=true, options={"comment":"公司地址"})
     */
    private $company_address;

    /**
     * @var string
     *
     * @ORM\Column(name="company_telephone", type="string", length=20, nullable=true, options={"comment":"公司电话"})
     */
    private $company_telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=255, nullable=true, options={"comment":"开户银行"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account", type="string", length=64, nullable=true, options={"comment":"开户账号"})
     */
    private $bank_account;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=true, options={"comment":"电子邮箱"})
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=20, nullable=true, options={"comment":"手机号码"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_status", type="string", length=20, options={"comment":"开票状态：待开票：pending，开票中：inProgress，开票成功：success，已作废：waste，开票失败：failed", "default":"pending"})
     */
    private $invoice_status = 'pending';

    /**
     * @var integer
     *
     * @ORM\Column(name="try_times", type="integer", options={"default":0, "comment":"重试次数"})
     */
    private $try_times = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="invoice_amount", type="integer", nullable=true, options={"comment":"开票金额，以分为单位", "default":"0"})
     */
    private $invoice_amount=0;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_file_url", type="string", length=255, nullable=true, options={"comment":"发票文件地址"})
     */
    private $invoice_file_url;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_file_url_red", type="string", length=255, nullable=true, options={"comment":"红票文件地址"})
     */
    private $invoice_file_url_red;

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_method", type="string", length=20, options={"comment":"开票类型：线上和线下", "default":"online"})
     */
    private $invoice_method = 'online';

    /**
     * @var string
     *
     * @ORM\Column(name="invoice_source", type="string", length=20, options={"comment":"开票来源：user客户端,oms"})
     */
    private $invoice_source = 'user';

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", length=255, nullable=true, options={"comment":"备注"})
     */
    private $remark;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_oms", type="integer", options={"comment":"是否推送OMS，0是未推送，1是已推送", "default":0})
     */
    private $is_oms = 0;

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
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", nullable=true, options={"comment":"订单完成时间"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="close_aftersales_time", type="integer", nullable=true, options={"comment":"售后截止时间"})
     */
    private $close_aftersales_time;

    /**
     * @var string
     *
     * @ORM\Column(name="query_content", type="json_array", nullable=true, options={"comment":"查询内容"})
     */
    private $query_content;    

    /**
     * @var string
     *
     * @ORM\Column(name="red_content", type="json_array", nullable=true, options={"comment":"冲红内容"})
     */
    private $red_content;    

    //SerialNo
    /**
     * @var string
     *
     * @ORM\Column(name="serial_no", type="string", length=255, nullable=true, options={"comment":"发票流水号"})
     */
    private $serial_no;    

    // 红冲流水号
    /**
     * @var string
     *
     * @ORM\Column(name="red_serial_no", type="string", length=255, nullable=true, options={"comment":"红冲流水号"})
     */
    private $red_serial_no;    

    // 红冲申请单
    /**
     * @var string
     *
     * @ORM\Column(name="red_apply_bn", type="string", length=255, nullable=true, options={"comment":"红冲申请单号"})
     */
    private $red_apply_bn;    

    // 订单店铺 id
    /**
     * @var string
     *
     * @ORM\Column(name="order_shop_id", type="string", length=20, nullable=true, options={"comment":"订单店铺id"})
     */
    private $order_shop_id;    

    //user_card_code
    /**
     * @var string
     *
     * @ORM\Column(name="user_card_code", type="string", length=50, nullable=true, options={"comment":"用户卡号"})
     */
    private $user_card_code;    

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // EcShopX core
        return $this->id;
    }

    /**
     * Set invoiceApplyBn
     *
     * @param string $invoiceApplyBn
     *
     * @return OrderInvoice
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return OrderInvoice
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
     * @return OrderInvoice
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
     * Set regionauthId
     *
     * @param integer $regionauthId
     *
     * @return OrderInvoice
     */
    public function setRegionauthId($regionauthId = null)
    {
        $this->regionauth_id = $regionauthId;

        return $this;
    }

    /**
     * Get regionauthId
     *
     * @return integer
     */
    public function getRegionauthId()
    {
        return $this->regionauth_id;
    }

    /**
     * Set orderId
     *
     * @param string $orderId
     *
     * @return OrderInvoice
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
     * Set invoiceType
     *
     * @param string $invoiceType
     *
     * @return OrderInvoice
     */
    public function setInvoiceType($invoiceType)
    {
        $this->invoice_type = $invoiceType;

        return $this;
    }

    /**
     * Get invoiceType
     *
     * @return string
     */
    public function getInvoiceType()
    {
        return $this->invoice_type;
    }

    /**
     * Set companyTitle
     *
     * @param string $companyTitle
     *
     * @return OrderInvoice
     */
    public function setCompanyTitle($companyTitle)
    {
        $this->company_title = $companyTitle;

        return $this;
    }

    /**
     * Get companyTitle
     *
     * @return string
     */
    public function getCompanyTitle()
    {
        return $this->company_title;
    }

    /**
     * Set companyTaxNumber
     *
     * @param string $companyTaxNumber
     *
     * @return OrderInvoice
     */
    public function setCompanyTaxNumber($companyTaxNumber = null)
    {
        $this->company_tax_number = $companyTaxNumber;

        return $this;
    }

    /**
     * Get companyTaxNumber
     *
     * @return string
     */
    public function getCompanyTaxNumber()
    {
        return $this->company_tax_number;
    }

    /**
     * Set companyAddress
     *
     * @param string $companyAddress
     *
     * @return OrderInvoice
     */
    public function setCompanyAddress($companyAddress = null)
    {
        $this->company_address = $companyAddress;

        return $this;
    }

    /**
     * Get companyAddress
     *
     * @return string
     */
    public function getCompanyAddress()
    {
        return $this->company_address;
    }

    /**
     * Set companyTelephone
     *
     * @param string $companyTelephone
     *
     * @return OrderInvoice
     */
    public function setCompanyTelephone($companyTelephone = null)
    {
        $this->company_telephone = $companyTelephone;

        return $this;
    }

    /**
     * Get companyTelephone
     *
     * @return string
     */
    public function getCompanyTelephone()
    {
        return $this->company_telephone;
    }

    /**
     * Set bankName
     *
     * @param string $bankName
     *
     * @return OrderInvoice
     */
    public function setBankName($bankName = null)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set bankAccount
     *
     * @param string $bankAccount
     *
     * @return OrderInvoice
     */
    public function setBankAccount($bankAccount = null)
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
     * Set email
     *
     * @param string $email
     *
     * @return OrderInvoice
     */
    public function setEmail($email = null)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return OrderInvoice
     */
    public function setMobile($mobile = null)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set invoiceStatus
     *
     * @param string $invoiceStatus
     *
     * @return OrderInvoice
     */
    public function setInvoiceStatus($invoiceStatus)
    {
        $this->invoice_status = $invoiceStatus;

        return $this;
    }

    /**
     * Get invoiceStatus
     *
     * @return string
     */
    public function getInvoiceStatus()
    {
        return $this->invoice_status;
    }

    /**
     * Set tryTimes
     *
     * @param integer $tryTimes
     *
     * @return OrderInvoice
     */
    public function setTryTimes($tryTimes)
    {
        $this->try_times = $tryTimes;

        return $this;
    }

    /**
     * Get tryTimes
     *
     * @return integer
     */
    public function getTryTimes()
    {
        return $this->try_times;
    }

    /**
     * Set invoiceAmount
     *
     * @param integer $invoiceAmount
     *
     * @return OrderInvoice
     */
    public function setInvoiceAmount($invoiceAmount)
    {
        $this->invoice_amount = $invoiceAmount;

        return $this;
    }

    /**
     * Get invoiceAmount
     *
     * @return integer
     */
    public function getInvoiceAmount()
    {
        return $this->invoice_amount;
    }

    /**
     * Set invoiceFileUrl
     *
     * @param string $invoiceFileUrl
     *
     * @return OrderInvoice
     */
    public function setInvoiceFileUrl($invoiceFileUrl = null)
    {
        $this->invoice_file_url = $invoiceFileUrl;

        return $this;
    }

    /**
     * Get invoiceFileUrl
     *
     * @return string
     */
    public function getInvoiceFileUrl()
    {
        return $this->invoice_file_url;
    }

    /**
     * Set invoiceFileUrlRed
     *
     * @param string $invoiceFileUrlRed
     *
     * @return OrderInvoice
     */
    public function setInvoiceFileUrlRed($invoiceFileUrlRed = null)
    {
        $this->invoice_file_url_red = $invoiceFileUrlRed;

        return $this;
    }

    /**
     * Get invoiceFileUrlRed
     *
     * @return string
     */
    public function getInvoiceFileUrlRed()
    {
        return $this->invoice_file_url_red;
    }

    /**
     * Set invoiceMethod
     *
     * @param string $invoiceMethod
     *
     * @return OrderInvoice
     */
    public function setInvoiceMethod($invoiceMethod)
    {
        $this->invoice_method = $invoiceMethod;

        return $this;
    }

    /**
     * Get invoiceMethod
     *
     * @return string
     */
    public function getInvoiceMethod()
    {
        return $this->invoice_method;
    }

    /**
     * Set invoiceSource
     *
     * @param string $invoiceSource
     *
     * @return OrderInvoice
     */
    public function setInvoiceSource($invoiceSource)
    {
        $this->invoice_source = $invoiceSource;

        return $this;
    }

    /**
     * Get invoiceSource
     *
     * @return string
     */
    public function getInvoiceSource()
    {
        return $this->invoice_source;
    }

    /**
     * Set remark
     *
     * @param string $remark
     *
     * @return OrderInvoice
     */
    public function setRemark($remark = null)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark
     *
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set isOms
     *
     * @param integer $isOms
     *
     * @return OrderInvoice
     */
    public function setIsOms($isOms)
    {
        $this->is_oms = $isOms;

        return $this;
    }

    /**
     * Get isOms
     *
     * @return integer
     */
    public function getIsOms()
    {
        return $this->is_oms;
    }

    /**
     * Set createTime
     *
     * @param \DateTime $createTime
     *
     * @return OrderInvoice
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
     * @return OrderInvoice
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

    /**
     * invoice_type_code 01:增值税专用发票,02:增值税普通发票
     */
    public function getInvoiceTypeCode()
    {
        return $this->invoice_type_code;
    }

    /**
     * invoice_type_code 01:增值税专用发票,02:增值税普通发票
     */
    public function setInvoiceTypeCode($invoiceTypeCode)
    {
        $this->invoice_type_code = $invoiceTypeCode;

        return $this;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return OrderInvoice
     */
    public function setEndTime($endTime = null)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set closeAftersalesTime
     *
     * @param integer $closeAftersalesTime
     *
     * @return OrderInvoice
     */
    public function setCloseAftersalesTime($closeAftersalesTime = null)
    {
        $this->close_aftersales_time = $closeAftersalesTime;

        return $this;
    }

    /**
     * Get closeAftersalesTime
     *
     * @return integer
     */
    public function getCloseAftersalesTime()
    {
        return $this->close_aftersales_time;
    }

    /**
     * Set queryContent
     *
     * @param array $queryContent
     *
     * @return OrderInvoice
     */
    public function setQueryContent($queryContent = null)
    {
        $this->query_content = $queryContent;

        return $this;
    }

    /**
     * Get queryContent
     *
     * @return array
     */
    public function getQueryContent()
    {
        return $this->query_content;
    }

    /**
     * Set redContent
     *
     * @param array $redContent
     *
     * @return OrderInvoice
     */
    public function setRedContent($redContent = null)
    {
        $this->red_content = $redContent;

        return $this;
    }

    /**
     * Get redContent
     *
     * @return array
     */
    public function getRedContent()
    {
        return $this->red_content;
    }

    /**
     * Set serialNo
     *
     * @param string $serialNo
     *
     * @return OrderInvoice
     */
    public function setSerialNo($serialNo = null)
    {
        $this->serial_no = $serialNo;

        return $this;
    }

    /**
     * Get serialNo
     *
     * @return string
     */
    public function getSerialNo()
    {
        return $this->serial_no;
    }

    /**
     * Set redApplyBn
     *
     * @param string $redApplyBn
     *
     * @return OrderInvoice
     */
    public function setRedApplyBn($redApplyBn = null)
    {
        $this->red_apply_bn = $redApplyBn;

        return $this;
    }

    /**
     * Get redApplyBn
     *
     * @return string
     */
    public function getRedApplyBn()
    {
        return $this->red_apply_bn;
    }

    /**
     * Set orderShopId
     *
     * @param string $orderShopId
     *
     * @return OrderInvoice
     */
    public function setOrderShopId($orderShopId = null)
    {
        $this->order_shop_id = $orderShopId;

        return $this;
    }

    /**
     * Get orderShopId
     *
     * @return string
     */
    public function getOrderShopId()
    {
        return $this->order_shop_id;
    }

    /**
     * Set userCardCode
     *
     * @param string $userCardCode
     *
     * @return OrderInvoice
     */
    public function setUserCardCode($userCardCode = null)
    {
        $this->user_card_code = $userCardCode;

        return $this;
    }

    /**
     * Get userCardCode
     *
     * @return string
     */
    public function getUserCardCode()
    {
        return $this->user_card_code;
    }

    /**
     * Set redSerialNo
     *
     * @param string $redSerialNo
     *
     * @return OrderInvoice
     */
    public function setRedSerialNo($redSerialNo = null)
    {
        $this->red_serial_no = $redSerialNo;

        return $this;
    }

    /**
     * Get redSerialNo
     *
     * @return string
     */
    public function getRedSerialNo()
    {
        return $this->red_serial_no;
    }
}
