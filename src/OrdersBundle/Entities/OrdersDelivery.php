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
 * OrdersDelivery  订单发货单表
 *
 * @ORM\Table(name="orders_delivery", options={"comment":"订单发货单表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_supplier_id", columns={"supplier_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\OrdersDeliveryRepository")
 */

class OrdersDelivery
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="orders_delivery_id", type="bigint", options={"comment":"orders_delivery_id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $orders_delivery_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="integer", options={"comment":"供应商id", "default":0})
     */
    private $supplier_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="order_id", type="bigint", options={"comment":"订单id"})
     */
    private $order_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp", type="string", options={"comment":"快递公司"})
     */
    private $delivery_corp;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp_name", type="string", options={"comment":"快递公司名称"})
     */
    private $delivery_corp_name;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_code", type="string", options={"comment":"快递单号"})
     */
    private $delivery_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivery_time", type="integer", options={"comment":"发货时间"})
     */
    private $delivery_time;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_corp_source", type="string", nullable=true, options={"comment":"快递代码来源"})
     */
    private $delivery_corp_source;

    /**
     * @var string
     *
     * @ORM\Column(name="receiver_mobile", type="string", nullable=true, options={"comment":"收货人手机号"})
     */
    private $receiver_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="package_type", type="string", nullable=true, options={"comment":"订单包裹类型 batch 整单发货  sep拆单发货"})
     */
    private $package_type;

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
     * @var integer
     *
     * @ORM\Column(name="self_delivery_operator_id", type="bigint", nullable=true, options={"comment":"自配送员id", "default": 0})
     */
    private $self_delivery_operator_id;

    /**
     * @var string
     *
     * @ORM\Column(name="delivery_remark", type="string", nullable=true, options={"comment":"配送备注"})
     */
    private $delivery_remark;


    /**
     * @var string
     *
     * @ORM\Column(name="delivery_pics", nullable=true, type="json_array", options={"comment":"配送图片"})
     */
    private $delivery_pics;

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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return OrdersDelivery
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
     * @return OrdersDelivery
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
     * Set deliveryCorp.
     *
     * @param string $deliveryCorp
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCorp($deliveryCorp)
    {
        $this->delivery_corp = $deliveryCorp;

        return $this;
    }

    /**
     * Get deliveryCorp.
     *
     * @return string
     */
    public function getDeliveryCorp()
    {
        return $this->delivery_corp;
    }

    /**
     * Set deliveryCorpName.
     *
     * @param string $deliveryCorpName
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCorpName($deliveryCorpName)
    {
        $this->delivery_corp_name = $deliveryCorpName;

        return $this;
    }

    /**
     * Get deliveryCorpName.
     *
     * @return string
     */
    public function getDeliveryCorpName()
    {
        return $this->delivery_corp_name;
    }

    /**
     * Set deliveryCode.
     *
     * @param string $deliveryCode
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCode($deliveryCode)
    {
        $this->delivery_code = $deliveryCode;

        return $this;
    }

    /**
     * Get deliveryCode.
     *
     * @return string
     */
    public function getDeliveryCode()
    {
        return $this->delivery_code;
    }

    /**
     * Set deliveryTime.
     *
     * @param int $deliveryTime
     *
     * @return OrdersDelivery
     */
    public function setDeliveryTime($deliveryTime)
    {
        $this->delivery_time = $deliveryTime;

        return $this;
    }

    /**
     * Get deliveryTime.
     *
     * @return int
     */
    public function getDeliveryTime()
    {
        return $this->delivery_time;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return OrdersDelivery
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
     * @return OrdersDelivery
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
     * Set deliveryCorpSource.
     *
     * @param string $deliveryCorpSource
     *
     * @return OrdersDelivery
     */
    public function setDeliveryCorpSource($deliveryCorpSource)
    {
        $this->delivery_corp_source = $deliveryCorpSource;

        return $this;
    }

    /**
     * Get deliveryCorpSource.
     *
     * @return string
     */
    public function getDeliveryCorpSource()
    {
        return $this->delivery_corp_source;
    }

    /**
     * Set receiverMobile.
     *
     * @param string|null $receiverMobile
     *
     * @return OrdersDelivery
     */
    public function setReceiverMobile($receiverMobile = null)
    {
        $this->receiver_mobile = $receiverMobile;

        return $this;
    }

    /**
     * Get receiverMobile.
     *
     * @return string|null
     */
    public function getReceiverMobile()
    {
        return $this->receiver_mobile;
    }

    /**
     * Set userId.
     *
     * @param int $userId
     *
     * @return OrdersDelivery
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
     * Set packageType.
     *
     * @param string|null $packageType
     *
     * @return OrdersDelivery
     */
    public function setPackageType($packageType = null)
    {
        $this->package_type = $packageType;

        return $this;
    }

    /**
     * Get packageType.
     *
     * @return string|null
     */
    public function getPackageType()
    {
        return $this->package_type;
    }

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return OrdersDelivery
     */
    public function setSupplierId($supplierId = 0)
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
     * Set selfDeliveryOperatorId.
     *
     * @param int|null $selfDeliveryOperatorId
     *
     * @return OrdersDelivery
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

    /**
     * Set deliveryRemark.
     *
     * @param string|null $deliveryRemark
     *
     * @return OrdersDelivery
     */
    public function setDeliveryRemark($deliveryRemark = null)
    {
        $this->delivery_remark = $deliveryRemark;

        return $this;
    }

    /**
     * Get deliveryRemark.
     *
     * @return string|null
     */
    public function getDeliveryRemark()
    {
        return $this->delivery_remark;
    }

    /**
     * Set deliveryPics.
     *
     * @param array|null $deliveryPics
     *
     * @return OrdersDelivery
     */
    public function setDeliveryPics($deliveryPics = null)
    {
        $this->delivery_pics = $deliveryPics;

        return $this;
    }

    /**
     * Get deliveryPics.
     *
     * @return array|null
     */
    public function getDeliveryPics()
    {
        return $this->delivery_pics;
    }
}
