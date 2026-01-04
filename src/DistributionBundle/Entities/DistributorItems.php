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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DistributorItems 店铺导购员表
 *
 * @ORM\Table(name="distribution_distributor_items", options={"comment":"店铺导购员表"},indexes={
 *         @ORM\Index(name="idx_defaultitemid_companyid", columns={"default_item_id", "company_id"}),
 *         @ORM\Index(name="idx_companyid_istotalstore_goodscansale_defaultitemid", columns={"company_id", "is_total_store", "goods_can_sale", "default_item_id"}),
 *     },uniqueConstraints={
 *    @ORM\UniqueConstraint(name="distributor_items", columns={"distributor_id", "item_id"}),
 * })
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorItemsRepository")
 */
class DistributorItems
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint")
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品ID"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_item_id", type="bigint", options={"comment":"默认商品ID", "default": 0})
     */
    private $default_item_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品集合ID", "default": 0})
     */
    private $goods_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_show", type="boolean", options={"comment":"是否为列表默认展示", "default" : true})
     */
    private $is_show = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id", "default": 0})
     */
    private $shop_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="store", type="bigint", nullable=true, options={"comment":"商品库存", "default": 0})
     */
    private $store = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="bigint", nullable=true, options={"comment":"商品价格", "default": 0})
     */
    private $price = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_total_store", type="boolean", nullable=true, options={"comment":"是否为总部库存", "default": true})
     */
    private $is_total_store = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_can_sale", type="boolean", nullable=true, options={"comment":"是否在本店可售", "default": true})
     */
    private $is_can_sale = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_can_sale", type="boolean", nullable=true, options={"comment":"商品是否可售，有一个sku可售，那么商品就可售", "default": true})
     */
    private $goods_can_sale = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_self_delivery", type="boolean", nullable=true, options={"comment":"是否开启自提配送", "default": false})
     */
    private $is_self_delivery = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_express_delivery", type="boolean", nullable=true, options={"comment":"是否开启快递配送", "default": false})
     */
    private $is_express_delivery = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="sales", type="bigint", options={"comment":"商品销量", "default": 0})
     */
    private $sales = 0;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return DistributorItems
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return DistributorItems
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return DistributorItems
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
     * Set created
     *
     * @param integer $created
     *
     * @return DistributorItems
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
     * @return DistributorItems
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return DistributorItems
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
     * Set store
     *
     * @param integer $store
     *
     * @return DistributorItems
     */
    public function setStore($store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * Get store
     *
     * @return integer
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return DistributorItems
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
     * Set isTotalStore
     *
     * @param boolean $isTotalStore
     *
     * @return DistributorItems
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
     * Set isCanSale
     *
     * @param boolean $isCanSale
     *
     * @return DistributorItems
     */
    public function setIsCanSale($isCanSale)
    {
        $this->is_can_sale = $isCanSale;

        return $this;
    }

    /**
     * Get isCanSale
     *
     * @return boolean
     */
    public function getIsCanSale()
    {
        return $this->is_can_sale;
    }

    /**
     * Set sales
     *
     * @param integer $sales
     *
     * @return DistributorItems
     */
    public function setSales($sales)
    {
        $this->sales = $sales;

        return $this;
    }

    /**
     * Get sales
     *
     * @return integer
     */
    public function getSales()
    {
        return $this->sales;
    }

    /**
     * Set isShow
     *
     * @param boolean $isShow
     *
     * @return DistributorItems
     */
    public function setIsShow($isShow)
    {
        $this->is_show = $isShow;

        return $this;
    }

    /**
     * Get isShow
     *
     * @return boolean
     */
    public function getIsShow()
    {
        return $this->is_show;
    }

    /**
     * Set defaultItemId
     *
     * @param integer $defaultItemId
     *
     * @return DistributorItems
     */
    public function setDefaultItemId($defaultItemId)
    {
        $this->default_item_id = $defaultItemId;

        return $this;
    }

    /**
     * Get defaultItemId
     *
     * @return integer
     */
    public function getDefaultItemId()
    {
        return $this->default_item_id;
    }

    /**
     * Set isSelfDelivery
     *
     * @param boolean $isSelfDelivery
     *
     * @return DistributorItems
     */
    public function setIsSelfDelivery($isSelfDelivery)
    {
        $this->is_self_delivery = $isSelfDelivery;

        return $this;
    }

    /**
     * Get isSelfDelivery
     *
     * @return boolean
     */
    public function getIsSelfDelivery()
    {
        return $this->is_self_delivery;
    }

    /**
     * Set isExpressDelivery
     *
     * @param boolean $isExpressDelivery
     *
     * @return DistributorItems
     */
    public function setIsExpressDelivery($isExpressDelivery)
    {
        $this->is_express_delivery = $isExpressDelivery;

        return $this;
    }

    /**
     * Get isExpressDelivery
     *
     * @return boolean
     */
    public function getIsExpressDelivery()
    {
        return $this->is_express_delivery;
    }

    /**
     * Set goodsId
     *
     * @param integer $goodsId
     *
     * @return DistributorItems
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId
     *
     * @return integer
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set goodsCanSale
     *
     * @param boolean $goodsCanSale
     *
     * @return DistributorItems
     */
    public function setGoodsCanSale($goodsCanSale)
    {
        $this->goods_can_sale = $goodsCanSale;

        return $this;
    }

    /**
     * Get goodsCanSale
     *
     * @return boolean
     */
    public function getGoodsCanSale()
    {
        return $this->goods_can_sale;
    }
}
