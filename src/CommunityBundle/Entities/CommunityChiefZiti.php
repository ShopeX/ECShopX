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

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * community_chief_ziti 社区拼团团长自提点管理表
 *
 * @ORM\Table(name="community_chief_ziti", options={"comment"="社区拼团团长自提点管理表"}, indexes={
 *    @ORM\Index(name="ix_chief_id", columns={"chief_id"}),
 *    @ORM\Index(name="ix_is_default", columns={"is_default"}),
 *    @ORM\Index(name="ix_ziti_status", columns={"ziti_status"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityChiefZitiRepository")
 */
class CommunityChiefZiti
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="ziti_id", type="bigint", options={"comment":"自提点id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $ziti_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="chief_id", type="bigint", options={"comment":"团长ID"})
     */
    private $chief_id;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_name", type="string", options={"comment":"自提点名称"})
     */
    private $ziti_name;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", nullable=true, options={"comment":"省"}))
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", nullable=true, options={"comment":"市"}))
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", nullable=true, options={"comment":"区"}))
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="regions_id", type="json_array", nullable=true, options={"comment":"地区编号集合"}))
     */
    private $regions_id;

    /**
     * @var string
     *
     * @ORM\Column(name="regions", type="json_array", nullable=true, options={"comment":"地区名称集合"}))
     */
    private $regions;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=500, nullable=true, options={"comment":"具体地址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="lng", type="string", nullable=true, options={"comment":"地图纬度"})
     */
    private $lng;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", nullable=true, options={"comment":"地图经度"})
     */
    private $lat;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_contact_user", type="string", nullable=true, options={"comment":"自提点联系人"})
     */
    private $ziti_contact_user;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_contact_mobile", type="string", nullable=true, options={"comment":"自提点联系电话"})
     */
    private $ziti_contact_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_pics", type="string", nullable=true, options={"comment":"自提点图片"})
     */
    private $ziti_pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_default", nullable=true, type="boolean", options={"comment":"是否默认", "default": false})
     */
    private $is_default = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_status", nullable=true, type="string", options={"comment":"自提点状态 success正常 fail作废", "default":"success"})
     */
    private $ziti_status = "success";

    /**
     * @var \DateTime $created_at
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated_at;

    /**
     * get ZitiId
     *
     * @return int
     */
    public function getZitiId()
    {
        // 1e236443e5a30b09910e0d48c994b8e6 core
        return $this->ziti_id;
    }

    /**
     * get ChiefId
     *
     * @return int
     */
    public function getChiefId()
    {
        return $this->chief_id;
    }

    /**
     * set ChiefId
     *
     * @param int $chief_id
     *
     * @return self
     */
    public function setChiefId($chief_id)
    {
        $this->chief_id = $chief_id;
        return $this;
    }

    /**
     * get ZitiName
     *
     * @return string
     */
    public function getZitiName()
    {
        return $this->ziti_name;
    }

    /**
     * set ZitiName
     *
     * @param string $ziti_name
     *
     * @return self
     */
    public function setZitiName($ziti_name)
    {
        $this->ziti_name = $ziti_name;
        return $this;
    }

    /**
     * get Province
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * set Province
     *
     * @param string $province
     *
     * @return self
     */
    public function setProvince($province)
    {
        $this->province = $province;
        return $this;
    }

    /**
     * get City
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * set City
     *
     * @param string $city
     *
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;
        return $this;
    }

    /**
     * get Area
     *
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * set Area
     *
     * @param string $area
     *
     * @return self
     */
    public function setArea($area)
    {
        $this->area = $area;
        return $this;
    }

    /**
     * get RegionsId
     *
     * @return string
     */
    public function getRegionsId()
    {
        return $this->regions_id;
    }

    /**
     * set RegionsId
     *
     * @param string $regions_id
     *
     * @return self
     */
    public function setRegionsId($regions_id)
    {
        $this->regions_id = $regions_id;
        return $this;
    }

    /**
     * get Regions
     *
     * @return string
     */
    public function getRegions()
    {
        return $this->regions;
    }

    /**
     * set Regions
     *
     * @param string $regions
     *
     * @return self
     */
    public function setRegions($regions)
    {
        $this->regions = $regions;
        return $this;
    }

    /**
     * get Address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * set Address
     *
     * @param string $address
     *
     * @return self
     */
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }

    /**
     * get Lng
     *
     * @return string
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * set Lng
     *
     * @param string $lng
     *
     * @return self
     */
    public function setLng($lng)
    {
        $this->lng = $lng;
        return $this;
    }

    /**
     * get Lat
     *
     * @return string
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * set Lat
     *
     * @param string $lat
     *
     * @return self
     */
    public function setLat($lat)
    {
        $this->lat = $lat;
        return $this;
    }

    /**
     * get ZitiContactUser
     *
     * @return string
     */
    public function getZitiContactUser()
    {
        return $this->ziti_contact_user;
    }

    /**
     * set ZitiContactUser
     *
     * @param string $ziti_contact_user
     *
     * @return self
     */
    public function setZitiContactUser($ziti_contact_user)
    {
        $this->ziti_contact_user = $ziti_contact_user;
        return $this;
    }

    /**
     * get ZitiContactMobile
     *
     * @return string
     */
    public function getZitiContactMobile()
    {
        return $this->ziti_contact_mobile;
    }

    /**
     * set ZitiContactMobile
     *
     * @param string $ziti_contact_mobile
     *
     * @return self
     */
    public function setZitiContactMobile($ziti_contact_mobile)
    {
        $this->ziti_contact_mobile = $ziti_contact_mobile;
        return $this;
    }

    /**
     * get ZitiPics
     *
     * @return string
     */
    public function getZitiPics()
    {
        return $this->ziti_pics;
    }

    /**
     * set ZitiPics
     *
     * @param string $ziti_pics
     *
     * @return self
     */
    public function setZitiPics($ziti_pics)
    {
        $this->ziti_pics = $ziti_pics;
        return $this;
    }

    /**
     * get IsDefault
     *
     * @return int
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * set IsDefault
     *
     * @param int $is_default
     *
     * @return self
     */
    public function setIsDefault($is_default)
    {
        $this->is_default = $is_default;
        return $this;
    }

    /**
     * get ZitiStatus
     *
     * @return string
     */
    public function getZitiStatus()
    {
        return $this->ziti_status;
    }

    /**
     * set ZitiStatus
     *
     * @param string $ziti_status
     *
     * @return self
     */
    public function setZitiStatus($ziti_status)
    {
        $this->ziti_status = $ziti_status;
        return $this;
    }

    /**
     * get CreatedAt
     *
     * @return \DateTime
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * set CreatedAt
     *
     * @param \DateTime $created_at
     *
     * @return self
     */
    public function setCreatedAt($created_at)
    {
        $this->created_at = $created_at;
        return $this;
    }

    /**
     * get UpdatedAt
     *
     * @return \DateTime
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }

    /**
     * set UpdatedAt
     *
     * @param \DateTime $updated_at
     *
     * @return self
     */
    public function setUpdatedAt($updated_at)
    {
        $this->updated_at = $updated_at;
        return $this;
    }


}
