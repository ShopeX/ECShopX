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
 * Cart 购物车表
 *
 * @ORM\Table(name="orders_cart", options={"comment":"购物车"},
 *     indexes={
 *         @ORM\Index(name="idx_item_id", columns={"item_id"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_promoter_user_id", columns={"promoter_user_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_shop_type", columns={"shop_type"}),
 *         @ORM\Index(name="idx_wxa_appid", columns={"wxa_appid"}),
 *         @ORM\Index(name="idx_is_checked", columns={"is_checked"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\CartRepository")
 */
class Cart
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="cart_id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $cart_id;

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
     * @ORM\Column(name="promoter_user_id", type="bigint", nullable=true, options={"comment":"推广员user_id"})
     */
    private $promoter_user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_ident", type="string", length=50, nullable=true, options={"comment":"会员ident,会员信息和session生成的唯一值"})
     */
    private $user_ident;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_type", nullable=true, type="string", options={"default":"distributor", "comment":"店铺类型；distributor:店铺，shop:门店，community:社区, mall:商城, drug 药品清单"})
     */
    private $shop_type = 'distributor';

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", nullable=true, type="bigint", options={"unsigned":true, "default":0, "comment":"店铺id 或者 社区id"})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_type", nullable=true, options={"comment":"活动类型"})
     */
    private $activity_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", nullable=true, options={"unsigned":true, "default":0, "comment":"活动id"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="marketing_type", type="string", nullable=true, options={"comment":"促销类型"})
     */
    private $marketing_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="marketing_id", type="bigint", nullable=true, options={"unsigned":true, "default":0, "comment":"促销id"})
     */
    private $marketing_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"商品类型。可选值有 normal 实体类商品;services 服务类商品, normal_gift:实体赠品,services_gift:服务类赠品","default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="items_id", nullable=true, type="string", options={"comment":"组合商品关联商品id", "default": ""})
     */
    private $items_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", nullable=true, length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="string", length=1024, nullable=true, options={"comment":"图片"})
     */
    private $pics;

    /**
    * @var integer
    *
    * @ORM\Column(name="num", type="integer", options={"unsigned":true, "comment":"购买商品数量"})
    */
    private $num;

    /**
    * @var integer
    *
    * @ORM\Column(name="price", type="integer", options={"comment":"购买商品价格", "default": 0})
    */
    private $price = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="point", nullable=true, type="integer", options={"comment":"积分兑换价格", "default": 0})
     */
    private $point = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", type="string", nullable=true, options={"comment":"小程序appid"})
     */
    private $wxa_appid;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_checked", type="boolean", options={"comment":"购物车是否选中", "default": true})
     */
    private $is_checked = true;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_plus_buy", type="boolean", options={"comment":"是加价购商品", "default": false})
     */
    private $is_plus_buy = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="source_type", type="string", options={"comment":"购物车数据来源：scancode：扫码加入购物车, normal:正常加入购物车,salesperson:业务员导购下单",   "default": "normal"})
     */
    private $source_type = "normal";

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * Get cartId
     *
     * @return integer
     */
    public function getCartId()
    {
        // Ver: 1e2364-fe10
        return $this->cart_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Cart
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return Cart
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
        // Ver: 1e2364-fe10
        return $this->user_id;
    }

    /**
     * Set promoterUserId
     *
     * @param integer $promoterUserId
     *
     * @return OrderAssociations
     */
    public function setPromoterUserId($promoterUserId)
    {
        $this->promoter_user_id = $promoterUserId;

        return $this;
    }

    /**
     * Get promoterUserId
     *
     * @return integer
     */
    public function getPromoterUserId()
    {
        return $this->promoter_user_id;
    }

    /**
     * Set userIdent
     *
     * @param string $userIdent
     *
     * @return Cart
     */
    public function setUserIdent($userIdent)
    {
        $this->user_ident = $userIdent;

        return $this;
    }

    /**
     * Get userIdent
     *
     * @return string
     */
    public function getUserIdent()
    {
        return $this->user_ident;
    }

    /**
     * Set shopType
     *
     * @param integer $shopType
     *
     * @return Cart
     */
    public function setShopType($shopType)
    {
        $this->shop_type = $shopType;

        return $this;
    }

    /**
     * Get shopType
     *
     * @return integer
     */
    public function getShopType()
    {
        return $this->shop_type;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return Cart
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
     * Set activityType
     *
     * @param string $activityType
     *
     * @return Cart
     */
    public function setActivityType($activityType)
    {
        $this->activity_type = $activityType;

        return $this;
    }

    /**
     * Get activityType
     *
     * @return string
     */
    public function getActivityType()
    {
        return $this->activity_type;
    }

    /**
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return Cart
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId
     *
     * @return integer
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * Set itemType
     *
     * @param string $itemType
     *
     * @return Cart
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return Cart
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return Cart
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
     * Set pics
     *
     * @param string $pics
     *
     * @return Cart
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics
     *
     * @return string
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return Cart
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
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return Cart
     */
    public function setWxaAppid($wxaAppid)
    {
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Cart
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return Cart
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set isChecked
     *
     * @param boolean $isChecked
     *
     * @return Cart
     */
    public function setIsChecked($isChecked)
    {
        $this->is_checked = $isChecked;

        return $this;
    }

    /**
     * Get isChecked
     *
     * @return boolean
     */
    public function getIsChecked()
    {
        return $this->is_checked;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return Cart
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
     * Set isPlusBuy
     *
     * @param boolean $isPlusBuy
     *
     * @return Cart
     */
    public function setIsPlusBuy($isPlusBuy)
    {
        $this->is_plus_buy = $isPlusBuy;

        return $this;
    }

    /**
     * Get isPlusBuy
     *
     * @return boolean
     */
    public function getIsPlusBuy()
    {
        return $this->is_plus_buy;
    }

    /**
     * Set marketingType
     *
     * @param string $marketingType
     *
     * @return Cart
     */
    public function setMarketingType($marketingType)
    {
        $this->marketing_type = $marketingType;

        return $this;
    }

    /**
     * Get marketingType
     *
     * @return string
     */
    public function getMarketingType()
    {
        return $this->marketing_type;
    }

    /**
     * Set marketingId
     *
     * @param integer $marketingId
     *
     * @return Cart
     */
    public function setMarketingId($marketingId)
    {
        $this->marketing_id = $marketingId;

        return $this;
    }

    /**
     * Get marketingId
     *
     * @return integer
     */
    public function getMarketingId()
    {
        return $this->marketing_id;
    }

    /**
     * Set itemsId
     *
     * @param string $itemsId
     *
     * @return Cart
     */
    public function setItemsId($itemsId)
    {
        $this->items_id = $itemsId;

        return $this;
    }

    /**
     * Get itemsId
     *
     * @return string
     */
    public function getItemsId()
    {
        return $this->items_id;
    }

    /**
     * Set sourceType.
     *
     * @param string $sourceType
     *
     * @return Cart
     */
    public function setSourceType($sourceType)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType.
     *
     * @return string
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * Set point.
     *
     * @param int|null $point
     *
     * @return Cart
     */
    public function setPoint($point = null)
    {
        $this->point = $point;

        return $this;
    }

    /**
     * Get point.
     *
     * @return int|null
     */
    public function getPoint()
    {
        return $this->point;
    }
}
