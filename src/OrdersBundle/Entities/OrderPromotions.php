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
 * OrderPromotions  商品促销和订单关联表
 *
 * @ORM\Table(name="orders_rel_promotions", options={"comment":"商品促销和订单关联表"},
 *     indexes={
 *         @ORM\Index(name="idx_moid", columns={"moid"}),
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *         @ORM\Index(name="idx_shop_id", columns={"shop_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrderPromotionsRepository")
 */

class OrderPromotions
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
     * @var integer master_order_id
     *
     * @ORM\Column(name="moid", type="bigint", length=64, options={"comment":"主订单id"})
     */
    private $moid;

    /**
     * @var integer  child_order_id
     *
     * @ORM\Column(name="coid", type="bigint", length=64, options={"comment":"子订单id"})
     */
    private $coid;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="order_type", type="string", options={"comment":"订单类型","default":"normal"})
     */
    private $order_type = 'normal';

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", nullable=true, type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="item_type", nullable=true, type="string", options={"comment":"商品类型", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
     */
    private $activity_id;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", nullable=true, type="string", options={"comment":"活动名称"})
     */
    private $activity_name;

    /**
    * @var integer
    *
    * @ORM\Column(name="activity_type", type="string", options={"comment":"活动类型"})
    */
    private $activity_type;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_tag", nullable=true, type="string", options={"comment":"活动标签"})
     */
    private $activity_tag;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_desc", nullable=true, type="text", options={"comment":"活动描述"})
     */
    private $activity_desc;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"店铺id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_type", type="string", nullable=true, options={"comment":"店铺类型", "default": "shop"})
     */
    private $shop_type = 'shop';

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="string", nullable=true, options={"comment":"促销应用状态,valid:有效, invalid:失效", "default": "valid"})
     */
    private $status = 'valid';

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set moid
     *
     * @param integer $moid
     *
     * @return OrderPromotions
     */
    public function setMoid($moid)
    {
        $this->moid = $moid;

        return $this;
    }

    /**
     * Get moid
     *
     * @return integer
     */
    public function getMoid()
    {
        return $this->moid;
    }

    /**
     * Set coid
     *
     * @param integer $coid
     *
     * @return OrderPromotions
     */
    public function setCoid($coid)
    {
        $this->coid = $coid;

        return $this;
    }

    /**
     * Get coid
     *
     * @return integer
     */
    public function getCoid()
    {
        return $this->coid;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return OrderPromotions
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
     * Set orderType
     *
     * @param string $orderType
     *
     * @return OrderPromotions
     */
    public function setOrderType($orderType)
    {
        $this->order_type = $orderType;

        return $this;
    }

    /**
     * Get orderType
     *
     * @return string
     */
    public function getOrderType()
    {
        return $this->order_type;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return OrderPromotions
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
     * @return OrderPromotions
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
     * Set itemType
     *
     * @param string $itemType
     *
     * @return OrderPromotions
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
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return OrderPromotions
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
     * Set activityName
     *
     * @param string $activityName
     *
     * @return OrderPromotions
     */
    public function setActivityName($activityName)
    {
        $this->activity_name = $activityName;

        return $this;
    }

    /**
     * Get activityName
     *
     * @return string
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }

    /**
     * Set activityType
     *
     * @param integer $activityType
     *
     * @return OrderPromotions
     */
    public function setActivityType($activityType)
    {
        $this->activity_type = $activityType;

        return $this;
    }

    /**
     * Get activityType
     *
     * @return integer
     */
    public function getActivityType()
    {
        return $this->activity_type;
    }

    /**
     * Set activityTag
     *
     * @param string $activityTag
     *
     * @return OrderPromotions
     */
    public function setActivityTag($activityTag)
    {
        $this->activity_tag = $activityTag;

        return $this;
    }

    /**
     * Get activityTag
     *
     * @return string
     */
    public function getActivityTag()
    {
        return $this->activity_tag;
    }

    /**
     * Set activityDesc
     *
     * @param string $activityDesc
     *
     * @return OrderPromotions
     */
    public function setActivityDesc($activityDesc)
    {
        $this->activity_desc = $activityDesc;

        return $this;
    }

    /**
     * Get activityDesc
     *
     * @return string
     */
    public function getActivityDesc()
    {
        return $this->activity_desc;
    }

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return OrderPromotions
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
     * Set shopType
     *
     * @param string $shopType
     *
     * @return OrderPromotions
     */
    public function setShopType($shopType)
    {
        $this->shop_type = $shopType;

        return $this;
    }

    /**
     * Get shopType
     *
     * @return string
     */
    public function getShopType()
    {
        return $this->shop_type;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return OrderPromotions
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return OrderPromotions
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
     * Set created
     *
     * @param integer $created
     *
     * @return OrderPromotions
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
     * @return OrderPromotions
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
}
