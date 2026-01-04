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

//笔记的图片
namespace WsugcBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * WsugcPostImage 图片
 *
 * @ORM\Table(name="wsugc_post_image", options={"comment"="笔记的图片"}, indexes={
 *    @ORM\Index(name="idx_post_id", columns={"post_id"})
 * }),
  * @ORM\Entity(repositoryClass="WsugcBundle\Repositories\ImageRepository")
 */
class Image
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="post_image_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $post_image_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="post_id", type="bigint", nullable=true,options={"comment":"笔记id","default":"0"})
     */
    private $post_id=0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true,options={"comment":"user_id","default":"0"})
     */
    private $user_id=0;

    /**
     * @var integer
     *
     * @ORM\Column(name="image_id", type="bigint", nullable=true,options={"comment":"image_id","default":"0"})
     */
    private $image_id=0;

    
    /**
     * @var integer
     *
     * @ORM\Column(name="image_url", type="string", options={"comment":"图片地址相对"})
     */
    private $image_url;

    /**
     * @var integer
     *
     * @ORM\Column(name="p_order", type="integer", nullable=true,options={"comment":"排序","default":"50"})
     */
    private $p_order;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_joiner_condition", type="text", nullable=true,options={"comment":"学员要求"})
     */
    private $activity_joiner_condition;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_brief", type="text", nullable=true,options={"comment":"活动简介"})
     */
    private $activity_brief;


    /**
     * @var integer
     *
     * @ORM\Column(name="yuyue_begin_time", type="integer", nullable=true,options={"comment":"活动报名开始时间"})
     */
    private $yuyue_begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="yuyue_end_time", type="integer", nullable=true,options={"comment":"活动报名结束时间"})
     */
    private $yuyue_end_time;


    /**
     * @var interge
     *
     * @ORM\Column(name="cat_id", type="text", nullable=true,options={"comment":"活动类型"})
     */
    private $cat_id;

    /**
     * @var string
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"活动开始时间"})
     */
    private $start_time;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"活动结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="join_limit", type="integer", options={"comment":"可参与次数", "default":0})
     */
    private $join_limit = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_number", type="integer", options={"comment":"限制人数", "default":0})
     */
    private $limit_number = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="teacher_title", type="string", options={"comment":"老师头衔", "default":""})
     */
    private $teacher_title = "";

    /**
     * @var string
     *
     * @ORM\Column(name="teacher_name", type="string", options={"comment":"可参与次数", "default":""})
     */
    private $teacher_name = "";

    /**
     * @var text
     *
     * @ORM\Column(name="teacher_brief", type="text", options={"comment":"可参与次数", "default":""})
     */
    private $teacher_brief = "";

  /**
     * @var string
     *
     * @ORM\Column(name="teacher_avatar", type="string", options={"comment":"可参与次数", "default":""})
     */
    private $teacher_avatar = "";
    /**
     * @var string
     *
     * @ORM\Column(name="is_sms_notice", type="boolean", options={"comment":"是否短信通知", "default": true})
     */
    private $is_sms_notice = false;

    /**
     * @var string
     *
     * @ORM\Column(name="is_wxapp_notice", type="boolean", options={"comment":"是否小程序模板通知", "default": true})
     */
    private $is_wxapp_notice = false;


    /**
     * @var string
     *
     * @ORM\Column(name="enabled", type="integer", options={"comment":"是否启用", "default": 1})
     */
    private $enabled = 1;

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
     * @ORM\Column(name="company_id", type="bigint")
     */
    private $company_id;

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
     * Set tempId
     *
     * @param integer $tempId
     *
     * @return YuyueActivity
     */
    public function setTempId($tempId)
    {
        $this->temp_id = $tempId;

        return $this;
    }

    /**
     * Get tempId
     *
     * @return integer
     */
    public function getTempId()
    {
        return $this->temp_id;
    }

    /**
     * Set activityName
     *
     * @param integer $activityName
     *
     * @return YuyueActivity
     */
    public function setActivityName($activityName)
    {
        $this->activity_name = $activityName;

        return $this;
    }

    /**
     * Get activityName
     *
     * @return integer
     */
    public function getActivityName()
    {
        return $this->activity_name;
    }
    /**
     * Get activityCover
     *
     * @return integer
     */
    public function getActivityCover()
    {
        return $this->activity_cover;
    }
    /**
     * Get giveCoupon
     *
     * @return string
     */
    public function getGiveCoupon()
    {
        return $this->give_coupon;
    }
     /**
     * Set activityCover
     *
     * @param string $activityCover
     *
     * @return RegistrationActivity
     */
    public function setActivityCover($activityCover)
    {
        $this->activity_cover = $activityCover;

        return $this;
    }
    /**
     * Set give_coupon
     *
     * @param string $activityCover
     *
     * @return RegistrationActivity
     */
    public function setGiveCoupon($giveCoupon)
    {
        $this->give_coupon = $giveCoupon;

        return $this;
    }
    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return YuyueActivity
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return YuyueActivity
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set joinLimit
     *
     * @param integer $joinLimit
     *
     * @return YuyueActivity
     */
    public function setJoinLimit($joinLimit)
    {
        $this->join_limit = $joinLimit;

        return $this;
    }

    /**
     * Get joinLimit
     *
     * @return integer
     */
    public function getJoinLimit()
    {
        return $this->join_limit;
    }
    /**
     * Set limitNumber
     *
     * @param integer $limitNumber
     *
     * @return YuyueActivity
     */
    public function setLimitNumber($limitNumber)
    {
        $this->limit_number = $limitNumber;

        return $this;
    }

    /**
     * Get limitNumber
     *
     * @return integer
     */
    public function getLimitNumber()
    {
        return $this->limit_number;
    }

    /**
     * Set isNotify
     *
     * @param boolean $isNotify
     *
     * @return YuyueActivity
     */
    public function setIsNotify($isNotify)
    {
        $this->is_notify = $isNotify;

        return $this;
    }

    /**
     * Get isNotify
     *
     * @return boolean
     */
    public function getIsNotify()
    {
        return $this->is_notify;
    }

    /**
     * Set isWxappNotice
     *
     * @param boolean $isWxappNotice
     *
     * @return YuyueActivity
     */
    public function setIsWxappNotice($isWxappNotice)
    {
        $this->is_wxapp_notice = $isWxappNotice;

        return $this;
    }

    /**
     * Get isWxappNotice
     *
     * @return boolean
     */
    public function getIsWxappNotice()
    {
        return $this->is_wxapp_notice;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return YuyueActivity
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
     * @return YuyueActivity
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
     * Set isSmsNotice
     *
     * @param boolean $isSmsNotice
     *
     * @return YuyueActivity
     */
    public function setIsSmsNotice($isSmsNotice)
    {
        $this->is_sms_notice = $isSmsNotice;

        return $this;
    }

    /**
     * Get isSmsNotice
     *
     * @return boolean
     */
    public function getIsSmsNotice()
    {
        return $this->is_sms_notice;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return YuyueActivity
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
     * Set activityBrief
     *
     * @param string $activityBrief
     *
     * @return YuyueActivity
     */
    public function setActivityBrief($activityBrief)
    {
        $this->activity_brief = $activityBrief;

        return $this;
    }

    /**
     * Get activityBrief
     *
     * @return string
     */
    public function getActivityBrief()
    {
        return $this->activity_brief;
    }
    /**
     * Set activityIntro
     *
     * @param string $activityIntro
     *
     * @return YuyueActivity
     */
    public function setActivityIntro($activityIntro)
    {
        $this->activity_intro = $activityIntro;

        return $this;
    }

    /**
     * Get activityIntro
     *
     * @return string
     */
    public function getActivityIntro()
    {
        return $this->activity_intro;
    }
    /**
     * Set yuyueBeginTime
     *
     * @param string $yuyueBeginTime
     *
     * @return YuyueActivity
     */
    public function setYuyueBeginTime($yuyueBeginTime)
    {
        $this->yuyue_begin_time = $yuyueBeginTime;

        return $this;
    }

    /**
     * Get yuyueBeginTime
     *
     * @return string
     */
    public function getYuyueBeginTime()
    {
        return $this->yuyue_begin_time;
    }
    /**
     * Set yuyueEndTime
     *
     * @param string $yuyueEndTime
     *
     * @return YuyueActivity
     */
    public function setYuyueEndTime($yuyueEndTime)
    {
        $this->yuyue_end_time = $yuyueEndTime;

        return $this;
    }

    /**
     * Get yuyueEndTime
     *
     * @return string
     */
    public function getYuyueEndTime()
    {
        return $this->yuyue_end_time;
    }
    /**
     * Get activityJoinerCondition
     *
     * @return string
     */
    public function getActivityJoinerCondition()
    {
        return $this->activity_joiner_condition;
    }
    /**
     * Set activityJoinerCondition
     *
     * @param string $activityJoinerCondition
     *
     * @return YuyueActivity
     */
    public function setActivityJoinerCondition($activityJoinerCondition)
    {
        $this->activity_joiner_condition = $activityJoinerCondition;

        return $this;
    }



    /**
     * Set onsaleTime
     *
     * @param string $onsaleTime
     *
     * @return YuyueActivity
     */
    public function setOnsaleTime($onsaleTime)
    {
        $this->onsale_time = $onsaleTime;

        return $this;
    }

    /**
     * Get onsaleTime
     *
     * @return string
     */
    public function getOnsaleTime()
    {
        return $this->onsale_time;
    }

    /**
     * Set cancelBefore
     *
     * @param integer $cancelBefore
     *
     * @return YuyueActivity
     */
    public function setCancelBefore($cancelBefore)
    {
        $this->cancel_before = $cancelBefore;

        return $this;
    }

    /**
     * Get cancelBefore
     *
     * @return string
     */
    public function getCancelBefore()
    {
        return $this->cancel_before;
    }

    /**
     * Set address
     *
     * @param string $address
     *
     * @return YuyueActivity
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
     * Set cityId
     *
     * @param string $cityId
     *
     * @return YuyueActivity
     */
    public function setCityId($cityId)
    {
        $this->city_id = $cityId;

        return $this;
    }

    /**
     * Get cityId
     *
     * @return string
     */
    public function getCityId()
    {
        return $this->city_id;
    }
        /**
     * Set cityName
     *
     * @param string $cityName
     *
     * @return YuyueActivity
     */
    public function setCityName($cityName)
    {
        $this->city_name = $cityName;

        return $this;
    }

    /**
     * Get citName
     *
     * @return string
     */
    public function getCityName()
    {
        return $this->city_name;
    }
    /**
     * Set give_point
     *
     * @param string $givePoint
     *
     * @return RegistrationActivity
     */
    public function getGivePoint()
    {
        return $this->give_point;
    }
        /**
     * Set give_point
     *
     * @param string $givePoint
     *
     * @return RegistrationActivity
     */
    public function setGivePoint($givePoint)
    {
        $this->give_point = $givePoint;

        return $this;
    }
    /**
     * Set goods_id
     *
     * @param string $goodsId
     *
     * @return RegistrationActivity
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }
        /**
     * Set goods_id
     *
     * @param string $goodsId
     *
     * @return RegistrationActivity
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }
    /**
     * Set pOrder
     *
     * @param string $porder
     *
     * @return YuyueActivity
     */
    public function setPorder($pOrder)
    {
        $this->p_order = $pOrder;

        return $this;
    }

    /**
     * Get pOrder
     *
     * @return string
     */
    public function getPorder()
    {
        return $this->p_order;
    }

    /**
     * Set needFee
     *
     * @param string $needFee
     *
     * @return YuyueActivity
     */
    public function setNeedFee($needFee)
    {
        $this->need_fee = $needFee;

        return $this;
    }

    /**
     * Get needFee
     *
     * @return string
     */
    public function getNeedFee()
    {
        return $this->need_fee;
    }
    /**
     * Set activityFee
     *
     * @param string $activityFee
     *
     * @return YuyueActivity
     */
    public function setActivityFee($activityFee)
    {
        $this->activity_fee = $activityFee;

        return $this;
    }

    /**
     * Get activityFee
     *
     * @return string
     */
    public function getActivityFee()
    {
        return $this->activity_fee;
    }
    /**
     * Set activityImages
     *
     * @param string $activityImages
     *
     * @return YuyueActivity
     */
    public function setActivityImages($activityImages)
    {
        $this->activity_images = $activityImages;

        return $this;
    }

    /**
     * Get activityImages
     *
     * @return string
     */
    public function getActivityImages()
    {
        return $this->activity_images;
    }

    /**
     * Get teacherName
     *
     * @param string $teacherName
     *
     * @return YuyueActivity
     */
    public function getTeacherName()
    {
        return $this->teacher_name;

    }
    /**
     * Set teacherName
     *
     * @param string $teacherName
     *
     * @return YuyueActivity
     */
    public function setTeacherName($teacherName)
    {
        $this->teacher_name = $teacherName;
        return $this;
    }
       /**
     * Get teacherTitle
     *
     * @param string $teacherTitle
     *
     * @return YuyueActivity
     */
    public function getTeacherTitle()
    {
        return $this->teacher_title;

    }
    /**
     * Set teacherTitle
     *
     * @param string $teacherTitle
     *
     * @return YuyueActivity
     */
    public function setTeacherTitle($teacherTitle)
    {
        $this->teacher_title = $teacherTitle;
        return $this;
    }
     /**
     * Get teacherBrief
     *
     * @param string $teacherBrief
     *
     * @return YuyueActivity
     */
    public function getTeacherBrief()
    {
        return $this->teacher_brief;
    }
    /**
     * Set teacherBrief
     *
     * @param string $teacherBrief
     *
     * @return YuyueActivity
     */
    public function setTeacherBrief($teacherBrief)
    {
        $this->teacher_brief = $teacherBrief;

        return $this;
    }
    /**
     * Get teacherAvatar
     *
     * @param string $teacherAvatar
     *
     * @return YuyueActivity
     */
    public function getTeacherAvatar()
    {
        return $this->teacher_avatar;
    }
    /**
     * Set teacherAvatar
     *
     * @param string $teacherAvatar
     *
     * @return YuyueActivity
     */
    public function setTeacherAvatar($teacherAvatar)
    {
        $this->teacher_avatar = $teacherAvatar;

        return $this;
    }
    /**
     * Get catId
     *
     * @param string $catId
     *
     * @return YuyueActivity
     */
    public function getCatId()
    {
        return $this->cat_id;
    }
    /**
     * Set catId
     *
     * @param string $catId
     *
     * @return YuyueActivity
     */
    public function setCatId($catId)
    {
        $this->cat_id = $catId;

        return $this;
    }
     /**
     * Get enabled
     *
     * @param string $enabled
     *
     * @return YuyueActivity
     */
    public function getEnabled()
    {
        return $this->enabled;
    }
    /**
     * Set enabled
     *
     * @param string $enabled
     *
     * @return YuyueActivity
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }

    /**
     * Get picId.
     *
     * @return int
     */
    public function getPicId()
    {
        return $this->pic_id;
    }

    /**
     * Get postImageId.
     *
     * @return int
     */
    public function getPostImageId()
    {
        return $this->post_image_id;
    }

    /**
     * Set postId.
     *
     * @param int|null $postId
     *
     * @return Image
     */
    public function setPostId($postId = null)
    {
        $this->post_id = $postId;

        return $this;
    }

    /**
     * Get postId.
     *
     * @return int|null
     */
    public function getPostId()
    {
        return $this->post_id;
    }

    /**
     * Set userId.
     *
     * @param int|null $userId
     *
     * @return Image
     */
    public function setUserId($userId = null)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId.
     *
     * @return int|null
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set imageId.
     *
     * @param int|null $imageId
     *
     * @return Image
     */
    public function setImageId($imageId = null)
    {
        $this->image_id = $imageId;

        return $this;
    }

    /**
     * Get imageId.
     *
     * @return int|null
     */
    public function getImageId()
    {
        return $this->image_id;
    }

    /**
     * Set imageUrl.
     *
     * @param string $imageUrl
     *
     * @return Image
     */
    public function setImageUrl($imageUrl)
    {
        $this->image_url = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl.
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }
}
