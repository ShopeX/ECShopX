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

namespace AftersalesBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * AftersalesOfflineRefund 线下转账退款
 *
 * @ORM\Table(name="aftersales_offline_refund", options={"comment":"线下转账退款"},
 *     indexes={
 *         @ORM\Index(name="idx_refund_bn", columns={"refund_bn"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AftersalesBundle\Repositories\AftersalesOfflineRefundRepository")
 */
class AftersalesOfflineRefund
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"自增ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_bn", type="bigint", options={"comment":"申请退款单号"})
     */
    private $refund_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_fee", type="integer", options={"unsigned":true, "comment":"应退金额，以分为单位，非积分支付"})
     */
    private $refund_fee;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_name", type="string", length=50, options={"comment":"收款账户名称"})
     */
    private $bank_account_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_no", type="string", length=30, options={"comment":"收款银行账号"})
     */
    private $bank_account_no;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=100, options={"comment":"收款开户银行"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="refund_account_name", type="string", nullable=true, length=100, options={"default":"", "comment":"退款账户名"})
     */
    private $refund_account_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="refund_account_bank", type="string", nullable=true, length=100, options={"default":"", "comment":"退款银行"})
     */
    private $refund_account_bank = '';

    /**
     * @var string
     *
     * @ORM\Column(name="refund_account_no", type="string", nullable=true, length=100, options={"default":"", "comment":"退款账号"})
     */
    private $refund_account_no = '';

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $update_time;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // Powered by ShopEx EcShopX
        return $this->id;
    }

    /**
     * Set refundBn.
     *
     * @param int $refundBn
     *
     * @return AftersalesOfflineRefund
     */
    public function setRefundBn($refundBn)
    {
        $this->refund_bn = $refundBn;

        return $this;
    }

    /**
     * Get refundBn.
     *
     * @return int
     */
    public function getRefundBn()
    {
        return $this->refund_bn;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return AftersalesOfflineRefund
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return int
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AftersalesOfflineRefund
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
     * Set refundFee.
     *
     * @param int $refundFee
     *
     * @return AftersalesOfflineRefund
     */
    public function setRefundFee($refundFee)
    {
        $this->refund_fee = $refundFee;

        return $this;
    }

    /**
     * Get refundFee.
     *
     * @return int
     */
    public function getRefundFee()
    {
        return $this->refund_fee;
    }

    /**
     * Set bankAccountName.
     *
     * @param string $bankAccountName
     *
     * @return AftersalesOfflineRefund
     */
    public function setBankAccountName($bankAccountName)
    {
        $this->bank_account_name = $bankAccountName;

        return $this;
    }

    /**
     * Get bankAccountName.
     *
     * @return string
     */
    public function getBankAccountName()
    {
        return $this->bank_account_name;
    }

    /**
     * Set bankAccountNo.
     *
     * @param string $bankAccountNo
     *
     * @return AftersalesOfflineRefund
     */
    public function setBankAccountNo($bankAccountNo)
    {
        $this->bank_account_no = $bankAccountNo;

        return $this;
    }

    /**
     * Get bankAccountNo.
     *
     * @return string
     */
    public function getBankAccountNo()
    {
        return $this->bank_account_no;
    }

    /**
     * Set bankName.
     *
     * @param string $bankName
     *
     * @return AftersalesOfflineRefund
     */
    public function setBankName($bankName)
    {
        $this->bank_name = $bankName;

        return $this;
    }

    /**
     * Get bankName.
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set refundAccountName.
     *
     * @param string|null $refundAccountName
     *
     * @return AftersalesOfflineRefund
     */
    public function setRefundAccountName($refundAccountName = null)
    {
        $this->refund_account_name = $refundAccountName;

        return $this;
    }

    /**
     * Get refundAccountName.
     *
     * @return string|null
     */
    public function getRefundAccountName()
    {
        return $this->refund_account_name;
    }

    /**
     * Set refundAccountBank.
     *
     * @param string|null $refundAccountBank
     *
     * @return AftersalesOfflineRefund
     */
    public function setRefundAccountBank($refundAccountBank = null)
    {
        $this->refund_account_bank = $refundAccountBank;

        return $this;
    }

    /**
     * Get refundAccountBank.
     *
     * @return string|null
     */
    public function getRefundAccountBank()
    {
        return $this->refund_account_bank;
    }

    /**
     * Set refundAccountNo.
     *
     * @param string|null $refundAccountNo
     *
     * @return AftersalesOfflineRefund
     */
    public function setRefundAccountNo($refundAccountNo = null)
    {
        $this->refund_account_no = $refundAccountNo;

        return $this;
    }

    /**
     * Get refundAccountNo.
     *
     * @return string|null
     */
    public function getRefundAccountNo()
    {
        return $this->refund_account_no;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AftersalesOfflineRefund
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
     * @return AftersalesOfflineRefund
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
