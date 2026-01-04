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
 * CommunityOrderRelActivity 社区团购订单关联活动信息表
 *
 * @ORM\Table(name="community_order_rel_activity", options={"comment"="社区团购购物车"})
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityOrderRelActivityRepository")
 */
class CommunityOrderRelActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
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
     * @ORM\Column(name="chief_id", type="bigint", options={"comment":"团长id"})
     */
    private $chief_id;

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
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
     */
    private $activity_id;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", type="string", options={"comment":"活动名称"})
     */
    private $activity_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_name", type="string", nullable=true, options={"comment":"自提点名称"})
     */
    private $ziti_name;

    /**
     * @var string
     *
     * @ORM\Column(name="ziti_address", type="string", length=500, nullable=true, options={"comment":"具体地址"})
     */
    private $ziti_address;

    /**
     * @var string
     *
     * @ORM\Column(name="lng", type="string", nullable=true, options={"comment":"地图纬度"})
     */
    private $ziti_lng;

    /**
     * @var string
     *
     * @ORM\Column(name="lat", type="string", nullable=true, options={"comment":"地图经度"})
     */
    private $ziti_lat;

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
     * @var integer
     *
     * @ORM\Column(name="activity_trade_no", type="integer", nullable=true, options={"comment":"跟团号"})
     */
    private $activity_trade_no;

    /**
     * @var text
     *
     * @ORM\Column(name="extra_data", type="text", nullable=true, options={"comment":"附加信息"})
     */
    private $extra_data;

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
     * @var string
     *
     * @ORM\Column(name="rebate_ratio", type="string", length=20, options={"comment":"佣金比例", "default": 0})
     */
    private $rebate_ratio = 0;

    /**
     * Set orderId.
     *
     * @param integer $orderId
     *
     * @return CommunityOrderRelActivity
     */
    public function setOrderId($orderId)
    {
        $this->order_id = $orderId;

        return $this;
    }

    /**
     * Get orderId.
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
     * @return CommunityOrderRelActivity
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
     * Set chiefId
     *
     * @param integer $chiefId
     *
     * @return CommunityOrderRelActivity
     */
    public function setChiefId($chiefId)
    {
        $this->chief_id = $chiefId;

        return $this;
    }

    /**
     * Get chiefId
     *
     * @return integer
     */
    public function getChiefId()
    {
        return $this->chief_id;
    }

    /**
     * set chiefName
     *
     * @param string $chiefName
     *
     * @return CommunityOrderRelActivity
     */
    public function setChiefName($chiefName)
    {
        $this->chief_name = $chiefName;

        return $this;
    }

    /**
     * get chiefName
     *
     * @return string
     */
    public function getChiefName()
    {
        return $this->chief_name;
    }

    /**
     * set chiefAvatar
     *
     * @param string $chiefAvatar
     *
     * @return CommunityOrderRelActivity
     */
    public function setChiefAvatar($chiefAvatar)
    {
        $this->chief_avatar = $chiefAvatar;

        return $this;
    }

    /**
     * get chiefAvatar
     *
     * @return string
     */
    public function getChiefAvatar()
    {
        return $this->chief_avatar;
    }

    /**
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return CommunityOrderRelActivity
     */
    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    /**
     * Get activityId
     *
     * @return integer
     */
    public function getActivityId()
    {
        return $this->activity_id;
    }

    /**
     * set activityName
     *
     * @param string $activityName
     *
     * @return CommunityOrderRelActivity
     */
    public function setActivityName($activityName)
    {
        $this->activity_name = $activityName;

        return $this;
    }

    /**
     * get activityName
     *
     * @return string
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }

    /**
     * set zitiName
     *
     * @param string $zitiName
     *
     * @return CommunityOrderRelActivity
     */
    public function setZitiName($zitiName)
    {
        $this->ziti_name = $zitiName;

        return $this;
    }

    /**
     * get zitiName
     *
     * @return string
     */
    public function getZitiName()
    {
        return $this->ziti_name;
    }

    /**
     * set zitiAddress
     *
     * @param string $zitiAddress
     *
     * @return CommunityOrderRelActivity
     */
    public function setZitiAddress($zitiAddress)
    {
        $this->ziti_address = $zitiAddress;

        return $this;
    }

    /**
     * get zitiAddress
     *
     * @return string
     */
    public function getZitiAddress()
    {
        return $this->ziti_address;
    }

    /**
     * set zitiLng
     *
     * @param string $zitiLng
     *
     * @return CommunityOrderRelActivity
     */
    public function setZitiLng($zitiLng)
    {
        $this->ziti_lng = $zitiLng;

        return $this;
    }

    /**
     * get zitiLng
     *
     * @return string
     */
    public function getZitiLng()
    {
        return $this->ziti_lng;
    }

    /**
     * set zitiLat
     *
     * @param string $zitiLat
     *
     * @return CommunityOrderRelActivity
     */
    public function setZitiLat($zitiLat)
    {
        $this->ziti_lat = $zitiLat;

        return $this;
    }

    /**
     * get zitiLat
     *
     * @return string
     */
    public function getZitiLat()
    {
        return $this->ziti_lat;
    }

    /**
     * set zitiContactUser
     *
     * @param string $zitiContactUser
     *
     * @return CommunityOrderRelActivity
     */
    public function setZitiContactUser($zitiContactUser)
    {
        $this->ziti_contact_user = $zitiContactUser;

        return $this;
    }

    /**
     * get zitiContactUser
     *
     * @return string
     */
    public function getZitiContactUser()
    {
        return $this->ziti_contact_user;
    }

    /**
     * set zitiContactMobile
     *
     * @param string $zitiContactMobile
     *
     * @return CommunityOrderRelActivity
     */
    public function setZitiContactMobile($zitiContactMobile)
    {
        $this->ziti_contact_mobile = $zitiContactMobile;

        return $this;
    }

    /**
     * get zitiContactMobile
     *
     * @return string
     */
    public function getZitiContactMobile()
    {
        return $this->ziti_contact_mobile;
    }

    /**
     * Set activityTradeNo
     *
     * @param integer $activityTradeNo
     *
     * @return CommunityOrderRelActivity
     */
    public function setActivityTradeNo($activityTradeNo)
    {
        $this->activity_trade_no = $activityTradeNo;

        return $this;
    }

    /**
     * Get activityTradeNo
     *
     * @return integer
     */
    public function getActivityTradeNo()
    {
        return $this->activity_trade_no;
    }

    /**
     * Set extraData
     *
     * @param string $extraData
     *
     * @return CommunityOrderRelActivity
     */
    public function setExtraData($extraData)
    {
        $this->extra_data = $extraData;

        return $this;
    }

    /**
     * Get extraData
     *
     * @return string
     */
    public function getExtraData()
    {
        return $this->extra_data;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return CommunityOrderRelActivity
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
     * @return CommunityOrderRelActivity
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
     * Set rebateRatio
     *
     * @param string $rebateRatio
     *
     * @return CommunityOrderRelActivity
     */
    public function setRebateRatio($rebateRatio)
    {
        $this->rebate_ratio = $rebateRatio;

        return $this;
    }

    /**
     * Get rebateRatio
     *
     * @return string
     */
    public function getRebateRatio()
    {
        return $this->rebate_ratio;
    }
}
