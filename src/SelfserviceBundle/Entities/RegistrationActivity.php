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

namespace SelfserviceBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * RegistrationActivity 报名问卷活动
 *
 * @ORM\Table(name="selfservice_registration_activity", options={"comment"="报名问卷活动"}, indexes={
 *    @ORM\Index(name="idx_temp_id", columns={"temp_id"}),
 *    @ORM\Index(name="idx_start_time", columns={"start_time"}),
 *    @ORM\Index(name="idx_group_no", columns={"group_no"})
 * }),
  * @ORM\Entity(repositoryClass="SelfserviceBundle\Repositories\RegistrationActivityRepository")
 */
class RegistrationActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id", "default":0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="temp_id", type="bigint", options={"comment":"表单模板id"})
     */
    private $temp_id;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", type="string", length="200", options={"comment":"活动名称"})
     */
    private $activity_name;

    /**
     * @var string
     *
     * @ORM\Column(name="area", type="string", length="100", nullable=true, options={"comment":"活动城市(省市)"})
     */
    private $area;

    /**
     * @var string
     *
     * @ORM\Column(name="place", type="string", length="100", nullable=true, options={"comment":"活动地点"})
     */
    private $place;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="string", length="200", nullable=true, options={"comment":"详情地址"})
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", type="string", length="500", nullable=true, options={"comment":"活动简介"})
     */
    private $intro;

    /**
     * @var string
     *
     * @ORM\Column(name="show_fields", type="string", length="100", nullable=true, options={"comment":"前端展示字段"})
     */
    private $show_fields;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="text", options={"columnDefinition":"TEXT", "comment":"活动轮播图"})
     */
    private $pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="gift_points", type="integer", options={"comment":"奖励积分"})
     */
    private $gift_points;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_allow_duplicate", type="integer", options={"comment":"是否允许重复报名(1或0)"})
     */
    private $is_allow_duplicate;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_allow_cancel", type="integer", options={"comment":"是否允许取消报名(1或0)"})
     */
    private $is_allow_cancel;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_offline_verify", type="integer", options={"comment":"是否线下核销(1或0)"})
     */
    private $is_offline_verify;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_need_check", type="integer", options={"comment":"是否需要审核(1或0)"})
     */
    private $is_need_check;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_white_list", type="integer", options={"comment":"是否自动加入内购白名单(1或0)"})
     */
    private $is_white_list;

    /**
     * @var string
     *
     * @ORM\Column(name="enterprise_ids", type="text", nullable=true, options={"comment":"内购白名单企业ID", "default":""})
     */
    private $enterprise_ids = '';

    /**
     * @var string
     *
     * @ORM\Column(name="group_no", type="string", length="15", nullable=true, options={"comment":"活动分组编码", "default":""})
     */
    private $group_no = '';

    /**
     * @var string
     *
     * @ORM\Column(name="member_level", type="string", length="50", nullable=true, options={"comment":"适用会员等级"})
     */
    private $member_level;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_ids", type="string", length="100", nullable=true, options={"comment":"适用店铺", "default":""})
     */
    private $distributor_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="join_tips", type="string", length="200", nullable=true, options={"comment":"活动参与提示信息"})
     */
    private $join_tips;

    /**
     * @var string
     *
     * @ORM\Column(name="submit_form_tips", type="text", nullable=true, options={"comment":"表单填写提示信息"})
     */
    private $submit_form_tips;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", nullable=true, options={"comment":"图文详情"})
     */
    private $content;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"活动开始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"活动结束时间"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="join_limit", type="integer", options={"comment":"可参与次数", "default":0})
     */
    private $join_limit = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_sms_notice", type="boolean", options={"comment":"是否短信通知", "default": true})
     */
    private $is_sms_notice = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="is_wxapp_notice", type="boolean", options={"comment":"是否小程序模板通知", "default": true})
     */
    private $is_wxapp_notice = false;

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
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * Set isWxappNotice
     *
     * @param boolean $isWxappNotice
     *
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
     * @return RegistrationActivity
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
    
    public function setArea($area)
    {
        $this->area = $area;
        return $this;
    }
    
    public function getArea()
    {
        return $this->area;
    }
    
    public function setPlace($place)
    {
        $this->place = $place;
        return $this;
    }
    
    public function getPlace()
    {
        return $this->place;
    }
    
    public function setAddress($address)
    {
        $this->address = $address;
        return $this;
    }
    
    public function getAddress()
    {
        return $this->address;
    }
    
    public function setIntro($intro)
    {
        $this->intro = $intro;
        return $this;
    }
    
    public function getIntro()
    {
        return $this->intro;
    }
    
    public function setShowFields($show_fields)
    {
        $this->show_fields = $show_fields;
        return $this;
    }
    
    public function getShowFields()
    {
        return $this->show_fields;
    }
    
    public function setPics($pics)
    {
        $this->pics = $pics;
        return $this;
    }
    
    public function getPics()
    {
        return $this->pics;
    }
    
    public function setGiftPoints($gift_points)
    {
        $this->gift_points = $gift_points;
        return $this;
    }
    
    public function getGiftPoints()
    {
        return $this->gift_points;
    }
    
    public function setIsAllowDuplicate($is_allow_duplicate)
    {
        $this->is_allow_duplicate = $is_allow_duplicate;
        return $this;
    }
    
    public function getIsAllowDuplicate()
    {
        return $this->is_allow_duplicate;
    }
    
    public function setIsAllowCancel($is_allow_cancel)
    {
        $this->is_allow_cancel = $is_allow_cancel;
        return $this;
    }
    
    public function getIsAllowCancel()
    {
        return $this->is_allow_cancel;
    }
    
    public function setIsOfflineVerify($is_offline_verify)
    {
        $this->is_offline_verify = $is_offline_verify;
        return $this;
    }
    
    public function getIsOfflineVerify()
    {
        return $this->is_offline_verify;
    }
    
    public function setIsNeedCheck($is_need_check)
    {
        $this->is_need_check = $is_need_check;
        return $this;
    }
    
    public function getIsNeedCheck()
    {
        return $this->is_need_check;
    }
    
    public function setIsWhiteList($is_white_list)
    {
        $this->is_white_list = $is_white_list;
        return $this;
    }
    
    public function getIsWhiteList()
    {
        return $this->is_white_list;
    }
    
    public function setEnterpriseIds($enterprise_ids)
    {
        $this->enterprise_ids = $enterprise_ids;
        return $this;
    }
    
    public function getEnterpriseIds()
    {
        return $this->enterprise_ids;
    }
    
    public function setGroupNo($group_no)
    {
        $this->group_no = $group_no;
        return $this;
    }
    
    public function getGroupNo()
    {
        return $this->group_no;
    }
    
    public function setMemberLevel($member_level)
    {
        $this->member_level = $member_level;
        return $this;
    }
    
    public function getMemberLevel()
    {
        return $this->member_level;
    }
    
    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    public function setDistributorIds($distributor_ids)
    {
        $this->distributor_ids = $distributor_ids;
        return $this;
    }
    
    public function getDistributorIds()
    {
        return $this->distributor_ids;
    }
    
    public function setJoinTips($join_tips)
    {
        $this->join_tips = $join_tips;
        return $this;
    }
    
    public function getJoinTips()
    {
        return $this->join_tips;
    }
    
    public function setSubmitFormTips($submit_form_tips)
    {
        $this->submit_form_tips = $submit_form_tips;
        return $this;
    }
    
    public function getSubmitFormTips()
    {
        return $this->submit_form_tips;
    }
    
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    
    public function getContent()
    {
        return $this->content;
    }
}
