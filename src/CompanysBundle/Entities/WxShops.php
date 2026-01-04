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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

// use LaravelDoctrine\Extensions\Timestamps\Timestamps;

/**
 * WxShops 微信门店表
 *
 * @ORM\Table(name="wxshops", options={"comment":"微信门店表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\WxShopsRepository")
 */
class WxShops
{
    // use Timestamps;
    // use SoftDeletes;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="wx_shop_id", type="bigint", options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $wx_shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="map_poi_id", type="string", nullable=true, options={"comment":"从腾讯地图换取的位置点id，即search_map_poi接口返回的sosomap_poi_uid字段"})
     */
    private $map_poi_id;

    /**
     * @var string
     *
     * @ORM\Column(name="store_name", type="string", length=100, nullable=true, options={"comment":"腾讯地图的门店名称"})
     */
    private $store_name;

    /**
     * @var string
     *
     * @ORM\Column(name="poi_id", type="string", nullable=true, options={"comment":"门店id"})
     */
    private $poi_id;

    /**
     * @var string
     *
     * @ORM\Column(name="lng", type="string", nullable=true, options={"comment":"腾讯地图纬度"})
     */
    private $lng;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", nullable=true, options={"comment":"腾讯地图经度"})
     */
    private $lat;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length=500, options={"comment":"腾讯地图门店地址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", nullable=true, options={"comment":"腾讯地图门店类目"})
     */
    private $category;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"门店所属店铺ID", "default":0})
     */
    private $distributor_id;

    /**
     * @var string
     *
     * @ORM\Column(name="pic_list", type="text", nullable=true, options={"comment":"门店图片，可传多张图片pic_list字段是一个json"})
     */
    private $pic_list;

    /**
     * @var string
     *
     * @ORM\Column(name="contract_phone", type="string", length=20, options={"comment":"联系电话"})
     */
    private $contract_phone;

    /**
     * @var integer
     *
     * @ORM\Column(name="add_type", type="smallint", options={"comment":"1,公众号主体；2,相关主体; 3,无主体"})
     */
    private $add_type = 3;


    /**
     * @var string
     *
     * @ORM\Column(name="hour", type="string", length=20, options={"comment":"营业时间，格式11:11-12:12"})
     */
    private $hour;

    /**
     * @var string
     *
     * @ORM\Column(name="credential", type="string", nullable=true, length=30, options={"comment":"经营资质证件号"})
     */
    private $credential;

    /**
     * @var string
     *
     * @ORM\Column(name="company_name", type="string", length=30, nullable=true, options={"comment":"主体名字 临时素材mediaid，如果复用公众号主体，则company_name为空，如果不复用公众号主体，则company_name为具体的主体名字"})
     */
    private $company_name;

    /**
     * @var string
     *
     * @ORM\Column(name="qualification_list", type="string", length=255, nullable=true, options={"comment":"相关证明材料，临时素材mediaid，不复用公众号主体时，才需要填"})
     */
    private $qualification_list;

    /**
     * @var string
     *
     * @ORM\Column(name="card_id", type="string", length=20, nullable=true, options={"comment":"卡券id，如果不需要添加卡券，该参数可为空，目前仅开放支持会员卡、买单和刷卡支付券，不支持自定义code，需要先去公众平台卡券后台创建cardid"})
     */
    private $card_id;

    /**
     * @var smallint
     *
     * @ORM\Column(name="status", type="smallint", options={"comment":"审核状态，1：审核成功，2：审核中，3：审核失败，4：管理员拒绝, 5: 无需审核"})
     */
    private $status = 5;

    /**
     * @var string
     *
     * @ORM\Column(name="errmsg", type="string", length=255, nullable=true, options={"comment":"审核失败原因"})
     */
    private $errmsg;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="audit_id", type="string", length=20, nullable=true, options={"comment":"微信返回的审核id"})
     */
    private $audit_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_id", type="bigint", nullable=true, options={"comment":"资源包id"})
     */
    private $resource_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="expired_at", type="bigint", nullable=true, options={"comment":"过期时间"})
     */
    private $expired_at;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_default", type="boolean", options={"comment":"是否是默认门店", "default":false})
     */
    private $is_default = false;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=100, nullable=true, options={"comment":"非中国国家名称"})
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="city", type="string", length=100, nullable=true, options={"comment":"非中国门店所在城市"})
     */
    private $city;

    /**
     * @var string
     *
     * @ORM\Column(name="is_domestic", type="smallint", length=1, nullable=true, options={"comment":"是否是中国国内门店 1:国内(包含港澳台),2:非国内", "default": 1})
     */
    private $is_domestic = 1;

    /**
     * @var string
     *
     *
     * @ORM\Column(name="is_direct_store", type="smallint", length=1, nullable=true, options={"comment":"是否为直营店 1:直营店,2:非直营店", "default": 1})
     */
    private $is_direct_store = 1;

    /**
     * @var integer
     *
     *
     * @ORM\Column(name="is_open", type="boolean", length=1, nullable=true, options={"comment":"是否开启 1:开启,0:关闭", "default":true})
     */
    private $is_open = true;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * Get wxShopId
     *
     * @return integer
     */
    public function getWxShopId()
    {
        return $this->wx_shop_id;
    }

    /**
     * Set mpPoiId
     *
     * @param string $mpPoiId
     *
     * @return WxShops
     */
    public function setMapPoiId($mapPoiId)
    {
        $this->map_poi_id = $mapPoiId;

        return $this;
    }

    /**
     * Get mpPoiId
     *
     * @return string
     */
    public function getMapPoiId()
    {
        return $this->map_poi_id;
    }

    /**
     * Set picList
     *
     * @param string $picList
     *
     * @return WxShops
     */
    public function setPicList($picList)
    {
        $this->pic_list = $picList;

        return $this;
    }

    /**
     * Get picList
     *
     * @return string
     */
    public function getPicList()
    {
        return $this->pic_list;
    }

    /**
     * Set contractPhone
     *
     * @param string $contractPhone
     *
     * @return WxShops
     */
    public function setContractPhone($contractPhone)
    {
        $this->contract_phone = $contractPhone;

        return $this;
    }

    /**
     * Get contractPhone
     *
     * @return string
     */
    public function getContractPhone()
    {
        return $this->contract_phone;
    }

    /**
     * Set hour
     *
     * @param string $hour
     *
     * @return WxShops
     */
    public function setHour($hour)
    {
        $this->hour = $hour;

        return $this;
    }

    /**
     * Get hour
     *
     * @return string
     */
    public function getHour()
    {
        return $this->hour;
    }

    /**
     * Set credential
     *
     * @param string $credential
     *
     * @return WxShops
     */
    public function setCredential($credential)
    {
        $this->credential = $credential;

        return $this;
    }

    /**
     * Get credential
     *
     * @return string
     */
    public function getCredential()
    {
        return $this->credential;
    }

    /**
     * Set companyName
     *
     * @param string $companyName
     *
     * @return WxShops
     */
    public function setCompanyName($companyName)
    {
        $this->company_name = $companyName;

        return $this;
    }

    /**
     * Get companyName
     *
     * @return string
     */
    public function getCompanyName()
    {
        return $this->company_name;
    }

    /**
     * Set qualificationList
     *
     * @param string $qualificationList
     *
     * @return WxShops
     */
    public function setQualificationList($qualificationList)
    {
        $this->qualification_list = $qualificationList;

        return $this;
    }

    /**
     * Get qualificationList
     *
     * @return string
     */
    public function getQualificationList()
    {
        return $this->qualification_list;
    }

    /**
     * Set cardId
     *
     * @param string $cardId
     *
     * @return WxShops
     */
    public function setCardId($cardId)
    {
        $this->card_id = $cardId;

        return $this;
    }

    /**
     * Get cardId
     *
     * @return string
     */
    public function getCardId()
    {
        return $this->card_id;
    }

    /**
     * Set status
     *
     * @param integer $status
     *
     * @return WxShops
     */
    public function setStatus($status)
    {
        if (!in_array($status, [1, 2, 3, 4])) {
            throw new \InvalidArgumentException("Invalid status");
        }
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return integer
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WxShops
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
     * Set created
     *
     * @param \DateTime $created
     *
     * @return WxShops
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return \DateTime
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param \DateTime $updated
     *
     * @return WxShops
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return \DateTime
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set lng
     *
     * @param string $lng
     *
     * @return WxShops
     */
    public function setLng($lng)
    {
        $this->lng = $lng;

        return $this;
    }

    /**
     * Get lng
     *
     * @return string
     */
    public function getLng()
    {
        return $this->lng;
    }

    /**
     * Set lat
     *
     * @param string $lat
     *
     * @return WxShops
     */
    public function setLat($lat)
    {
        $this->lat = $lat;

        return $this;
    }

    /**
     * Get lat
     *
     * @return string
     */
    public function getLat()
    {
        return $this->lat;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return WxShops
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * Get address
     *
     * @return string
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Set category
     *
     * @param string $category
     *
     * @return WxShops
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set poiId
     *
     * @param string $poiId
     *
     * @return WxShops
     */
    public function setPoiId($poiId)
    {
        $this->poi_id = $poiId;

        return $this;
    }

    /**
     * Get poiId
     *
     * @return string
     */
    public function getPoiId()
    {
        return $this->poi_id;
    }

    /**
     * Set errmsg
     *
     * @param string $errmsg
     *
     * @return WxShops
     */
    public function setErrmsg($errmsg)
    {
        $this->errmsg = $errmsg;

        return $this;
    }

    /**
     * Get errmsg
     *
     * @return string
     */
    public function getErrmsg()
    {
        return $this->errmsg;
    }

    /**
     * Set auditId
     *
     * @param string $auditId
     *
     * @return WxShops
     */
    public function setAuditId($auditId)
    {
        $this->audit_id = $auditId;

        return $this;
    }

    /**
     * Get auditId
     *
     * @return string
     */
    public function getAuditId()
    {
        return $this->audit_id;
    }

    /**
     * Set resourceId
     *
     * @param integer $resourceId
     *
     * @return WxShops
     */
    public function setResourceId($resourceId)
    {
        $this->resource_id = $resourceId;

        return $this;
    }

    /**
     * Get resourceId
     *
     * @return integer
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * Set expiredAt
     *
     * @param integer $expiredAt
     *
     * @return WxShops
     */
    public function setExpiredAt($expiredAt)
    {
        $this->expired_at = $expiredAt;

        return $this;
    }

    /**
     * Get expiredAt
     *
     * @return integer
     */
    public function getExpiredAt()
    {
        return $this->expired_at;
    }

    /**
     * Set isDefault
     *
     * @param boolean $isDefault
     *
     * @return WxShops
     */
    public function setIsDefault($isDefault)
    {
        $this->is_default = $isDefault;

        return $this;
    }

    /**
     * Get isDefault
     *
     * @return boolean
     */
    public function getIsDefault()
    {
        return $this->is_default;
    }

    /**
     * Set storeName
     *
     * @param string $storeName
     *
     * @return WxShops
     */
    public function setStoreName($storeName)
    {
        $this->store_name = $storeName;

        return $this;
    }

    /**
     * Get storeName
     *
     * @return string
     */
    public function getStoreName()
    {
        return $this->store_name;
    }

    /**
     * Set addType
     *
     * @param boolean $addType
     *
     * @return WxShops
     */
    public function setAddType($addType)
    {
        if (!in_array($addType, [1, 2, 3])) {
            throw new \InvalidArgumentException("Invalid api param add_type");
        }

        $this->add_type = $addType;

        return $this;
    }

    /**
     * Get addType
     *
     * @return boolean
     */
    public function getAddType()
    {
        return $this->add_type;
    }

    /**
     * Set country
     *
     * @param string $country
     *
     * @return WxShops
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set city
     *
     * @param string $city
     *
     * @return WxShops
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
     * Set isDomestic
     *
     * @param integer $isDomestic
     *
     * @return WxShops
     */
    public function setIsDomestic($isDomestic)
    {
        if (!in_array($isDomestic, [1, 2])) {
            throw new \InvalidArgumentException("Invalid api param is_domestic");
        }

        $this->is_domestic = $isDomestic;

        return $this;
    }

    /**
     * Get isDomestic
     *
     * @return integer
     */
    public function getIsDomestic()
    {
        return $this->is_domestic;
    }

    /**
     * Set isDirectStore
     *
     * @param integer $isDirectStore
     *
     * @return WxShops
     */
    public function setIsDirectStore($isDirectStore)
    {
        if (!in_array($isDirectStore, [1, 2])) {
            throw new \InvalidArgumentException("Invalid api param is_direct_store");
        }

        $this->is_direct_store = $isDirectStore;

        return $this;
    }

    /**
     * Get isDirectStore
     *
     * @return integer
     */
    public function getIsDirectStore()
    {
        return $this->is_direct_store;
    }

    /**
     * Set isOpen
     *
     * @param integer $isOpen
     *
     * @return WxShops
     */
    public function setIsOpen($isOpen)
    {
        $this->is_open = $isOpen;

        return $this;
    }

    /**
     * Get isOpen
     *
     * @return integer
     */
    public function getIsOpen()
    {
        return $this->is_open;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return WxShops
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
}
