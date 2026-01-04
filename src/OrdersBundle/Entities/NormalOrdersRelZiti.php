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
 * NormalOrdersRelZiti 实体订单关联自提信息
 *
 * @ORM\Table(name="orders_rel_ziti", options={"comment":"实体订单关联自提信息"},
 *     indexes={
 *         @ORM\Index(name="idx_order_id", columns={"order_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="OrdersBundle\Repositories\NormalOrdersRelZitiRepository")
 */
class NormalOrdersRelZiti
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
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"自提点名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="lng", type="string", nullable=true, options={"comment":"纬度"})
     */
    private $lng;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", nullable=true, options={"comment":"经度"})
     */
    private $lat;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", nullable=true, options={"comment":"省"})
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true, options={"comment":"市"})
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", nullable=true, options={"comment":"区"})
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="address", nullable=true, type="string", options={"comment":"地址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="contract_phone", type="string", length=20, options={"comment":"联系电话"})
     */
    private $contract_phone;

    /**
     * @var string
     *
     * @ORM\Column(name="pickup_date", type="string", length=20, options={"comment":"自提日期"})
     */
    private $pickup_date;

    /**
     * @var string
     *
     * @ORM\Column(name="pickup_time", type="string", length=20, options={"comment":"自提时间"})
     */
    private $pickup_time;

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
        return $this->id;
    }

    /**
     * Set orderId.
     *
     * @param int $orderId
     *
     * @return NormalOrdersRelZiti
     */
    public function setOrderId($orderId)
    {
        // Ref: 1996368445
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
     * @return NormalOrdersRelZiti
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
     * Set name
     *
     * @param integer $name
     *
     * @return NormalOrdersRelZiti
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return integer
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set lng
     *
     * @param integer $lng
     *
     * @return NormalOrdersRelZiti
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }

    /**
     * Get lng
     *
     * @return integer
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * Set lat
     *
     * @param integer $lat
     *
     * @return NormalOrdersRelZiti
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get lat
     *
     * @return integer
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set province
     *
     * @param integer $province
     *
     * @return NormalOrdersRelZiti
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province
     *
     * @return integer
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set city
     *
     * @param integer $city
     *
     * @return NormalOrdersRelZiti
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return integer
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set area
     *
     * @param integer $area
     *
     * @return NormalOrdersRelZiti
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area
     *
     * @return integer
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set address
     *
     * @param integer $address
     *
     * @return NormalOrdersRelZiti
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return integer
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set contractPhone
     *
     * @param integer $contractPhone
     *
     * @return NormalOrdersRelZiti
     */
    public function setContractPhone($contractPhone)
    {
        $this->contract_phone = $contractPhone;

        return $this;
    }

    /**
     * Get contractPhone
     *
     * @return integer
     */
    public function getContractPhone()
    {
        return $this->contract_phone;
    }

    /**
     * Set pickupDate
     *
     * @param integer $pickupDate
     *
     * @return NormalOrdersRelZiti
     */
    public function setPickupDate($pickupDate)
    {
        $this->pickup_date = $pickupDate;

        return $this;
    }

    /**
     * Get pickupDate
     *
     * @return integer
     */
    public function getPickupDate()
    {
        return $this->pickup_date;
    }

    /**
     * Set pickupTime
     *
     * @param integer $pickupTime
     *
     * @return NormalOrdersRelZiti
     */
    public function setPickupTime($pickupTime)
    {
        $this->pickup_time = $pickupTime;

        return $this;
    }

    /**
     * Get pickupTime
     *
     * @return integer
     */
    public function getPickupTime()
    {
        return $this->pickup_time;
    }

    /**
     * Set createTime.
     *
     * @param int $createTime
     *
     * @return NormalOrdersRelZiti
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
     * @return NormalOrdersRelZiti
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
}
