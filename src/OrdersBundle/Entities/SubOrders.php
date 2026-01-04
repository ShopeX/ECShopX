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
 * SubOrders 服务类子订单表
 *
 * @ORM\Table(name="sub_orders", options={"comment":"服务类子订单表"})
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\SubOrdersRepository")
 */
class SubOrders
{
    /**
     * @var integerbigint
     *
     * @ORM\Id
     * @ORM\Column(name="order_id", type="bigint", length=64, options={"comment":"订单号"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", length=64, options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", length=255, options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="label_id", type="bigint", options={"comment":"数值属性ID"})
     */
    private $label_id;

    /**
     * @var string
     *
     * @ORM\Column(name="label_name", type="string", length=255, options={"comment":"数值属性名称"})
     */
    private $label_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_price", type="integer", options={"comment":"价格,单位为‘分’"})
     */
    private $label_price;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="bigint", options={"comment":"数值属性值，例如买了50个次卡，就是填50；赠送了10个经验，就是填10"})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_time", type="bigint", options={"comment":"有效期，例如30天"})
     */
    private $limit_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_not_limit_num", type="integer", options={"comment":"限制核销次数,1:不限制；2:限制", "default": 2})
     */
    private $is_not_limit_num = 2;


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
     * Set orderId
     *
     * @param integer $orderId
     *
     * @return SubOrders
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
        return $this->order_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return SubOrders
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
        // ShopEx EcShopX Service Component
        return $this->company_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return SubOrders
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
     * Set labelId
     *
     * @param integer $labelId
     *
     * @return SubOrders
     */
    public function setLabelId($labelId)
    {
        $this->label_id = $labelId;

        return $this;
    }

    /**
     * Get labelId
     *
     * @return integer
     */
    public function getLabelId()
    {
        return $this->label_id;
    }

    /**
     * Set labelName
     *
     * @param string $labelName
     *
     * @return SubOrders
     */
    public function setLabelName($labelName)
    {
        $this->label_name = $labelName;

        return $this;
    }

    /**
     * Get labelName
     *
     * @return string
     */
    public function getLabelName()
    {
        return $this->label_name;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return SubOrders
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
     * Set limitTime
     *
     * @param integer $limitTime
     *
     * @return SubOrders
     */
    public function setLimitTime($limitTime)
    {
        $this->limit_time = $limitTime;

        return $this;
    }

    /**
     * Get limitTime
     *
     * @return integer
     */
    public function getLimitTime()
    {
        return $this->limit_time;
    }

    /**
     * Set itemName
     *
     * @param string $itemName
     *
     * @return SubOrders
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
     * Set labelPrice
     *
     * @param integer $labelPrice
     *
     * @return SubOrders
     */
    public function setLabelPrice($labelPrice)
    {
        $this->label_price = $labelPrice;

        return $this;
    }

    /**
     * Get labelPrice
     *
     * @return integer
     */
    public function getLabelPrice()
    {
        return $this->label_price;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return SubOrders
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
     * @return SubOrders
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
     * Set isNotLimitNum
     *
     * @param integer $isNotLimitNum
     *
     * @return SubOrders
     */
    public function setIsNotLimitNum($isNotLimitNum)
    {
        $this->is_not_limit_num = $isNotLimitNum;

        return $this;
    }

    /**
     * Get isNotLimitNum
     *
     * @return integer
     */
    public function getIsNotLimitNum()
    {
        return $this->is_not_limit_num;
    }
}
