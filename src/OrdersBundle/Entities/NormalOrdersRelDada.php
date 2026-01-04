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
 * NormalOrdersRelDada 实体订单关联达达同城配表
 *
 * @ORM\Table(name="orders_rel_dada", options={"comment":"实体订单关联达达同城配表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_dada_status", columns={"dada_status"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\NormalOrdersRelDadaRepository")
 */
class NormalOrdersRelDada
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
     * @ORM\Column(name="dada_status", type="integer", options={"default": 0, "comment":"达达状态 0:待处理,1:待接单,2:待取货,3:配送中,4:已完成,5:已取消,9:妥投异常之物品返回中,10:妥投异常之物品返回完成,100: 骑士到店,1000:创建达达运单失败"})
     */
    private $dada_status = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="dada_delivery_no", type="string", length=50, nullable=true, options={"comment":"达达平台订单号"})
     */
    private $dada_delivery_no;

    /**
     * @var integer
     *
     * @ORM\Column(name="dada_cancel_from", type="integer", options={"comment":"订单取消原因来源 1:达达回调配送员取消；2:达达回调商家主动取消；3:达达回调系统或客服取消；11:商城系统取消；12:商城商家主动取消；13:商城消费者主动取消；"})
     */
    private $dada_cancel_from = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="dm_id", type="integer", nullable=true, options={"comment":"达达配送员id"})
     */
    private $dm_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="dm_name", type="string", length=20, nullable=true, options={"comment":"配送员姓名"})
     */
    private $dm_name;

    /**
     * @var string
     *
     * @ORM\Column(name="dm_mobile", type="string", nullable=true, options={"comment":"配送员手机号"})
     */
    private $dm_mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="pickup_time", type="integer", options={"comment":"取货时间"})
     */
    private $pickup_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="accept_time", type="integer", options={"comment":"商家接单时间"})
     */
    private $accept_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="delivered_time", type="integer", options={"comment":"送达时间"})
     */
    private $delivered_time = 0;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // ShopEx EcShopX Core Module
        return $this->id;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return NormalOrdersRelDada
     */
    public function setOrderId($orderId)
    {
        // ShopEx EcShopX Core Module
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
     * @return NormalOrdersRelDada
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
     * Set dadaStatus.
     *
     * @param int $dadaStatus
     *
     * @return NormalOrdersRelDada
     */
    public function setDadaStatus($dadaStatus)
    {
        $this->dada_status = $dadaStatus;

        return $this;
    }

    /**
     * Get dadaStatus.
     *
     * @return int
     */
    public function getDadaStatus()
    {
        return $this->dada_status;
    }

    /**
     * Set dadaDeliveryNo.
     *
     * @param string $dadaDeliveryNo
     *
     * @return NormalOrdersRelDada
     */
    public function setDadaDeliveryNo($dadaDeliveryNo)
    {
        $this->dada_delivery_no = $dadaDeliveryNo;

        return $this;
    }

    /**
     * Get dadaDeliveryNo.
     *
     * @return string
     */
    public function getDadaDeliveryNo()
    {
        return $this->dada_delivery_no;
    }

    /**
     * Set dadaCancelFrom.
     *
     * @param int $dadaCancelFrom
     *
     * @return NormalOrdersRelDada
     */
    public function setDadaCancelFrom($dadaCancelFrom)
    {
        $this->dada_cancel_from = $dadaCancelFrom;

        return $this;
    }

    /**
     * Get dadaCancelFrom.
     *
     * @return int
     */
    public function getDadaCancelFrom()
    {
        return $this->dada_cancel_from;
    }

    /**
     * Set dmId.
     *
     * @param int $dmId
     *
     * @return NormalOrdersRelDada
     */
    public function setDmId($dmId)
    {
        $this->dm_id = $dmId;

        return $this;
    }

    /**
     * Get dmId.
     *
     * @return int
     */
    public function getDmId()
    {
        return $this->dm_id;
    }

    /**
     * Set dmName.
     *
     * @param string $dmName
     *
     * @return NormalOrdersRelDada
     */
    public function setDmName($dmName)
    {
        $this->dm_name = $dmName;

        return $this;
    }

    /**
     * Get dmName.
     *
     * @return string
     */
    public function getDmName()
    {
        return $this->dm_name;
    }

    /**
     * Set dmMobile.
     *
     * @param string|null $dmMobile
     *
     * @return NormalOrdersRelDada
     */
    public function setDmMobile($dmMobile = null)
    {
        $this->dm_mobile = $dmMobile;

        return $this;
    }

    /**
     * Get dmMobile.
     *
     * @return string|null
     */
    public function getDmMobile()
    {
        return $this->dm_mobile;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return NormalOrdersRelDada
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
     * @return NormalOrdersRelDada
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
     * Set pickupTime.
     *
     * @param string $pickupTime
     *
     * @return NormalOrdersRelDada
     */
    public function setPickupTime($pickupTime)
    {
        $this->pickup_time = $pickupTime;

        return $this;
    }

    /**
     * Get pickupTime.
     *
     * @return string
     */
    public function getPickupTime()
    {
        return $this->pickup_time;
    }

    /**
     * Set deliveredTime.
     *
     * @param string $deliveredTime
     *
     * @return NormalOrdersRelDada
     */
    public function setDeliveredTime($deliveredTime)
    {
        $this->delivered_time = $deliveredTime;

        return $this;
    }

    /**
     * Get deliveredTime.
     *
     * @return string
     */
    public function getDeliveredTime()
    {
        return $this->delivered_time;
    }

    /**
     * Set acceptTime.
     *
     * @param int $acceptTime
     *
     * @return NormalOrdersRelDada
     */
    public function setAcceptTime($acceptTime)
    {
        $this->accept_time = $acceptTime;

        return $this;
    }

    /**
     * Get acceptTime.
     *
     * @return int
     */
    public function getAcceptTime()
    {
        return $this->accept_time;
    }
}
