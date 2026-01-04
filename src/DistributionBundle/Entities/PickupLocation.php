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
 * PickupLocation
 *
 * @ORM\Table(name="distribution_pickup_location", options={"comment":"店铺自提点"},
 *     indexes={
 *         @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\PickupLocationRepository")
 */
class PickupLocation
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"所属店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rel_distributor_id", type="bigint", options={"comment":"绑定店铺id", "default":0})
     */
    private $rel_distributor_id = 0;

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
     * @ORM\Column(name="hours", nullable=true, type="string", options={"comment":"营业时间"})
     */
    private $hours;

    /**
     * @var string
     *
     * @ORM\Column(name="workdays", nullable=true, type="string", options={"comment":"工作日：周一至周日->1-7，逗号分隔", "default":","})
     */
    private $workdays = ',';

    /**
     * @var string
     *
     * @ORM\Column(name="wait_pickup_days", nullable=true, type="string", options={"comment":"最长预约时间，天", "default":0})
     */
    private $wait_pickup_days = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="latest_pickup_time", nullable=true, type="string", options={"comment":"当前最晚提货时间",})
     */
    private $latest_pickup_time;

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
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return PickupLocation
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return PickupLocation
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
     * Set relDistributorId
     *
     * @param integer $relDistributorId
     *
     * @return PickupLocation
     */
    public function setRelDistributorId($relDistributorId)
    {
        $this->rel_distributor_id = $relDistributorId;

        return $this;
    }

    /**
     * Get relDistributorId
     *
     * @return integer
     */
    public function getRelDistributorId()
    {
        return $this->rel_distributor_id;
    }

    /**
     * Set name
     *
     * @param integer $name
     *
     * @return PickupLocation
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
     * @return PickupLocation
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
     * @return PickupLocation
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
     * @return PickupLocation
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
     * @return PickupLocation
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
     * @return PickupLocation
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
     * @return PickupLocation
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
     * @return PickupLocation
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
     * Set hours
     *
     * @param integer $hours
     *
     * @return PickupLocation
     */
    public function setHours($hours)
    {
        $this->hours = $hours;

        return $this;
    }

    /**
     * Get hours
     *
     * @return integer
     */
    public function getHours()
    {
        return $this->hours;
    }

    /**
     * Set workdays
     *
     * @param integer $workdays
     *
     * @return PickupLocation
     */
    public function setWorkdays($workdays)
    {
        $this->workdays = $workdays;

        return $this;
    }

    /**
     * Get workdays
     *
     * @return integer
     */
    public function getWorkdays()
    {
        return $this->workdays;
    }

    /**
     * Set waitPickupDays
     *
     * @param integer $waitPickupDays
     *
     * @return PickupLocation
     */
    public function setWaitPickupDays($waitPickupDays)
    {
        $this->wait_pickup_days = $waitPickupDays;

        return $this;
    }

    /**
     * Get waitPickupDays
     *
     * @return integer
     */
    public function getWaitPickupDays()
    {
        return $this->wait_pickup_days;
    }

    /**
     * Set latestPickupTime
     *
     * @param integer $latestPickupTime
     *
     * @return PickupLocation
     */
    public function setLatestPickupTime($latestPickupTime)
    {
        $this->latest_pickup_time = $latestPickupTime;

        return $this;
    }

    /**
     * Get latestPickupTime
     *
     * @return integer
     */
    public function getLatestPickupTime()
    {
        return $this->latest_pickup_time;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return PickupLocation
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
     * @param int $updated
     *
     * @return PickupLocation
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
