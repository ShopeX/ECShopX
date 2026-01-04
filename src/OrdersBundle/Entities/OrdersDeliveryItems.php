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
 * OrdersDeliveryItems  订单发货单商品表
 *
 * @ORM\Table(name="orders_delivery_items", options={"comment":"订单发货单商品表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_orders_delivery_id", columns={"orders_delivery_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrdersDeliveryItemsRepository")
 */

class OrdersDeliveryItems
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="orders_delivery_items_id", type="bigint", options={"comment":"orders_delivery_items_id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $orders_delivery_items_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"订单id"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_items_id", type="bigint", options={"comment":"订单items表id"})
     */
    private $order_items_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="orders_delivery_id", type="bigint", options={"comment":"订单发货单id"})
     */
    private $orders_delivery_id;

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
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"comment":"发货数量"})
     */
    private $num;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="pic", type="string", options={"comment":"商品图片"})
     */
    private $pic;

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
     * Get ordersDeliveryItemsId.
     *
     * @return int
     */
    public function getOrdersDeliveryItemsId()
    {
        return $this->orders_delivery_items_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrdersDeliveryItems
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
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return OrdersDeliveryItems
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
     * Set ordersDeliveryId.
     *
     * @param int $ordersDeliveryId
     *
     * @return OrdersDeliveryItems
     */
    public function setOrdersDeliveryId($ordersDeliveryId)
    {
        $this->orders_delivery_id = $ordersDeliveryId;

        return $this;
    }

    /**
     * Get ordersDeliveryId.
     *
     * @return int
     */
    public function getOrdersDeliveryId()
    {
        return $this->orders_delivery_id;
    }

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return OrdersDeliveryItems
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
     * Set num.
     *
     * @param int $num
     *
     * @return OrdersDeliveryItems
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
     * Set itemName.
     *
     * @param string $itemName
     *
     * @return OrdersDeliveryItems
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName.
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set pic.
     *
     * @param string $pic
     *
     * @return OrdersDeliveryItems
     */
    public function setPic($pic)
    {
        $this->pic = $pic;

        return $this;
    }

    /**
     * Get pic.
     *
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OrdersDeliveryItems
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return OrdersDeliveryItems
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set orderItemsId.
     *
     * @param int $orderItemsId
     *
     * @return OrdersDeliveryItems
     */
    public function setOrderItemsId($orderItemsId)
    {
        $this->order_items_id = $orderItemsId;

        return $this;
    }

    /**
     * Get orderItemsId.
     *
     * @return int
     */
    public function getOrderItemsId()
    {
        return $this->order_items_id;
    }

    /**
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return OrdersDeliveryItems
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
