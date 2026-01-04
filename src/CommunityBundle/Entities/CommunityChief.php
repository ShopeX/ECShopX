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
 * community_chief 社区拼团团长表
 *
 * @ORM\Table(name="community_chief", options={"comment"="社区拼团团长表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_chief_mobile", columns={"chief_mobile"}),
 *    @ORM\Index(name="ix_user_id", columns={"user_id"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityChiefRepository")
 */
class CommunityChief
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="chief_id", type="bigint", options={"comment":"团长id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $chief_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_name", type="string", options={"comment":"团长名称"})
     */
    private $chief_name;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_avatar", type="string", options={"comment":"团长头像"})
     */
    private $chief_avatar;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_mobile", type="string", options={"comment":"团长手机号"})
     */
    private $chief_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_desc", type="string", options={"comment":"团长简介"})
     */
    private $chief_desc;

    /**
     * @var string
     *
     * @ORM\Column(name="chief_intro", type="text", nullable=true, options={"comment":"团长详细介绍"})
     */
    private $chief_intro;

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
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"会员ID", "default": 0})
     */
    private $user_id;

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
     * @var string
     *
     * @ORM\Column(name="alipay_name", nullable=true, type="string", options={"comment":"团长提现的支付宝姓名"})
     */
    private $alipay_name;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_account", nullable=true, type="string", options={"comment":"团长提现的支付宝账号"})
     */
    private $alipay_account;

    /**
     * @var string
     *
     * @ORM\Column(name="bank_name", nullable=true, type="string", options={"comment":"银行名称"})
     */
    private $bank_name;

    /**
     * @var string
     *
     * @ORM\Column(name="bankcard_no", nullable=true, type="string", options={"comment":"团长提现的银行卡号"})
     */
    private $bankcard_no;

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
     * get CompanyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * set CompanyId
     *
     * @param int $company_id
     *
     * @return self
     */
    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
        return $this;
    }

    /**
     * get ChiefName
     *
     * @return string
     */
    public function getChiefName()
    {
        return $this->chief_name;
    }

    /**
     * set ChiefName
     *
     * @param string $chief_name
     *
     * @return self
     */
    public function setChiefName($chief_name)
    {
        $this->chief_name = $chief_name;
        return $this;
    }

    /**
     * get ChiefAvatar
     *
     * @return string
     */
    public function getChiefAvatar()
    {
        return $this->chief_avatar;
    }

    /**
     * set ChiefAvatar
     *
     * @param string $chief_avatar
     *
     * @return self
     */
    public function setChiefAvatar($chief_avatar)
    {
        $this->chief_avatar = $chief_avatar;
        return $this;
    }

    /**
     * get ChiefMobile
     *
     * @return string
     */
    public function getChiefMobile()
    {
        return $this->chief_mobile;
    }

    /**
     * set ChiefMobile
     *
     * @param string $chief_mobile
     *
     * @return self
     */
    public function setChiefMobile($chief_mobile)
    {
        $this->chief_mobile = $chief_mobile;
        return $this;
    }

    /**
     * get ChiefDesc
     *
     * @return string
     */
    public function getChiefDesc()
    {
        return $this->chief_desc;
    }

    /**
     * set ChiefDesc
     *
     * @param string $chief_desc
     *
     * @return self
     */
    public function setChiefDesc($chief_desc)
    {
        $this->chief_desc = $chief_desc;
        return $this;
    }

    /**
     * get ChiefIntro
     *
     * @return string
     */
    public function getChiefIntro()
    {
        return $this->chief_intro;
    }

    /**
     * set ChiefIntro
     *
     * @param string $chief_intro
     *
     * @return self
     */
    public function setChiefIntro($chief_intro)
    {
        $this->chief_intro = $chief_intro;
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
     * get UserId
     *
     * @return int
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * set UserId
     *
     * @param int $user_id
     *
     * @return self
     */
    public function setUserId($user_id)
    {
        $this->user_id = $user_id;
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

    /**
     * Set AlipayName
     *
     * @param string $alipay_name
     *
     * @return self
     */
    public function setAlipayName($alipay_name)
    {
        $this->alipay_name = $alipay_name;

        return $this;
    }

    /**
     * Get AlipayName
     *
     * @return string
     */
    public function getAlipayName()
    {
        return $this->alipay_name;
    }

    /**
     * Set AlipayAccount
     *
     * @param string $alipay_account
     *
     * @return self
     */
    public function setAlipayAccount($alipay_account)
    {
        $this->alipay_account = $alipay_account;

        return $this;
    }

    /**
     * Get AlipayAccount
     *
     * @return string
     */
    public function getAlipayAccount()
    {
        return $this->alipay_account;
    }

    /**
     * Set banktName
     *
     * @param string $banktName
     *
     * @return CommunityChiefCashWithdrawal
     */
    public function setBankName($banktName)
    {
        $this->bank_name = $banktName;

        return $this;
    }

    /**
     * Get banktName
     *
     * @return string
     */
    public function getBankName()
    {
        return $this->bank_name;
    }

    /**
     * Set BankcardNo
     *
     * @param string $bankcard_no
     *
     * @return self
     */
    public function setBankcardNo($bankcard_no)
    {
        $this->bankcard_no = $bankcard_no;

        return $this;
    }

    /**
     * Get BankcardNo
     *
     * @return string
     */
    public function getBankcardNo()
    {
        return $this->bankcard_no;
    }
}
