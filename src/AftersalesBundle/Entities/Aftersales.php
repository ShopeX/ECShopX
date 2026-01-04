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
 * Aftersales 售后主表
 *
 * @ORM\Table(name="aftersales", options={"comment":"售后主表"},
 *     indexes={
 *         @ORM\Index(name="idx_aftersales_bn", columns={"aftersales_bn"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_merchant_id", columns={"merchant_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="AftersalesBundle\Repositories\AftersalesRepository")
 */
class Aftersales
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="aftersales_bn", type="bigint", options={"comment":"售后单号"})
     */
    private $aftersales_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="detail_id", type="bigint", options={"comment":"当前售后明细id"})
     */
    // private $detail_id;

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
     * @ORM\Column(name="salesman_id", nullable=true, type="bigint", options={"comment":"导购员id", "default": 0})
     */
    private $salesman_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="item_bn", type="string", nullable=true, options={"comment":"商品编码"})
     */
    private $item_bn;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"门店id","default":0})
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
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"商品名称"})
     */
    // private $item_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"unsigned":true, "comment":"购买商品数量"})
     */
    // private $num;

    /**
     * @var string
     *
     * ONLY_REFUND 仅退款
     * REFUND_GOODS 退货退款
     * EXCHANGING_GOODS 换货
     *
     * @ORM\Column(name="aftersales_type", type="string", options={"comment":"售后服务类型"})
     */
    private $aftersales_type;

    /**
     * @var integer
     *
     * 0 待处理。申请中
     * 1 处理中。接受申请，审核中等
     * 2 已处理。已完成
     * 3 已驳回。已拒绝
     * 4 已撤销。已关闭（取消售后）
     * @ORM\Column(name="aftersales_status", type="integer", options={"default": 0, "comment":"售后状态"})
     */
    private $aftersales_status = 0;

    /**
     * @var integer
     *
     * 0 等待商家处理
     * 1 商家接受申请，等待消费者回寄
     * 2 消费者回寄，等待商家收货确认
     * 8 商家确认收货,等待审核退款
     * 3 已驳回
     * 4 已处理
     * 7 已撤销。已关闭
     * 9 退款处理中
     * 5 退款驳回
     * 6 退款完成
     *
     * @ORM\Column(name="progress", type="integer", options={"default": 0, "comment":"处理进度"})
     */
    private $progress = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_fee", type="integer", options={"unsigned":true, "comment":"应退总金额，单位(分)"})
     */
    private $refund_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_point", type="integer", options={"unsigned":true, "comment":"应退总积分"})
     */
    private $refund_point;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=300, options={"comment":"申请售后原因"})
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="description", nullable=true, type="string", length=300, options={"comment":"申请描述"})
     */
    private $description;

    /**
     * @var json_array
     *
     * @ORM\Column(name="evidence_pic", nullable=true, type="simple_array", options={"comment":"图片凭证信息"})
     */
    private $evidence_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="refuse_reason", nullable=true, type="text", options={"comment":"拒绝原因"})
     */
    private $refuse_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="memo", nullable=true, type="string", length=300, options={"comment":"售后备注"})
     */
    private $memo;

    /**
     * @var json_array
     *
     * @ORM\Column(name="sendback_data", nullable=true, type="json_array", options={"comment":"消费者提交退货物流信息"})
     */
    private $sendback_data;

    /**
     * @var json_array
     *
     * @ORM\Column(name="sendconfirm_data", nullable=true, type="json_array", options={"comment":"商家重新发货物流信息"})
     */
    private $sendconfirm_data;

    /**
     * @var string
     *
     * @ORM\Column(name="third_data", nullable=true, type="string", options={"comment":"百胜等第三方返回的数据"})
     */
    private $third_data;

    /**
     * @var string
     *
     * @ORM\Column(name="aftersales_address", nullable=true, type="json_array", options={"comment":"售后回寄地址信息"})
     */
    private $aftersales_address;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_remark", type="string", length=255, nullable=false, options={"comment":"店务端商家备注", "default": ""})
     */
    private $distributor_remark = '';

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
     * @var string
     *
     * @ORM\Column(name="contact", length=500, type="string", nullable=true, options={"comment":"联系人"})
     */
    private $contact;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, nullable=true, options={"comment":"手机号"})
     */
    private $mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id;

    /**
     * @var string
     *
     * @ORM\Column(name="is_partial_cancel", type="boolean", nullable=true, options={"default":0, "comment":"是否部分取消订单退款"})
     */
    private $is_partial_cancel = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="return_type", type="string", length=20, options={"comment":"退货方式：logistics寄回 offline到店退", "default": "logistics"})
     */
    private $return_type = 'logistics';

    /**
     * @var integer
     *
     * @ORM\Column(name="return_distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"退货门店ID"})
     */
    private $return_distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="self_delivery_operator_id", type="bigint", nullable=true, options={"comment":"自配送员id", "default": 0})
     */
    private $self_delivery_operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="freight", type="integer",  nullable=true, options={"unsigned":true, "comment":"退款运费","default":0})
     */
    private $freight = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="freight_type", type="string", length=10, options={"default":"cash", "comment":"运费类型-用于积分商城 cash:现金 point:积分"})
     */
    private $freight_type = 'cash';

    public function setFreight($freight)
    {
        $this->freight = $freight;

        return $this;
    }

    public function getFreight()
    {
        return $this->freight;
    }

    /**
     * Set freightType.
     *
     * @param string $freightType
     *
     * @return Aftersales
     */
    public function setFreightType($freightType)
    {
        $this->freight_type = $freightType;

        return $this;
    }

    /**
     * Get freightType.
     *
     * @return string
     */
    public function getFreightType()
    {
        return $this->freight_type;
    }

    /**
     * Set aftersalesBn.
     *
     * @param int $aftersalesBn
     *
     * @return Aftersales
     */
    public function setAftersalesBn($aftersalesBn)
    {
        $this->aftersales_bn = $aftersalesBn;

        return $this;
    }

    /**
     * Get aftersalesBn.
     *
     * @return int
     */
    public function getAftersalesBn()
    {
        // EcShopX core
        return $this->aftersales_bn;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return Aftersales
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
     * @return Aftersales
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
     * @return Aftersales
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
     * Set itemBn
     *
     * @param string $itemBn
     *
     * @return Aftersales
     */
    public function setItemBn($itemBn)
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
     * Set shopId.
     *
     * @param int $shopId
     *
     * @return Aftersales
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId.
     *
     * @return int
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
     * @return Aftersales
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return Aftersales
     */
    public function setSupplierId($supplierId)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set aftersalesType.
     *
     * @param string $aftersalesType
     *
     * @return Aftersales
     */
    public function setAftersalesType($aftersalesType)
    {
        $this->aftersales_type = $aftersalesType;

        return $this;
    }

    /**
     * Get aftersalesType.
     *
     * @return string
     */
    public function getAftersalesType()
    {
        return $this->aftersales_type;
    }

    /**
     * Set aftersalesStatus.
     *
     * @param int $aftersalesStatus
     *
     * @return Aftersales
     */
    public function setAftersalesStatus($aftersalesStatus)
    {
        $this->aftersales_status = $aftersalesStatus;

        return $this;
    }

    /**
     * Get aftersalesStatus.
     *
     * @return int
     */
    public function getAftersalesStatus()
    {
        return $this->aftersales_status;
    }

    /**
     * Set progress.
     *
     * @param int $progress
     *
     * @return Aftersales
     */
    public function setProgress($progress)
    {
        $this->progress = $progress;

        return $this;
    }

    /**
     * Get progress.
     *
     * @return int
     */
    public function getProgress()
    {
        return $this->progress;
    }

    /**
     * Set refundFee.
     *
     * @param int $refundFee
     *
     * @return Aftersales
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
     * Set refundPoint.
     *
     * @param int $refundPoint
     *
     * @return Aftersales
     */
    public function setRefundPoint($refundPoint)
    {
        $this->refund_point = $refundPoint;

        return $this;
    }

    /**
     * Get refundPoint.
     *
     * @return int
     */
    public function getRefundPoint()
    {
        return $this->refund_point;
    }

    /**
     * Set reason.
     *
     * @param string $reason
     *
     * @return Aftersales
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason.
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return Aftersales
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set evidencePic.
     *
     * @param array|null $evidencePic
     *
     * @return Aftersales
     */
    public function setEvidencePic($evidencePic = null)
    {
        $this->evidence_pic = $evidencePic;

        return $this;
    }

    /**
     * Get evidencePic.
     *
     * @return array|null
     */
    public function getEvidencePic()
    {
        return $this->evidence_pic;
    }

    /**
     * Set refuseReason.
     *
     * @param string|null $refuseReason
     *
     * @return Aftersales
     */
    public function setRefuseReason($refuseReason = null)
    {
        $this->refuse_reason = $refuseReason;

        return $this;
    }

    /**
     * Get refuseReason.
     *
     * @return string|null
     */
    public function getRefuseReason()
    {
        return $this->refuse_reason;
    }

    /**
     * Set memo.
     *
     * @param string|null $memo
     *
     * @return Aftersales
     */
    public function setMemo($memo = null)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * Get memo.
     *
     * @return string|null
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * Set sendbackData.
     *
     * @param array|null $sendbackData
     *
     * @return Aftersales
     */
    public function setSendbackData($sendbackData = null)
    {
        $this->sendback_data = $sendbackData;

        return $this;
    }

    /**
     * Get sendbackData.
     *
     * @return array|null
     */
    public function getSendbackData()
    {
        return $this->sendback_data;
    }

    /**
     * Set sendconfirmData.
     *
     * @param array|null $sendconfirmData
     *
     * @return Aftersales
     */
    public function setSendconfirmData($sendconfirmData = null)
    {
        $this->sendconfirm_data = $sendconfirmData;

        return $this;
    }

    /**
     * Get sendconfirmData.
     *
     * @return array|null
     */
    public function getSendconfirmData()
    {
        return $this->sendconfirm_data;
    }

    /**
     * Set thirdData.
     *
     * @param string|null $thirdData
     *
     * @return Aftersales
     */
    public function setThirdData($thirdData = null)
    {
        $this->third_data = $thirdData;

        return $this;
    }

    /**
     * Get thirdData.
     *
     * @return string|null
     */
    public function getThirdData()
    {
        return $this->third_data;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return Aftersales
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
     * @return Aftersales
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

    /**
     * Set salesmanId.
     *
     * @param int|null $salesmanId
     *
     * @return Aftersales
     */
    public function setSalesmanId($salesmanId = null)
    {
        $this->salesman_id = $salesmanId;

        return $this;
    }

    /**
     * Get salesmanId.
     *
     * @return int|null
     */
    public function getSalesmanId()
    {
        return $this->salesman_id;
    }

    /**
     * Set aftersalesAddress.
     *
     * @param array|null $aftersalesAddress
     *
     * @return Aftersales
     */
    public function setAftersalesAddress($aftersalesAddress = null)
    {
        $this->aftersales_address = $aftersalesAddress;

        return $this;
    }

    /**
     * Get aftersalesAddress.
     *
     * @return array|null
     */
    public function getAftersalesAddress()
    {
        return $this->aftersales_address;
    }

    /**
     * Set distributorRemark.
     *
     * @param string $distributorRemark
     *
     * @return Aftersales
     */
    public function setDistributorRemark($distributorRemark)
    {
        $this->distributor_remark = $distributorRemark;

        return $this;
    }

    /**
     * Get distributorRemark.
     *
     * @return string
     */
    public function getDistributorRemark()
    {
        return $this->distributor_remark;
    }

    /**
     * Set contact.
     *
     * @param string $contact
     *
     * @return Aftersales
     */
    public function setContact($contact)
    {
        $this->contact = fixedencrypt($contact);

        return $this;
    }

    /**
     * Get contact.
     *
     * @return string
     */
    public function getContact()
    {
        return fixeddecrypt($this->contact);
    }

    /**
     * Set mobile.
     *
     * @param string|null $mobile
     *
     * @return Aftersales
     */
    public function setMobile($mobile = null)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile.
     *
     * @return string|null
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return Aftersales
     */
    public function setMerchantId($merchantId)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }

    /**
     * Set isPartialCancel.
     *
     * @param bool|null $isPartialCancel
     *
     * @return Aftersales
     */
    public function setIsPartialCanceld($isPartialCancel = null)
    {
        $this->is_partial_cancel = $isPartialCancel;

        return $this;
    }

    /**
     * Get isPartialCancel.
     *
     * @return bool|null
     */
    public function getIsPartialCancel()
    {
        return $this->is_partial_cancel;
    }

    /**
     * Set isPartialCancel.
     *
     * @param bool|null $isPartialCancel
     *
     * @return Aftersales
     */
    public function setIsPartialCancel($isPartialCancel = null)
    {
        $this->is_partial_cancel = $isPartialCancel;

        return $this;
    }

    /**
     * Set returnType.
     *
     * @param string $returnType
     *
     * @return Aftersales
     */
    public function setReturnType($returnType)
    {
        $this->return_type = $returnType;

        return $this;
    }

    /**
     * Get returnType.
     *
     * @return string
     */
    public function getReturnType()
    {
        return $this->return_type;
    }

    /**
     * Set returnDistributorId.
     *
     * @param int $returnDistributorId
     *
     * @return Aftersales
     */
    public function setReturnDistributorId($returnDistributorId)
    {
        $this->return_distributor_id = $returnDistributorId;

        return $this;
    }

    /**
     * Get returnDistributorId.
     *
     * @return int
     */
    public function getReturnDistributorId()
    {
        return $this->return_distributor_id;
    }

    /**
     * Set selfDeliveryOperatorId.
     *
     * @param int|null $selfDeliveryOperatorId
     *
     * @return Aftersales
     */
    public function setSelfDeliveryOperatorId($selfDeliveryOperatorId = null)
    {
        $this->self_delivery_operator_id = $selfDeliveryOperatorId;

        return $this;
    }

    /**
     * Get selfDeliveryOperatorId.
     *
     * @return int|null
     */
    public function getSelfDeliveryOperatorId()
    {
        return $this->self_delivery_operator_id;
    }
}
