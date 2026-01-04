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

namespace SupplierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Supplier 供应商
 *
 * @ORM\Table(name="supplier", options={"comment":"供应商"},
 *     indexes={
 *         @ORM\Index(name="idx_supplier_name", columns={"supplier_name"}),
 *         @ORM\Index(name="idx_mobile", columns={"mobile"}),
 *         @ORM\Index(name="idx_is_check", columns={"is_check"}),
 *     }
 * )
 * @ORM\Entity(repositoryClass="SupplierBundle\Repositories\SupplierRepository")
 */
class Supplier
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"商户id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="supplier_name", type="string", length=100, nullable=true, options={"comment":"供应商名称"})
     */
    private $supplier_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="contact", type="string", length=30, nullable=true, options={"comment":"联系人", "default":""})
     */
    private $contact;

    /**
     * @var integer
     *
     * @ORM\Column(name="mobile", type="string", length=30, nullable=true, options={"comment":"手机号", "default":""})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="business_license", type="string", length=200, nullable=true, options={"comment":"营业执照"})
     */
    private $business_license;

    /**
     * @var string
     *
     * @ORM\Column(name="wechat_qrcode", type="string", length=200, nullable=true, options={"comment":"企业微信二维码"})
     */
    private $wechat_qrcode;

    /**
     * @var string
     *
     * @ORM\Column(name="service_tel", type="string", length=30, nullable=true, options={"comment":"客服电话"})
     */
    private $service_tel;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=50, nullable=true, options={"comment":"收款银行"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account", type="string", length=50, nullable=true, options={"comment":"收款账号"})
     */
    private $bank_account;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_check", type="bigint", options={"comment"="是否审核"})
     */
    private $is_check;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_remark", type="string", length=500, nullable=true, options={"comment":"审核备注"})
     */
    private $audit_remark;

    /**
     * @var string
     *
     * @ORM\Column(name="adapay_mch_id", type="string", length=50, nullable=true, options={"comment":"汇付商户ID"})
     */
    private $adapay_mch_id;

    /**
     * @var string
     *
     * @ORM\Column(name="wx_openid", type="string", length=100, nullable=true, options={"comment":"微信公众号openid"})
     */
    private $wx_openid;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"管理员id"})
     */
    private $operator_id;

    /**
     * @var \DateTime $add_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime", nullable=true, options={"comment":"创建时间"})
     */
    private $add_time;

    /**
     * @var \DateTime $modify_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="datetime", nullable=true, options={"comment":"更新时间"})
     */
    private $modify_time;

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
     * Set supplierName.
     *
     * @param string|null $supplierName
     *
     * @return Supplier
     */
    public function setSupplierName($supplierName = null)
    {
        $this->supplier_name = $supplierName;

        return $this;
    }

    /**
     * Get supplierName.
     *
     * @return string|null
     */
    public function getSupplierName()
    {
        // ModuleID: 76fe2a3d
        return $this->supplier_name;
    }

    /**
     * Set contact.
     *
     * @param string|null $contact
     *
     * @return Supplier
     */
    public function setContact($contact = null)
    {
        $this->contact = $contact;

        return $this;
    }

    /**
     * Get contact.
     *
     * @return string|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set mobile.
     *
     * @param string|null $mobile
     *
     * @return Supplier
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
     * Set businessLicense.
     *
     * @param string|null $businessLicense
     *
     * @return Supplier
     */
    public function setBusinessLicense($businessLicense = null)
    {
        $this->business_license = $businessLicense;

        return $this;
    }

    /**
     * Get businessLicense.
     *
     * @return string|null
     */
    public function getBusinessLicense()
    {
        return $this->business_license;
    }

    /**
     * Set wechatQrcode.
     *
     * @param string|null $wechatQrcode
     *
     * @return Supplier
     */
    public function setWechatQrcode($wechatQrcode = null)
    {
        $this->wechat_qrcode = $wechatQrcode;

        return $this;
    }

    /**
     * Get wechatQrcode.
     *
     * @return string|null
     */
    public function getWechatQrcode()
    {
        return $this->wechat_qrcode;
    }

    /**
     * Set serviceTel.
     *
     * @param string|null $serviceTel
     *
     * @return Supplier
     */
    public function setServiceTel($serviceTel = null)
    {
        $this->service_tel = $serviceTel;

        return $this;
    }

    /**
     * Get serviceTel.
     *
     * @return string|null
     */
    public function getServiceTel()
    {
        return $this->service_tel;
    }

    /**
     * Set bankName.
     *
     * @param string|null $bankName
     *
     * @return Supplier
     */
    public function setBankName($bankName = null)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string|null
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set bankAccount.
     *
     * @param string|null $bankAccount
     *
     * @return Supplier
     */
    public function setBankAccount($bankAccount = null)
    {
        $this->bank_account = $bankAccount;

        return $this;
    }

    /**
     * Get bankAccount.
     *
     * @return string|null
     */
    public function getBankAccount()
    {
        return $this->bank_account;
    }

    /**
     * Set isCheck.
     *
     * @param int $isCheck
     *
     * @return Supplier
     */
    public function setIsCheck($isCheck)
    {
        $this->is_check = $isCheck;

        return $this;
    }

    /**
     * Get isCheck.
     *
     * @return int
     */
    public function getIsCheck()
    {
        return $this->is_check;
    }

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return Supplier
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
     * Set addTime.
     *
     * @param \DateTime|null $addTime
     *
     * @return Supplier
     */
    public function setAddTime($addTime = null)
    {
        $this->add_time = $addTime;

        return $this;
    }

    /**
     * Get addTime.
     *
     * @return \DateTime|null
     */
    public function getAddTime()
    {
        return $this->add_time;
    }

    /**
     * Set modifyTime.
     *
     * @param \DateTime|null $modifyTime
     *
     * @return Supplier
     */
    public function setModifyTime($modifyTime = null)
    {
        $this->modify_time = $modifyTime;

        return $this;
    }

    /**
     * Get modifyTime.
     *
     * @return \DateTime|null
     */
    public function getModifyTime()
    {
        return $this->modify_time;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Supplier
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
     * Set auditRemark.
     *
     * @param string|null $auditRemark
     *
     * @return Supplier
     */
    public function setAuditRemark($auditRemark = null)
    {
        $this->audit_remark = $auditRemark;

        return $this;
    }

    /**
     * Get auditRemark.
     *
     * @return string|null
     */
    public function getAuditRemark()
    {
        return $this->audit_remark;
    }

    /**
     * Set adapayMchId.
     *
     * @param string|null $adapayMchId
     *
     * @return Supplier
     */
    public function setAdapayMchId($adapayMchId = null)
    {
        $this->adapay_mch_id = $adapayMchId;

        return $this;
    }

    /**
     * Get adapayMchId.
     *
     * @return string|null
     */
    public function getAdapayMchId()
    {
        return $this->adapay_mch_id;
    }


    /**
     * Set wxOpenid.
     *
     * @param string|null $wxOpenid
     *
     * @return Supplier
     */
    public function setWxOpenid($wxOpenid = null)
    {
        $this->wx_openid = $wxOpenid;

        return $this;
    }

    /**
     * Get wxOpenid.
     *
     * @return string|null
     */
    public function getWxOpenid()
    {
        return $this->wx_openid;
    }
}
