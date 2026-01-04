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
 * RegistrationRecord 报名记录
 *
 * @ORM\Table(name="selfservice_registration_record", options={"comment"="报名记录"}, indexes={
 *    @ORM\Index(name="idx_activity_id", columns={"activity_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *    @ORM\Index(name="idx_true_name", columns={"true_name"}),
 *    @ORM\Index(name="idx_created", columns={"created"}),
 *    @ORM\Index(name="idx_status", columns={"status"}),
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="SelfserviceBundle\Repositories\RegistrationRecordRepository")
 */
class RegistrationRecord
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="record_id", type="bigint")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $record_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
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
     * @ORM\Column(name="record_no", type="bigint", options={"comment":"报名编号", "default":0})
     */
    private $record_no = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="group_no", type="string", length="15", nullable=true, options={"comment":"活动分组编码", "default":""})
     */
    private $group_no = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="form_id", type="bigint", options={"comment":"表单id"})
     */
    private $form_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="get_points", type="bigint", options={"comment":"获取到积分"})
     */
    private $get_points;

    /**
     * @var integer
     *
     * @ORM\Column(name="verify_code", type="bigint", options={"comment":"核销码"})
     */
    private $verify_code;

    /**
     * @var integer
     *
     * @ORM\Column(name="verify_time", type="bigint", options={"comment":"核销时间", "default":0})
     */
    private $verify_time = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="verify_operator", type="string", length=50, nullable=true, options={"comment":"核销员", "default":""})
     */
    private $verify_operator = '';

    /**
     * @var string
     *
     * @ORM\Column(name="true_name", type="string", length=30, nullable=true, options={"comment":"真实姓名", "default":""})
     */
    private $true_name = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="is_white_list", type="bigint", options={"comment":"是否加入白名单"})
     */
    private $is_white_list;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", options={"comment"="手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="form_mobile", type="string", options={"comment":"表单填写手机号", "default":""})
     */
    private $form_mobile = '';

    /**
     * @var string
     *
     * @ORM\Column(name="wxapp_appid", type="string", length=32, nullable=true, options={"comment":"会员小程序appid"})
     */
    private $wxapp_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="open_id", type="string", length=32, nullable=true, options={"comment":"会员小程序openid"})
     */
    private $open_id;

    /**
     * @var string
     *
     * pending 待审核，
     * passed 已通过，
     * rejected 已拒绝
     * verified 已核销
     * canceled 已取消
     *
     * @ORM\Column(name="status", type="string", length=32, options={"comment":"状态: pending 待审核，passed 已通过，rejected 已拒绝, canceled 已取消, verified 已核销", "default": "pending"})
     */
    private $status = "pending";

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"报名内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="reason", type="text", nullable=true, options={"comment":"拒绝原因"})
     */
    private $reason;

    /**
     * @var string
     *
     * @ORM\Column(name="remark", type="text", nullable=true, options={"comment":"备注"})
     */
    private $remark;

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
    * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司_ID"})
    */
    private $company_id;

    public function setRemark($remark)
    {
        $this->remark = $remark;
        return $this;
    }

    public function getRemark()
    {
        // Ver: 8d1abe8e
        return $this->remark;
    }

    /**
     * Get recordId
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    public function setVerifyTime($verify_time)
    {
        // Ver: 8d1abe8e
        $this->verify_time = $verify_time;
        return $this;
    }

    public function getVerifyTime()
    {
        return $this->verify_time;
    }

    public function setVerifyOperator($verify_operator)
    {
        $this->verify_operator = $verify_operator;
        return $this;
    }

    public function getVerifyOperator()
    {
        return $this->verify_operator;
    }

    public function setTrueName($true_name)
    {
        $this->true_name = $true_name;
        return $this;
    }

    public function getTrueName()
    {
        return $this->true_name;
    }

    /**
     * Set activityId
     *
     * @param integer $activityId
     *
     * @return RegistrationRecord
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

    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    public function setRecordNo($record_no)
    {
        $this->record_no = $record_no;
        return $this;
    }

    public function getRecordNo()
    {
        return $this->record_no;
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

    public function setFormId($form_id)
    {
        $this->form_id = $form_id;
        return $this;
    }

    public function getFormId()
    {
        return $this->form_id;
    }

    public function setGetPoints($get_points)
    {
        $this->get_points = $get_points;
        return $this;
    }

    public function getGetPoints()
    {
        return $this->get_points;
    }

    public function setVerifyCode($verify_code)
    {
        $this->verify_code = $verify_code;
        return $this;
    }

    public function getVerifyCode()
    {
        return $this->verify_code;
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

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return RegistrationRecord
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
     * Set mobile
     *
     * @param integer $mobile
     *
     * @return RegistrationRecord
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    public function setFormMobile($form_mobile)
    {
        $this->form_mobile = fixedencrypt($form_mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return integer
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    public function getFormMobile()
    {
        return fixeddecrypt($this->form_mobile);
    }

    /**
     * Set wxappAppid
     *
     * @param string $wxappAppid
     *
     * @return RegistrationRecord
     */
    public function setWxappAppid($wxappAppid)
    {
        $this->wxapp_appid = $wxappAppid;

        return $this;
    }

    /**
     * Get wxappAppid
     *
     * @return string
     */
    public function getWxappAppid()
    {
        return $this->wxapp_appid;
    }

    /**
     * Set openId
     *
     * @param string $openId
     *
     * @return RegistrationRecord
     */
    public function setOpenId($openId)
    {
        $this->open_id = $openId;

        return $this;
    }

    /**
     * Get openId
     *
     * @return string
     */
    public function getOpenId()
    {
        return $this->open_id;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return RegistrationRecord
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set content
     *
     * @param string $content
     *
     * @return RegistrationRecord
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set reason
     *
     * @param string $reason
     *
     * @return RegistrationRecord
     */
    public function setReason($reason)
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->reason;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return RegistrationRecord
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
     * @return RegistrationRecord
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return RegistrationRecord
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
}
