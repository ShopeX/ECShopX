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
 * NormalOrdersItems 实体订单明细表
 *
 * @ORM\Table(name="orders_normal_orders_items", options={"comment":"实体订单明细表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_order_id", columns={"company_id", "order_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\NormalOrdersItemsRepository")
 */
class NormalOrdersItems
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     * @ORM\Column(name="goods_bn", type="string", nullable=true, options={"comment":"商品spu编码"})
     */
    private $goods_bn;

    /**
     * @var int
     *
     * @ORM\Column(name="cost_fee", type="integer", options={"unsigned":true, "comment":"商品成本价，以分为单位"})
     */
    private $cost_fee = 0;

    /**
     * @var integer
     *
     * 佣金，以分为单位
     *
     * @ORM\Column(name="commission_fee", type="integer", options={"comment":"佣金(分)", "default": 0})
     */
    private $commission_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="act_id", type="bigint", nullable=true, options={"comment":"营销活动ID，团购ID，社区拼团ID，秒杀活动ID等"})
     */
    private $act_id;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_total_store", type="boolean", nullable=true, options={"comment":"是否是总部库存(true:总部库存，false:店铺库存)", "default": true})
     */
    private $is_total_store = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_unit", nullable=true, type="string", options={"comment":"商品计量单位"})
     */
    private $item_unit;

    /**
     * @var string
     *
     * @ORM\Column(name="pic", type="string", nullable=true,  options={"comment":"商品图片"})
     */
    private $pic;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"unsigned":true, "comment":"购买商品数量"})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"unsigned":true, "comment":"单价，以分为单位"})
     */
    private $price;

    /**
     * @var integer
     *
     * @ORM\Column(name="cost_price", type="integer", options={"unsigned":true, "default": 0, "comment":"原价价，以分为单位"})
     */
    private $cost_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="market_price", type="integer", options={"unsigned":true, "default": 0, "comment":"原价，以分为单位"})
     */
    private $market_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_fee", type="integer", options={"unsigned":true, "comment":"订单金额，以分为单位"})
     */
    private $total_fee;

    /**
     * @var integer
     *
     * @ORM\Column(name="rebate", type="integer", options={"unsigned":true, "default": 0, "comment":"单个分销金额，以分为单位"})
     */
    private $rebate = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_rebate", type="integer", options={"unsigned":true, "default":0, "comment":"总分销金额，以分为单位"})
     */
    private $total_rebate = 0;


    /**
     * @var integer
     *
     * @ORM\Column(name="templates_id", type="integer", options={"default":0, "comment":"运费模板id"})
     */
    private $templates_id = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="item_fee", type="integer", options={"unsigned":true, "comment":"商品总金额，以分为单位"})
     */
    private $item_fee = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="member_discount", type="integer", options={"unsigned":true, "comment":"会员折扣金额，以分为单位"})
     */
    private $member_discount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="coupon_discount", type="integer", options={"unsigned":true, "comment":"优惠券抵扣金额，以分为单位"})
     */
    private $coupon_discount = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="discount_fee", type="integer", options={"comment":"订单优惠金额，以分为单位", "default":0})
     */
    private $discount_fee = 0;

    /**
    * @var int
    *
    * @ORM\Column(name="discount_info", type="text", nullable=true, options={"comment":"订单优惠详情"})
    */
    private $discount_info = 0;

    /**
    * @var string
    *
    * @ORM\Column(name="add_service_info", type="text", nullable=true, options={"comment":"订单商品附加信息"})
    */
    private $add_service_info = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="order_item_type", type="string", options={"comment":"订单商品类型,normal:正常商品，gift: 赠品, plus_buy: 加价购商品", "default": "normal"})
     */
    private $order_item_type = 'normal';

    /**
     * @var string
     *
     * @ORM\Column(name="coupon_discount_desc", type="text", nullable=true, options={"comment":"优惠券使用详情"})
     */
    private $coupon_discount_desc = "";

    /**
     * @var string
     *
     * @ORM\Column(name="member_discount_desc", type="text", nullable=true, options={"comment":"会员折扣使用详情"})
     */
    private $member_discount_desc = "";

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_rate", type="boolean", nullable=true, options={"comment":"是否评价", "default": 0})
     */
    private $is_rate = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="auto_close_aftersales_time", nullable=true, type="integer", options={"comment":"自动关闭售后时间"})
     */
    private $auto_close_aftersales_time;

    /**
     * @var \DateTime $create_time
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", options={"comment":"订单创建时间"})
     */
    private $create_time;

    /**
     * @var \DateTime $update_time
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true, options={"comment":"订单更新时间"})
     */
    private $update_time;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp", type="string", nullable=true, options={"comment":"快递公司"})
     */
    private $delivery_corp;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_code", type="string", nullable=true, options={"comment":"快递单号"})
     */
    private $delivery_code;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_img", type="string", nullable=true, options={"comment":"快递发货凭证"})
     */
    private $delivery_img;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_time", type="integer", nullable=true, options={"comment":"发货时间"})
     */
    private $delivery_time;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_status", type="string", options={"default": "PENDING", "comment":"发货状态。可选值有 DONE—已发货;PENDING—待发货"})
     */
    private $delivery_status = 'PENDING';

    /**
     * @var string
     *
     * @ORM\Column(name="aftersales_status", nullable=true, type="string", options={ "comment":"售后状态。可选值有 WAIT_SELLER_AGREE 0 等待商家处理;WAIT_BUYER_RETURN_GOODS 1 商家接受申请，等待消费者回寄;WAIT_SELLER_CONFIRM_GOODS 2 消费者回寄，等待商家收货确认;SELLER_REFUSE_BUYER 3 售后驳回;SELLER_SEND_GOODS 4 卖家重新发货 换货完成;REFUND_SUCCESS 5 退款成功;REFUND_CLOSED 6 退款关闭;CLOSED 7 售后关闭"})
     */
    private $aftersales_status;

    /**
     * @var integer
     *
     * @ORM\Column(name="refunded_fee", type="integer", options={"unsigned":true, "comment":"退款金额，以分为单位","default": 0})
     */
    private $refunded_fee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_type", type="string", length=5, options={"comment":"货币类型", "default":"CNY"})
     */
    private $fee_type = 'CNY';

    /**
     * @var string
     *
     * @ORM\Column(name="fee_rate", type="float", precision=15, scale=4, options={"comment":"货币汇率", "default":1})
     */
    private $fee_rate = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="fee_symbol", type="string", options={"comment":"货币符号", "default":"￥"})
     */
    private $fee_symbol = '￥';

    /**
     * @var int
     *
     * @ORM\Column(name="item_point", nullable=true, type="integer", options={"unsigned":true, "comment":"商品积分", "default": 0})
     */
    private $item_point = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"unsigned":true, "comment":"商品总积分", "default": 0})
     */
    private $point = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", type="text", nullable=true, options={"comment":"商品规格描述"})
     */
    private $item_spec_desc;

    /**
     * @var int
     *
     * @ORM\Column(name="volume", nullable=true, type="integer", options={"unsigned":true, "comment":"商品体积", "default": 0})
     */
    private $volume = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="weight", nullable=true, type="float", precision=15, scale=4, options={"comment":"商品重量", "default": 0})
     */
    private $weight = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", options={"default":0, "comment":"订单类型，0普通订单,1跨境订单,....其他"})
     */
    private $type = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="tax_rate", type="string", length=6, nullable=true, options={"default":"", "comment":"商品税率"})
     */
    private $tax_rate;

    /**
     * @var integer
     *
     * @ORM\Column(name="cross_border_tax", type="integer", options={"default":0, "comment":"商品跨境税费"})
     */
    private $cross_border_tax = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="origincountry_name", type="string",length=50, nullable=true, options={"default":"", "comment":"产地国名称"})
     */
    private $origincountry_name;

    /**
     * @var string
     *
     * @ORM\Column(name="origincountry_img_url", type="string", nullable=true, options={"default":"", "comment":"产地国国旗"})
     */
    private $origincountry_img_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="point_fee", type="integer", nullable=true, options={"default":0, "comment":"积分抵扣时分摊的积分的金额，以分为单位"})
     */
    private $point_fee = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_logistics", type="boolean", nullable=true, options={"comment":"门店缺货商品总部快递发货", "default": false})
     */
    private $is_logistics = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="share_points", type="integer", nullable=true, options={"default":0, "comment":"积分抵扣时分摊的积分值"})
     */
    private $share_points = 0;


    /**
     * @var int
     *
     * @ORM\Column(name="up_share_points", nullable=true, type="integer", options={"unsigned":true, "comment":"积分抵扣时分摊的积分升值数", "default": 0})
     */
    private $share_uppoints = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="taxable_fee", type="integer", options={"unsigned":true, "comment":"计税总价，以分为单位", "default":0})
     */
    private $taxable_fee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_item_num", type="integer", nullable=true, options={"comment":"发货单发货数量"})
     */
    private $delivery_item_num;

    /**
     * @var integer
     *
     * @ORM\Column(name="cancel_item_num", type="integer", nullable=true, options={"comment":"取消数量"})
     */
    private $cancel_item_num;

    /**
     * @var int
     *
     * @ORM\Column(name="get_points", nullable=true, type="integer", options={"unsigned":true, "comment":"商品获取积分", "default": 0})
     */
    private $get_points = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_prescription", type="integer", options={"comment":"是否为处方药商品,0否 1是", "default": 0})
     */
    private $is_prescription = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_invoice", type="integer", options={"comment":"是否开票,0否 1已开票 2开票中 3红冲", "default": 0})
     */
    private $is_invoice = 0;

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
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return NormalOrdersItems
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId
     *
     * @return integer
     */
    public function getOrderId()
    {
        // NOTE: important business logic
        return $this->order_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return NormalOrdersItems
     */
    public function setCompanyId($companyId)
    {
        // NOTE: important business logic
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return NormalOrdersItems
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return NormalOrdersItems
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set itemBn
     *
     * @param string $itemBn
     *
     * @return NormalOrdersItems
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
     * Set goodsBn
     *
     * @param string $goodsBn
     *
     * @return NormalOrdersItems
     */
    public function setGoodsBn($goodsBn)
    {
        $this->goods_bn = $goodsBn;

        return $this;
    }

    /**
     * Get goodsBn
     *
     * @return string
     */
    public function getGoodsBn()
    {
        return $this->goods_bn;
    }

    /**
     * Set costFee
     *
     * @param integer $costFee
     *
     * @return NormalOrdersItems
     */
    public function setCostFee($costFee)
    {
        $this->cost_fee = $costFee;

        return $this;
    }

    /**
     * Get costFee
     *
     * @return integer
     */
    public function getCostFee()
    {
        return $this->cost_fee;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return NormalOrdersItems
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set actId
     *
     * @param integer $actId
     *
     * @return NormalOrdersItems
     */
    public function setActId($actId)
    {
        $this->act_id = $actId;

        return $this;
    }

    /**
     * Get actId
     *
     * @return integer
     */
    public function getActId()
    {
        return $this->act_id;
    }

    /**
     * Set isTotalStore
     *
     * @param boolean $isTotalStore
     *
     * @return NormalOrdersItems
     */
    public function setIsTotalStore($isTotalStore)
    {
        $this->is_total_store = $isTotalStore;

        return $this;
    }

    /**
     * Get isTotalStore
     *
     * @return boolean
     */
    public function getIsTotalStore()
    {
        return $this->is_total_store;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return NormalOrdersItems
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return NormalOrdersItems
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
     * Set itemUnit
     *
     * @param string $itemUnit
     *
     * @return NormalOrdersItems
     */
    public function setItemUnit($itemUnit)
    {
        $this->item_unit = $itemUnit;

        return $this;
    }

    /**
     * Get itemUnit
     *
     * @return string
     */
    public function getItemUnit()
    {
        return $this->item_unit;
    }

    /**
     * Set pic
     *
     * @param string $pic
     *
     * @return NormalOrdersItems
     */
    public function setPic($pic)
    {
        $this->pic = $pic;

        return $this;
    }

    /**
     * Get pic
     *
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return NormalOrdersItems
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
     * Set price
     *
     * @param integer $price
     *
     * @return NormalOrdersItems
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set costPrice
     *
     * @param integer $costPrice
     *
     * @return NormalOrdersItems
     */
    public function setCostPrice($costPrice)
    {
        $this->cost_price = $costPrice;

        return $this;
    }

    /**
     * Get costPrice
     *
     * @return integer
     */
    public function getCostPrice()
    {
        return $this->cost_price;
    }

    /**
     * Set totalFee
     *
     * @param integer $totalFee
     *
     * @return NormalOrdersItems
     */
    public function setTotalFee($totalFee)
    {
        $this->total_fee = $totalFee;

        return $this;
    }

    /**
     * Get totalFee
     *
     * @return integer
     */
    public function getTotalFee()
    {
        return $this->total_fee;
    }

    /**
     * Set rebate
     *
     * @param integer $rebate
     *
     * @return NormalOrdersItems
     */
    public function setRebate($rebate)
    {
        $this->rebate = $rebate;

        return $this;
    }

    /**
     * Get rebate
     *
     * @return integer
     */
    public function getRebate()
    {
        return $this->rebate;
    }

    /**
     * Set totalRebate
     *
     * @param integer $totalRebate
     *
     * @return NormalOrdersItems
     */
    public function setTotalRebate($totalRebate)
    {
        $this->total_rebate = $totalRebate;

        return $this;
    }

    /**
     * Get totalRebate
     *
     * @return integer
     */
    public function getTotalRebate()
    {
        return $this->total_rebate;
    }

    /**
     * Set templatesId
     *
     * @param integer $templatesId
     *
     * @return NormalOrdersItems
     */
    public function setTemplatesId($templatesId)
    {
        $this->templates_id = $templatesId;

        return $this;
    }

    /**
     * Get templatesId
     *
     * @return integer
     */
    public function getTemplatesId()
    {
        return $this->templates_id;
    }

    /**
     * Set itemFee
     *
     * @param integer $itemFee
     *
     * @return NormalOrdersItems
     */
    public function setItemFee($itemFee)
    {
        $this->item_fee = $itemFee;

        return $this;
    }

    /**
     * Get itemFee
     *
     * @return integer
     */
    public function getItemFee()
    {
        return $this->item_fee;
    }

    /**
     * Set memberDiscount
     *
     * @param integer $memberDiscount
     *
     * @return NormalOrdersItems
     */
    public function setMemberDiscount($memberDiscount)
    {
        $this->member_discount = $memberDiscount;

        return $this;
    }

    /**
     * Get memberDiscount
     *
     * @return integer
     */
    public function getMemberDiscount()
    {
        return $this->member_discount;
    }

    /**
     * Set couponDiscount
     *
     * @param integer $couponDiscount
     *
     * @return NormalOrdersItems
     */
    public function setCouponDiscount($couponDiscount)
    {
        $this->coupon_discount = $couponDiscount;

        return $this;
    }

    /**
     * Get couponDiscount
     *
     * @return integer
     */
    public function getCouponDiscount()
    {
        return $this->coupon_discount;
    }

    /**
     * Set discountFee
     *
     * @param integer $discountFee
     *
     * @return NormalOrdersItems
     */
    public function setDiscountFee($discountFee)
    {
        $this->discount_fee = $discountFee;

        return $this;
    }

    /**
     * Get discountFee
     *
     * @return integer
     */
    public function getDiscountFee()
    {
        return $this->discount_fee;
    }

    /**
     * Set discountInfo
     *
     * @param string $discountInfo
     *
     * @return NormalOrdersItems
     */
    public function setDiscountInfo($discountInfo)
    {
        $this->discount_info = $discountInfo;

        return $this;
    }

    /**
     * Get discountInfo
     *
     * @return string
     */
    public function getDiscountInfo()
    {
        return $this->discount_info;
    }

    /**
     * Set addServiceInfo
     *
     * @param string $addServiceInfo
     *
     * @return NormalOrdersItems
     */
    public function setAddServiceInfo($addServiceInfo)
    {
        $this->add_service_info = $addServiceInfo;

        return $this;
    }

    /**
     * Get addServiceInfo
     *
     * @return string
     */
    public function getAddServiceInfo()
    {
        return $this->add_service_info;
    }

    /**
     * Set couponDiscountDesc
     *
     * @param string $couponDiscountDesc
     *
     * @return NormalOrdersItems
     */
    public function setCouponDiscountDesc($couponDiscountDesc)
    {
        $this->coupon_discount_desc = $couponDiscountDesc;

        return $this;
    }

    /**
     * Get couponDiscountDesc
     *
     * @return string
     */
    public function getCouponDiscountDesc()
    {
        return $this->coupon_discount_desc;
    }

    /**
     * Set memberDiscountDesc
     *
     * @param string $memberDiscountDesc
     *
     * @return NormalOrdersItems
     */
    public function setMemberDiscountDesc($memberDiscountDesc)
    {
        $this->member_discount_desc = $memberDiscountDesc;

        return $this;
    }

    /**
     * Get memberDiscountDesc
     *
     * @return string
     */
    public function getMemberDiscountDesc()
    {
        return $this->member_discount_desc;
    }

    /**
     * Set createTime
     *
     * @param integer $createTime
     *
     * @return NormalOrdersItems
     */
    public function setCreateTime($createTime)
    {
        $this->create_time = $createTime;

        return $this;
    }

    /**
     * Get createTime
     *
     * @return integer
     */
    public function getCreateTime()
    {
        return $this->create_time;
    }

    /**
     * Set updateTime
     *
     * @param integer $updateTime
     *
     * @return NormalOrdersItems
     */
    public function setUpdateTime($updateTime)
    {
        $this->update_time = $updateTime;

        return $this;
    }

    /**
     * Get updateTime
     *
     * @return integer
     */
    public function getUpdateTime()
    {
        return $this->update_time;
    }

    /**
     * Set deliveryCorp
     *
     * @param string $deliveryCorp
     *
     * @return NormalOrdersItems
     */
    public function setDeliveryCorp($deliveryCorp)
    {
        $this->delivery_corp = $deliveryCorp;

        return $this;
    }

    /**
     * Get deliveryCorp
     *
     * @return string
     */
    public function getDeliveryCorp()
    {
        return $this->delivery_corp;
    }

    /**
     * Set deliveryCode
     *
     * @param string $deliveryCode
     *
     * @return NormalOrdersItems
     */
    public function setDeliveryCode($deliveryCode)
    {
        $this->delivery_code = $deliveryCode;

        return $this;
    }

    /**
     * Get deliveryCode
     *
     * @return string
     */
    public function getDeliveryCode()
    {
        return $this->delivery_code;
    }

    /**
     * Set deliveryTime
     *
     * @param integer $deliveryTime
     *
     * @return NormalOrdersItems
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->delivery_time = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime
     *
     * @return integer
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * Set deliveryStatus
     *
     * @param string $deliveryStatus
     *
     * @return NormalOrdersItems
     */
    public function setDeliveryStatus($deliveryStatus)
    {
        $this->delivery_status = $deliveryStatus;

        return $this;
    }

    /**
     * Get deliveryStatus
     *
     * @return string
     */
    public function getDeliveryStatus()
    {
        return $this->delivery_status;
    }

    /**
     * Set aftersalesStatus
     *
     * @param string $aftersalesStatus
     *
     * @return NormalOrdersItems
     */
    public function setAftersalesStatus($aftersalesStatus)
    {
        $this->aftersales_status = $aftersalesStatus;

        return $this;
    }

    /**
     * Get aftersalesStatus
     *
     * @return string
     */
    public function getAftersalesStatus()
    {
        return $this->aftersales_status;
    }

    /**
     * Set refundedFee
     *
     * @param integer $refundedFee
     *
     * @return NormalOrdersItems
     */
    public function setRefundedFee($refundedFee)
    {
        $this->refunded_fee = $refundedFee;

        return $this;
    }

    /**
     * Get refundedFee
     *
     * @return integer
     */
    public function getRefundedFee()
    {
        return $this->refunded_fee;
    }

    /**
     * Set feeType
     *
     * @param string $feeType
     *
     * @return NormalOrdersItems
     */
    public function setFeeType($feeType)
    {
        $this->fee_type = $feeType;

        return $this;
    }

    /**
     * Get feeType
     *
     * @return string
     */
    public function getFeeType()
    {
        return $this->fee_type;
    }

    /**
     * Set feeRate
     *
     * @param float $feeRate
     *
     * @return NormalOrdersItems
     */
    public function setFeeRate($feeRate)
    {
        $this->fee_rate = $feeRate;

        return $this;
    }

    /**
     * Get feeRate
     *
     * @return float
     */
    public function getFeeRate()
    {
        return $this->fee_rate;
    }

    /**
     * Set feeSymbol
     *
     * @param string $feeSymbol
     *
     * @return NormalOrdersItems
     */
    public function setFeeSymbol($feeSymbol)
    {
        $this->fee_symbol = $feeSymbol;

        return $this;
    }

    /**
     * Get feeSymbol
     *
     * @return string
     */
    public function getFeeSymbol()
    {
        return $this->fee_symbol;
    }

    /**
     * Set itemPoint
     *
     * @param integer $itemPoint
     *
     * @return NormalOrdersItems
     */
    public function setItemPoint($itemPoint)
    {
        $this->item_point = $itemPoint;

        return $this;
    }

    /**
     * Get itemPoint
     *
     * @return integer
     */
    public function getItemPoint()
    {
        return $this->item_point;
    }

    /**
     * Set point
     *
     * @param integer $point
     *
     * @return NormalOrdersItems
     */
    public function setPoint($point)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point
     *
     * @return integer
     */
    public function getPoint()
    {
        return $this->point;
    }

    /**
     * Set orderItemType
     *
     * @param string $orderItemType
     *
     * @return NormalOrdersItems
     */
    public function setOrderItemType($orderItemType)
    {
        $this->order_item_type = $orderItemType;

        return $this;
    }

    /**
     * Get orderItemType
     *
     * @return string
     */
    public function getOrderItemType()
    {
        return $this->order_item_type;
    }

    /**
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return NormalOrdersItems
     */
    public function setItemSpecDesc($itemSpecDesc)
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
     * Set volume
     *
     * @param integer $volume
     *
     * @return NormalOrdersItems
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     * Get volume
     *
     * @return integer
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set weight
     *
     * @param integer $weight
     *
     * @return NormalOrdersItems
     */
    public function setWeight($weight)
    {
        $this->weight = $weight;

        return $this;
    }

    /**
     * Get weight
     *
     * @return integer
     */
    public function getWeight()
    {
        return $this->weight;
    }

    /**
     * Set isRate
     *
     * @param boolean $isRate
     *
     * @return NormalOrdersItems
     */
    public function setIsRate($isRate)
    {
        $this->is_rate = $isRate;

        return $this;
    }

    /**
     * Get isRate
     *
     * @return boolean
     */
    public function getIsRate()
    {
        return $this->is_rate;
    }

    /**
     * Set deliveryImg.
     *
     * @param string|null $deliveryImg
     *
     * @return NormalOrdersItems
     */
    public function setDeliveryImg($deliveryImg = null)
    {
        $this->delivery_img = $deliveryImg;

        return $this;
    }

    /**
     * Get deliveryImg.
     *
     * @return string|null
     */
    public function getDeliveryImg()
    {
        return $this->delivery_img;
    }

    /**
     * Set autoCloseAftersalesTime.
     *
     * @param string $autoCloseAftersalesTime
     *
     * @return NormalOrdersItems
     */
    public function setAutoCloseAftersalesTime($autoCloseAftersalesTime)
    {
        $this->auto_close_aftersales_time = $autoCloseAftersalesTime;

        return $this;
    }

    /**
     * Get autoCloseAftersalesTime.
     *
     * @return string
     */
    public function getAutoCloseAftersalesTime()
    {
        return $this->auto_close_aftersales_time;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return NormalOrdersItems
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set taxRate.
     *
     * @param string|null $taxRate
     *
     * @return NormalOrdersItems
     */
    public function setTaxRate($taxRate = null)
    {
        $this->tax_rate = $taxRate;

        return $this;
    }

    /**
     * Get taxRate.
     *
     * @return string|null
     */
    public function getTaxRate()
    {
        return $this->tax_rate;
    }

    /**
     * Set crossBorderTax.
     *
     * @param int $crossBorderTax
     *
     * @return NormalOrdersItems
     */
    public function setCrossBorderTax($crossBorderTax)
    {
        $this->cross_border_tax = $crossBorderTax;

        return $this;
    }

    /**
     * Get crossBorderTax.
     *
     * @return int
     */
    public function getCrossBorderTax()
    {
        return $this->cross_border_tax;
    }

    /**
     * Set origincountryName.
     *
     * @param string|null $origincountryName
     *
     * @return NormalOrdersItems
     */
    public function setOrigincountryName($origincountryName = null)
    {
        $this->origincountry_name = $origincountryName;

        return $this;
    }

    /**
     * Get origincountryName.
     *
     * @return string|null
     */
    public function getOrigincountryName()
    {
        return $this->origincountry_name;
    }

    /**
     * Set origincountryImgUrl.
     *
     * @param string|null $origincountryImgUrl
     *
     * @return NormalOrdersItems
     */
    public function setOrigincountryImgUrl($origincountryImgUrl = null)
    {
        $this->origincountry_img_url = $origincountryImgUrl;

        return $this;
    }

    /**
     * Get origincountryImgUrl.
     *
     * @return string|null
     */
    public function getOrigincountryImgUrl()
    {
        return $this->origincountry_img_url;
    }

    /**
     * Set pointFee.
     *
     * @param int|null $pointFee
     *
     * @return NormalOrdersItems
     */
    public function setPointFee($pointFee = null)
    {
        $this->point_fee = $pointFee;

        return $this;
    }

    /**
     * Get pointFee.
     *
     * @return int|null
     */
    public function getPointFee()
    {
        return $this->point_fee;
    }

    /**
     * Set sharePoints.
     *
     * @param int|null $sharePoints
     *
     * @return NormalOrdersItems
     */
    public function setSharePoints($sharePoints = null)
    {
        $this->share_points = $sharePoints;

        return $this;
    }

    /**
     * Set isLogistics.
     *
     * @param bool $isLogistics
     *
     * @return NormalOrdersItems
     */
    public function setIsLogistics($isLogistics)
    {
        $this->is_logistics = $isLogistics;

        return $this;
    }

    /**
     * Get sharePoints.
     *
     * @return int|null
     */
    public function getSharePoints()
    {
        return $this->share_points;
    }

    /**
     * Set taxableFee.
     *
     * @param int $taxableFee
     *
     * @return NormalOrdersItems
     */
    public function setTaxableFee($taxableFee)
    {
        $this->taxable_fee = $taxableFee;

        return $this;
    }

    /**
     * Get isLogistics.
     *
     * @return bool
     */
    public function getIsLogistics()
    {
        return $this->is_logistics;
    }

    /**
     * Get taxableFee.
     *
     * @return int
     */
    public function getTaxableFee()
    {
        return $this->taxable_fee;
    }

    /**
     * Set deliveryItemNum.
     *
     * @param int|null $deliveryItemNum
     *
     * @return NormalOrdersItems
     */
    public function setDeliveryItemNum($deliveryItemNum = null)
    {
        $this->delivery_item_num = $deliveryItemNum;

        return $this;
    }

    /**
     * Get deliveryItemNum.
     *
     * @return int|null
     */
    public function getDeliveryItemNum()
    {
        return $this->delivery_item_num;
    }

    /**
     * Set cancelItemNum.
     *
     * @param int|null $cancelItemNum
     *
     * @return NormalOrdersItems
     */
    public function setCancelItemNum($cancelItemNum = null)
    {
        $this->cancel_item_num = $cancelItemNum;

        return $this;
    }

    /**
     * Get cancelItemNum.
     *
     * @return int|null
     */
    public function getCancelItemNum()
    {
        return $this->cancel_item_num;
    }

    /**
     * Set getPoints.
     *
     * @param int|null $getPoints
     *
     * @return NormalOrders
     */
    public function setGetPoints($getPoints = null)
    {
        $this->get_points = $getPoints;

        return $this;
    }

    /**
     * Get getPoints.
     *
     * @return int|null
     */
    public function getGetPoints()
    {
        return $this->get_points;
    }


    /**
     * Set shareUppoints.
     *
     * @param int|null $shareUppoints
     *
     * @return NormalOrdersItems
     */
    public function setShareUppoints($shareUppoints = null)
    {
        $this->share_uppoints = $shareUppoints;

        return $this;
    }

    /**
     * Get shareUppoints.
     *
     * @return int|null
     */
    public function getShareUppoints()
    {
        return $this->share_uppoints;
    }


    /**
     * Set marketPrice.
     *
     * @param int $marketPrice
     *
     * @return NormalOrdersItems
     */
    public function setMarketPrice($marketPrice)
    {
        $this->market_price = $marketPrice;

        return $this;
    }

    /**
     * Get marketPrice.
     *
     * @return int
     */
    public function getMarketPrice()
    {
        return $this->market_price;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return NormalOrdersItems
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
     * Set commissionFee.
     *
     * @param int $commissionFee
     *
     * @return NormalOrdersItems
     */
    public function setCommissionFee($commissionFee)
    {
        $this->commission_fee = $commissionFee;

        return $this;
    }

    /**
     * Get commissionFee.
     *
     * @return int
     */
    public function getCommissionFee()
    {
        return $this->commission_fee;
    }


    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return NormalOrdersItems
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

    /**
     * Set isPrescription.
     *
     * @param int $isPrescription
     *
     * @return NormalOrdersItems
     */
    public function setIsPrescription($isPrescription)
    {
        $this->is_prescription = $isPrescription;

        return $this;
    }

    /**
     * Get isPrescription.
     *
     * @return int
     */
    public function getIsPrescription()
    {
        return $this->is_prescription;
    }

    /**
     * Set isInvoice.
     *
     * @param int $isInvoice
     *
     * @return NormalOrdersItems
     */
    public function setIsInvoice($isInvoice)
    {
        $this->is_invoice = $isInvoice;

        return $this;
    }

    /**
     * Get isInvoice.
     *
     * @return int
     */
    public function getIsInvoice()
    {
        return $this->is_invoice;
    }
}
