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
 * OfflinePayment 线下转账支付订单
 *
 * @ORM\Table(name="offline_payment", options={"comment":"线下转账支付订单"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_create_time", columns={"create_time"}),
 *         @ORM\Index(name="idx_check_status", columns={"check_status"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OfflinePaymentRepository")
 */
class OfflinePayment
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
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="bigint", options={"unsigned":true, "comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="pay_fee", type="bigint", options={"unsigned":true, "comment":"支付金额，以分为单位"})
     */
    private $pay_fee;

    /**
     * @var integer
     * 0 待处理
     * 1 已审核
     * 2 已拒绝
     * 9 已取消
     * @ORM\Column(name="check_status", type="smallint", options={"default": 0, "comment":"审核状态。可选值有 0 待处理;1 已审核;2 已拒绝;9 已取消"})
     */
    private $check_status = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="bank_account_id", type="bigint", options={"comment":"收款账户id"})
     */
    private $bank_account_id;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_name", type="string", length=50, options={"comment":"收款账户名称"})
     */
    private $bank_account_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_account_no", type="string", length=30, options={"comment":"银行账号"})
     */
    private $bank_account_no;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", type="string", length=100, options={"comment":"开户银行"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="china_ums_no", type="string", length=20, options={"comment":"银联号"})
     */
    private $china_ums_no;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_account_name", type="string", nullable=true, length=100, options={"default":"", "comment":"付款账户名"})
     */
    private $pay_account_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="pay_account_bank", type="string", nullable=true, length=100, options={"default":"", "comment":"付款银行"})
     */
    private $pay_account_bank = '';

    /**
     * @var string
     *
     * @ORM\Column(name="pay_account_no", type="string", nullable=true, length=100, options={"default":"", "comment":"付款账号"})
     */
    private $pay_account_no = '';

    /**
     * @var string
     *
     * @ORM\Column(name="pay_sn", type="string", nullable=true, length=100, options={"default":"", "comment":"付款流水单号"})
     */
    private $pay_sn = '';

    /**
     * @var string
     *
     * @ORM\Column(name="voucher_pic", type="json_array", options={"comment":"付款凭证图片"})
     */
    private $voucher_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="transfer_remark", type="string", nullable=true, length=100, options={"default":"", "comment":"转账备注"})
     */
    private $transfer_remark = '';

    /**
     * @var string
     *
     * @ORM\Column(name="operator_name", type="string", nullable=true, length=50, options={"default":"", "comment":"审核人"})
     */
    private $operator_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="string", nullable=true, length=500, options={"default":"", "comment":"审核备注"})
     */
    private $remark = '';

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
        return $this->id;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return OfflinePayment
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
     * @return OfflinePayment
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
     * Set userId.
     *
     * @param int $userId
     *
     * @return OfflinePayment
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set shopId.
     *
     * @param int|null $shopId
     *
     * @return OfflinePayment
     */
    public function setShopId($shopId = null)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return int|null
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return OfflinePayment
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set totalFee.
     *
     * @param int $totalFee
     *
     * @return OfflinePayment
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee.
     *
     * @return int
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set payFee.
     *
     * @param int $payFee
     *
     * @return OfflinePayment
     */
    public function setPayFee($payFee)
    {
        $this->pay_fee = $payFee;

        return $this;
    }

    /**
     * Get payFee.
     *
     * @return int
     */
    public function getPayFee()
    {
        return $this->pay_fee;
    }

    /**
     * Set checkStatus.
     *
     * @param int $checkStatus
     *
     * @return OfflinePayment
     */
    public function setCheckStatus($checkStatus)
    {
        $this->check_status = $checkStatus;

        return $this;
    }

    /**
     * Get checkStatus.
     *
     * @return int
     */
    public function getCheckStatus()
    {
        return $this->check_status;
    }

    /**
     * Set bankAccountId.
     *
     * @param int $bankAccountId
     *
     * @return OfflinePayment
     */
    public function setBankAccountId($bankAccountId)
    {
        $this->bank_account_id = $bankAccountId;

        return $this;
    }

    /**
     * Get bankAccountId.
     *
     * @return int
     */
    public function getBankAccountId()
    {
        return $this->bank_account_id;
    }

    /**
     * Set bankAccountName.
     *
     * @param string $bankAccountName
     *
     * @return OfflinePayment
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
     * @return OfflinePayment
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
     * @return OfflinePayment
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
     * Set chinaUmsNo.
     *
     * @param string $chinaUmsNo
     *
     * @return OfflinePayment
     */
    public function setChinaUmsNo($chinaUmsNo)
    {
        $this->china_ums_no = $chinaUmsNo;

        return $this;
    }

    /**
     * Get chinaUmsNo.
     *
     * @return string
     */
    public function getChinaUmsNo()
    {
        return $this->china_ums_no;
    }

    /**
     * Set payAccountName.
     *
     * @param string|null $payAccountName
     *
     * @return OfflinePayment
     */
    public function setPayAccountName($payAccountName = null)
    {
        $this->pay_account_name = $payAccountName;

        return $this;
    }

    /**
     * Get payAccountName.
     *
     * @return string|null
     */
    public function getPayAccountName()
    {
        return $this->pay_account_name;
    }

    /**
     * Set payAccountBank.
     *
     * @param string|null $payAccountBank
     *
     * @return OfflinePayment
     */
    public function setPayAccountBank($payAccountBank = null)
    {
        $this->pay_account_bank = $payAccountBank;

        return $this;
    }

    /**
     * Get payAccountBank.
     *
     * @return string|null
     */
    public function getPayAccountBank()
    {
        return $this->pay_account_bank;
    }

    /**
     * Set payAccountNo.
     *
     * @param string|null $payAccountNo
     *
     * @return OfflinePayment
     */
    public function setPayAccountNo($payAccountNo = null)
    {
        $this->pay_account_no = $payAccountNo;

        return $this;
    }

    /**
     * Get payAccountNo.
     *
     * @return string|null
     */
    public function getPayAccountNo()
    {
        return $this->pay_account_no;
    }

    /**
     * Set paySn.
     *
     * @param string|null $paySn
     *
     * @return OfflinePayment
     */
    public function setPaySn($paySn = null)
    {
        $this->pay_sn = $paySn;

        return $this;
    }

    /**
     * Get paySn.
     *
     * @return string|null
     */
    public function getPaySn()
    {
        return $this->pay_sn;
    }

    /**
     * Set voucherPic.
     *
     * @param array $voucherPic
     *
     * @return OfflinePayment
     */
    public function setVoucherPic($voucherPic)
    {
        $this->voucher_pic = $voucherPic;

        return $this;
    }

    /**
     * Get voucherPic.
     *
     * @return array
     */
    public function getVoucherPic()
    {
        return $this->voucher_pic;
    }

    /**
     * Set transferRemark.
     *
     * @param string|null $transferRemark
     *
     * @return OfflinePayment
     */
    public function setTransferRemark($transferRemark = null)
    {
        $this->transfer_remark = $transferRemark;

        return $this;
    }

    /**
     * Get transferRemark.
     *
     * @return string|null
     */
    public function getTransferRemark()
    {
        return $this->transfer_remark;
    }

    /**
     * Set operatorName.
     *
     * @param string|null $operatorName
     *
     * @return OfflinePayment
     */
    public function setOperatorName($operatorName = null)
    {
        $this->operator_name = $operatorName;

        return $this;
    }

    /**
     * Get operatorName.
     *
     * @return string|null
     */
    public function getOperatorName()
    {
        return $this->operator_name;
    }

    /**
     * Set remark.
     *
     * @param string|null $remark
     *
     * @return OfflinePayment
     */
    public function setRemark($remark = null)
    {
        $this->remark = $remark;

        return $this;
    }

    /**
     * Get remark.
     *
     * @return string|null
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return OfflinePayment
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
     * @return OfflinePayment
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
