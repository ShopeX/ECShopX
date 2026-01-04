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
 * AftersalesDetail 售后明细子表
 *
 * @ORM\Table(name="aftersales_detail", options={"comment":"售后明细子表"},
 *     indexes={
 *         @ORM\Index(name="idx_aftersales_bn", columns={"aftersales_bn"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_company_order_sub", columns={"company_id", "order_id", "sub_order_id"})
 *     },
 * )
 * @ORM\Entity(repositoryClass="AftersalesBundle\Repositories\AftersalesDetailRepository")
 */
class AftersalesDetail
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="detail_id", type="bigint", options={"comment":"售后明细ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $detail_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="aftersales_bn", type="bigint", options={"comment":"售后单号"})
     */
    private $aftersales_bn;

    /**
     * @var string
     *
     * @ORM\Column(name="order_id", type="string", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="sub_order_id", type="bigint", options={"comment":"订单明细表id"})
     */
    private $sub_order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"产品id"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_bn", type="string", nullable=true, options={"comment":"商品编码"})
     */
    private $item_bn;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="order_item_type", type="string", options={"comment":"订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品", "default": "normal"})
     */
    private $order_item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="item_pic", nullable=true, type="string", options={"comment":"商品图片"})
     */
    private $item_pic;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"unsigned":true, "comment":"售后数量"})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_fee", type="integer", options={"unsigned":true, "comment":"应退总金额，单位(分)"})
     */
    private $refund_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="refund_point", type="integer", nullable=true, options={"comment":"积分支付应退款积分","default":0})
     */
    private $refund_point = 0;

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
     * 0 等待商家处理
     * 1 商家接受申请，等待消费者回寄
     * 2 消费者回寄，等待商家收货确认 //换货
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
     * 0 待处理。申请中
     * 5 审核中。
     * 1 处理中。接受申请
     * 2 已处理。已完成
     * 3 已驳回。已拒绝
     * 4 已撤销。已关闭（取消售后）
     *
     * @ORM\Column(name="aftersales_status", type="integer", options={"default": 0, "comment":"售后状态"})
     */
    private $aftersales_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="contact_info", type="string", nullable=true, options={"comment":"联系信息"})
     */
    // private $contact_info;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="string", length=300, options={"comment":"申请售后原因"})
     */
    // private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="description", nullable=true, type="string", length=300, options={"comment":"申请描述"})
     */
    // private $description;

    /**
     * @var json_array
     *
     * @ORM\Column(name="evidence_pic", nullable=true, type="simple_array", options={"comment":"图片凭证信息"})
     */
    // private $evidence_pic;

    /**
     * @var string
     *
     * @ORM\Column(name="refuse_reason", nullable=true, type="string", length=300, options={"comment":"拒绝原因"})
     */
    // private $refuse_reason;

    /**
     * @var string
     *
     * @ORM\Column(name="memo", nullable=true, type="string", length=300, options={"comment":"售后备注"})
     */
    // private $memo;

    /**
     * @var json_array
     *
     * @ORM\Column(name="sendback_data", nullable=true, type="json_array", options={"comment":"消费者提交退货物流信息"})
     */
    // private $sendback_data;

    /**
     * @var json_array
     *
     * @ORM\Column(name="sendconfirm_data", nullable=true, type="json_array", options={"comment":"商家重新发货物流信息"})
     */
    // private $sendconfirm_data;

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
     * @ORM\Column(name="auto_refuse_time", type="string", options={"comment":"售后自动驳回时间", "default": 0})
     */
    private $auto_refuse_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="refunded_num", type="integer", nullable=true, options={"unsigned":true, "comment":"实际入库数量", "default": 0})
     */
    private $refunded_num;

    public function setRefundedNum($refundedNum)
    {
        $this->refunded_num = $refundedNum;

        return $this;
    }

    public function getRefundedNum()
    {
        return $this->refunded_num;
    }
    
    /**
     * Get detailId.
     *
     * @return int
     */
    public function getDetailId()
    {
        return $this->detail_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return AftersalesDetail
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
     * @return AftersalesDetail
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
     * Set aftersalesBn.
     *
     * @param int $aftersalesBn
     *
     * @return AftersalesDetail
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
        return $this->aftersales_bn;
    }

    /**
     * Set orderId.
     *
     * @param string $orderId
     *
     * @return AftersalesDetail
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
     *
     * @return string
     */
    public function getOrderId()
    {
        return $this->order_id;
    }

    /**
     * Set subOrderId.
     *
     * @param int $subOrderId
     *
     * @return AftersalesDetail
     */
    public function setSubOrderId($subOrderId)
    {
        $this->sub_order_id = $subOrderId;

        return $this;
    }

    /**
     * Get subOrderId.
     *
     * @return int
     */
    public function getSubOrderId()
    {
        return $this->sub_order_id;
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return AftersalesDetail
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemBn.
     *
     * @param string|null $itemBn
     *
     * @return AftersalesDetail
     */
    public function setItemBn($itemBn = null)
    {
        $this->item_bn = $itemBn;

        return $this;
    }

    /**
     * Get itemBn.
     *
     * @return string|null
     */
    public function getItemBn()
    {
        return $this->item_bn;
    }

    /**
     * Set itemName.
     *
     * @param string|null $itemName
     *
     * @return AftersalesDetail
     */
    public function setItemName($itemName = null)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName.
     *
     * @return string|null
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set orderItemType.
     *
     * @param string $orderItemType
     *
     * @return AftersalesDetail
     */
    public function setOrderItemType($orderItemType)
    {
        $this->order_item_type = $orderItemType;

        return $this;
    }

    /**
     * Get orderItemType.
     *
     * @return string
     */
    public function getOrderItemType()
    {
        return $this->order_item_type;
    }

    /**
     * Set itemPic.
     *
     * @param string|null $itemPic
     *
     * @return AftersalesDetail
     */
    public function setItemPic($itemPic = null)
    {
        $this->item_pic = $itemPic;

        return $this;
    }

    /**
     * Get itemPic.
     *
     * @return string|null
     */
    public function getItemPic()
    {
        return $this->item_pic;
    }

    /**
     * Set num.
     *
     * @param int $num
     *
     * @return AftersalesDetail
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num.
     *
     * @return int
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set refundFee.
     *
     * @param int $refundFee
     *
     * @return AftersalesDetail
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
     * @param int|null $refundPoint
     *
     * @return AftersalesDetail
     */
    public function setRefundPoint($refundPoint = null)
    {
        $this->refund_point = $refundPoint;

        return $this;
    }

    /**
     * Get refundPoint.
     *
     * @return int|null
     */
    public function getRefundPoint()
    {
        return $this->refund_point;
    }

    /**
     * Set aftersalesType.
     *
     * @param string $aftersalesType
     *
     * @return AftersalesDetail
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
     * Set progress.
     *
     * @param int $progress
     *
     * @return AftersalesDetail
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
     * Set aftersalesStatus.
     *
     * @param int $aftersalesStatus
     *
     * @return AftersalesDetail
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
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return AftersalesDetail
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
     * @return AftersalesDetail
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
     * Set autoRefuseTime.
     *
     * @param string $autoRefuseTime
     *
     * @return AftersalesDetail
     */
    public function setAutoRefuseTime($autoRefuseTime)
    {
        $this->auto_refuse_time = $autoRefuseTime;

        return $this;
    }

    /**
     * Get autoRefuseTime.
     *
     * @return string
     */
    public function getAutoRefuseTime()
    {
        return $this->auto_refuse_time;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return AftersalesDetail
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
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return AftersalesDetail
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }
}
