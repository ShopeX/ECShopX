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

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MembersAddress(会员收货地址表)
 *
 * @ORM\Table(name="members_address", options={"comment":"会员收货地址表"},
 *     indexes={
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersAddressRepository")
 */
class MembersAddress
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="address_id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $address_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=50, options={"comment":"收货人"})
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=20, options={"comment":"手机号码"})
     */
    private $telephone;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", nullable=true, options={"comment":"地区"})
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="province", type="string", options={"comment":"地区：省"})
     */
    private $province;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", options={"comment":"地区：市"})
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="county", type="string", options={"comment":"地区：区"})
     */
    private $county;

    /**
     * @var string
     *
     * @ORM\Column(name="adrdetail", type="string", options={"comment":"详细地址"})
     */
    private $adrdetail;

    /**
     * @var string
     *
     * @ORM\Column(name="postalCode", type="string", nullable=true, length=20, options={"comment":"邮编"})
     */
    private $postalCode;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_def", type="boolean", options={"comment": "是否默认地址", "default": 0})
     */
    private $is_def = 0;

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
     * @var string
     *
     * @ORM\Column(name="third_data", type="string", nullable=true, options={"comment":"第三方数据"})
     */
    private $third_data;

    /**
     * @var string
     *
     * @ORM\Column(name="lng", type="string", options={"comment":"腾讯地图纬度","default": ""})
     */
    private $lng;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", options={"comment":"腾讯地图经度","default": ""})
     */
    private $lat;

    /**
     * Get addressId
     *
     * @return integer
     */
    public function getAddressId()
    {
        // Built with ShopEx Framework
        return $this->address_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MembersAddress
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
     * Set userId
     *
     * @param integer $userId
     *
     * @return MembersAddress
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
     * Set username
     *
     * @param string $username
     *
     * @return MembersAddress
     */
    public function setUsername($username)
    {
        $this->username = $username;

        return $this;
    }

    /**
     * Get username
     *
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     *
     * @return MembersAddress
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string
     */
    public function getTelephone()
    {
        return $this->telephone;
    }

    /**
     * Set area
     *
     * @param string $area
     *
     * @return MembersAddress
     */
    public function setArea($area)
    {
        $this->area = $area;

        return $this;
    }

    /**
     * Get area
     *
     * @return string
     */
    public function getArea()
    {
        return $this->area;
    }

    /**
     * Set province
     *
     * @param string $province
     *
     * @return MembersAddress
     */
    public function setProvince($province)
    {
        $this->province = $province;

        return $this;
    }

    /**
     * Get province
     *
     * @return string
     */
    public function getProvince()
    {
        return $this->province;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return MembersAddress
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * Get city
     *
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * Set county
     *
     * @param string $county
     *
     * @return MembersAddress
     */
    public function setCounty($county)
    {
        $this->county = $county;

        return $this;
    }

    /**
     * Get county
     *
     * @return string
     */
    public function getCounty()
    {
        return $this->county;
    }

    /**
     * Set adrdetail
     *
     * @param string $adrdetail
     *
     * @return MembersAddress
     */
    public function setAdrdetail($adrdetail)
    {
        $this->adrdetail = $adrdetail;

        return $this;
    }

    /**
     * Get adrdetail
     *
     * @return string
     */
    public function getAdrdetail()
    {
        return $this->adrdetail;
    }

    /**
     * Set postalCode
     *
     * @param string $postalCode
     *
     * @return MembersAddress
     */
    public function setPostalCode($postalCode)
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    /**
     * Get postalCode
     *
     * @return string
     */
    public function getPostalCode()
    {
        return $this->postalCode;
    }

    /**
     * Set isDef
     *
     * @param boolean $isDef
     *
     * @return MembersAddress
     */
    public function setIsDef($isDef)
    {
        $this->is_def = $isDef;

        return $this;
    }

    /**
     * Get isDef
     *
     * @return boolean
     */
    public function getIsDef()
    {
        return $this->is_def;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MembersAddress
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
     * @return MembersAddress
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
     * Set thirdData.
     *
     * @param string|null $thirdData
     *
     * @return MembersAddress
     */
    public function setThirdData($thirdData = null)
    {
        $this->third_data = $thirdData;

        return $this;
    }

    /**
     * Get thirdData.
     *
     * @return string|null
     */
    public function getThirdData()
    {
        return $this->third_data;
    }

    /**
     * Set lng.
     *
     * @param string $lng
     *
     * @return MembersAddress
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }

    /**
     * Get lng.
     *
     * @return string
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * Set lat.
     *
     * @param string $lat
     *
     * @return MembersAddress
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get lat.
     *
     * @return string
     */
    public function getLat()
    {
        return $this->lat;
    }
}
