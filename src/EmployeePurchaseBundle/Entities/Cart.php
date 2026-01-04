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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cart 员工内购活动购物车
 *
 * @ORM\Table(name="employee_purchase_cart", options={"comment"="员工内购活动购物车"},
 *     indexes={
 *         @ORM\Index(name="idx_enterprise_activity_item_id", columns={"enterprise_id", "activity_id", "item_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\CartRepository")
 */
class Cart
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="cart_id", type="bigint", options={"comment":"购物车ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $cart_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业id"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id",type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户ID"})
     */
    private $user_id;
    
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
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="bigint", options={"comment":"商品数量", "default" : 1})
     */
    private $num = 1;

    /**
     * @var boolean
     *
     * @orm\column(name="is_checked", type="boolean", options={"comment":"购物车是否选中", "default": true})
     */
    private $is_checked = true;


    /**
     * Get cartId
     *
     * @return integer
     */
    public function getCartId()
    {
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
     * Set enterpriseId.
     *
     * @param int $enterpriseId
     *
     * @return Cart
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return int
     */
    public function getEnterpriseId()
    {
        return $this->enterprise_id;
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
        return $this->user_id;
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
     * Set shopType.
     *
     * @param string|null $shopType
     *
     * @return Cart
     */
    public function setShopType($shopType = null)
    {
        $this->shop_type = $shopType;

        return $this;
    }

    /**
     * Get shopType.
     *
     * @return string|null
     */
    public function getShopType()
    {
        return $this->shop_type;
    }

    /**
     * Set shopId.
     *
     * @param int|null $shopId
     *
     * @return Cart
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
}
