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

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Activities 员工内购活动表
 *
 * @ORM\Table(name="employee_purchase_activities", options={"comment"="员工内购活动表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivitiesRepository")
 */
class Activities
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"活动id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;
    
    /**
     * @var integer
     *  为0时表示商城的企业
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示商城的企业", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="integer", options={"comment":"操作id", "default": 0})
     */
    private $operator_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=50, options={"comment":"活动名称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=50, options={"comment":"活动标题"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="pages_template_id", type="bigint", options={"comment":"活动首页模版id"})
     */
    private $pages_template_id;

    /**
     * @var string
     *
     * @ORM\Column(name="pic", type="string", options={"comment":"活动图片"})
     */
    private $pic;

    /**
     * @var string
     *
     * @ORM\Column(name="share_pic", type="string", options={"comment":"活动分享图片"})
     */
    private $share_pic;

    /**
     * @var json_array
     *
     * @ORM\Column(name="enterprise_id", type="simple_array", options={"comment":"参与企业ID"})
     */
    private $enterprise_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="display_time", type="integer", options={"comment":"活动展示(预热)时间"})
     */
    private $display_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_begin_time", type="integer", options={"comment":"员工开始购买时间"})
     */
    private $employee_begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_end_time", type="integer", options={"comment":"员工结束购买时间"})
     */
    private $employee_end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="employee_limitfee", type="integer", options={"unsigned":true, "comment":"员工额度，以分为单位"})
     */
    private $employee_limitfee;

    /**
     * @var boolean
     *
     * @ORM\Column(name="if_relative_join", type="boolean", options={"comment":"亲友是否参与", "default":false})
     */
    private $if_relative_join = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="invite_limit", type="integer", nullable=true, options={"comment":"员工邀请亲友上限", "default":0})
     */
    private $invite_limit = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="relative_begin_time", type="integer", nullable=true, options={"comment":"亲友开始购买时间"})
     */
    private $relative_begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="relative_end_time", type="integer", nullable=true, options={"comment":"亲友结束购买时间"})
     */
    private $relative_end_time;

    /**
     * @var boolean
     *
     * @ORM\Column(name="if_share_limitfee", type="boolean", nullable=true, options={"comment":"亲友是否共享员工额度", "default":false})
     */
    private $if_share_limitfee = false;

    /**
     * @var integer
     *
     * @ORM\Column(name="relative_limitfee", type="integer", nullable=true, options={"unsigned":true, "comment":"家属额度，以分为单位", "default":0})
     */
    private $relative_limitfee = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="minimum_amount", type="integer", options={"unsigned":true, "comment":"起定金额，以分为单位", "default":0})
     */
    private $minimum_amount = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="close_modify_hours_after_activity", type="integer", options={"comment":"活动后数小时关闭修改", "default":0})
     */
    private $close_modify_hours_after_activity = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"状态 active:有效的 cancel:取消 pending:暂停 over:结束", "default":"active"})
     */
    private $status = 'active';

    /**
     * @var boolean
     *
     * @ORM\Column(name="if_share_store", type="boolean", options={"comment":"是否共享库存", "default":false})
     */
    private $if_share_store = false;
    
    /**
     * @var string
     *
     * @ORM\Column(name="price_display_config", nullable=true, type="json_array", options={"comment":"价格展示配置"})
     */
    private $price_display_config;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_discount_description_enabled", type="boolean", options={"comment":"优惠说明开关", "default":false})
     */
    private $is_discount_description_enabled = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="discount_description", type="string", length=50, options={"comment":"优惠说明", "default":""})
     */
    private $discount_description;

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
     * Get Id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return Activities
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
     * Set name.
     *
     * @param string $name
     *
     * @return Activities
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title.
     *
     * @param string $title
     *
     * @return Activities
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set pagesTemplateId.
     *
     * @param int $pagesTemplateId
     *
     * @return Activities
     */
    public function setPagesTemplateId($pagesTemplateId)
    {
        $this->pages_template_id = $pagesTemplateId;

        return $this;
    }

    /**
     * Get pagesTemplateId.
     *
     * @return int
     */
    public function getPagesTemplateId()
    {
        return $this->pages_template_id;
    }

    /**
     * Set pic.
     *
     * @param string $pic
     *
     * @return Activities
     */
    public function setPic($pic)
    {
        $this->pic = $pic;

        return $this;
    }

    /**
     * Get pic.
     *
     * @return string
     */
    public function getPic()
    {
        return $this->pic;
    }

    /**
     * Set sharePic.
     *
     * @param string $sharePic
     *
     * @return Activities
     */
    public function setSharePic($sharePic)
    {
        $this->share_pic = $sharePic;

        return $this;
    }

    /**
     * Get sharePic.
     *
     * @return string
     */
    public function getSharePic()
    {
        return $this->share_pic;
    }

    /**
     * Set enterpriseId.
     *
     * @param array $enterpriseId
     *
     * @return Activities
     */
    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    /**
     * Get enterpriseId.
     *
     * @return array
     */
    public function getEnterpriseId()
    {
        return $this->enterprise_id;
    }

    /**
     * Set displayTime.
     *
     * @param int $displayTime
     *
     * @return Activities
     */
    public function setDisplayTime($displayTime)
    {
        $this->display_time = $displayTime;

        return $this;
    }

    /**
     * Get displayTime.
     *
     * @return int
     */
    public function getDisplayTime()
    {
        return $this->display_time;
    }

    /**
     * Set employeeBeginTime.
     *
     * @param int $employeeBeginTime
     *
     * @return Activities
     */
    public function setEmployeeBeginTime($employeeBeginTime)
    {
        $this->employee_begin_time = $employeeBeginTime;

        return $this;
    }

    /**
     * Get employeeBeginTime.
     *
     * @return int
     */
    public function getEmployeeBeginTime()
    {
        return $this->employee_begin_time;
    }

    /**
     * Set employeeEndTime.
     *
     * @param int $employeeEndTime
     *
     * @return Activities
     */
    public function setEmployeeEndTime($employeeEndTime)
    {
        $this->employee_end_time = $employeeEndTime;

        return $this;
    }

    /**
     * Get employeeEndTime.
     *
     * @return int
     */
    public function getEmployeeEndTime()
    {
        return $this->employee_end_time;
    }

    /**
     * Set employeeLimitfee.
     *
     * @param int $employeeLimitfee
     *
     * @return Activities
     */
    public function setEmployeeLimitfee($employeeLimitfee)
    {
        $this->employee_limitfee = $employeeLimitfee;

        return $this;
    }

    /**
     * Get employeeLimitfee.
     *
     * @return int
     */
    public function getEmployeeLimitfee()
    {
        return $this->employee_limitfee;
    }

    /**
     * Set ifRelativeJoin.
     *
     * @param bool $ifRelativeJoin
     *
     * @return Activities
     */
    public function setIfRelativeJoin($ifRelativeJoin)
    {
        $this->if_relative_join = $ifRelativeJoin;

        return $this;
    }

    /**
     * Get ifRelativeJoin.
     *
     * @return bool
     */
    public function getIfRelativeJoin()
    {
        return $this->if_relative_join;
    }

    /**
     * Set inviteLimit.
     *
     * @param int $inviteLimit
     *
     * @return Activities
     */
    public function setInviteLimit($inviteLimit)
    {
        $this->invite_limit = $inviteLimit;

        return $this;
    }

    /**
     * Get inviteLimit.
     *
     * @return int
     */
    public function getInviteLimit()
    {
        return $this->invite_limit;
    }

    /**
     * Set relativeBeginTime.
     *
     * @param int $relativeBeginTime
     *
     * @return Activities
     */
    public function setRelativeBeginTime($relativeBeginTime)
    {
        $this->relative_begin_time = $relativeBeginTime;

        return $this;
    }

    /**
     * Get relativeBeginTime.
     *
     * @return int
     */
    public function getRelativeBeginTime()
    {
        return $this->relative_begin_time;
    }

    /**
     * Set relativeEndTime.
     *
     * @param int $relativeEndTime
     *
     * @return Activities
     */
    public function setRelativeEndTime($relativeEndTime)
    {
        $this->relative_end_time = $relativeEndTime;

        return $this;
    }

    /**
     * Get relativeEndTime.
     *
     * @return int
     */
    public function getRelativeEndTime()
    {
        return $this->relative_end_time;
    }

    /**
     * Set ifShareLimitfee.
     *
     * @param int $ifShareLimitfee
     *
     * @return Activities
     */
    public function setIfShareLimitfee($ifShareLimitfee)
    {
        $this->if_share_limitfee = $ifShareLimitfee;

        return $this;
    }

    /**
     * Get ifShareLimitfee.
     *
     * @return int
     */
    public function getIfShareLimitfee()
    {
        return $this->if_share_limitfee;
    }

    /**
     * Set relativeLimitfee.
     *
     * @param int $relativeLimitfee
     *
     * @return Activities
     */
    public function setRelativeLimitfee($relativeLimitfee)
    {
        $this->relative_limitfee = $relativeLimitfee;

        return $this;
    }

    /**
     * Get relativeLimitfee.
     *
     * @return int
     */
    public function getRelativeLimitfee()
    {
        return $this->relative_limitfee;
    }

    /**
     * Set minimumAmount.
     *
     * @param int $minimumAmount
     *
     * @return Activities
     */
    public function setMinimumAmount($minimumAmount)
    {
        $this->minimum_amount = $minimumAmount;

        return $this;
    }

    /**
     * Get minimumAmount.
     *
     * @return int
     */
    public function getMinimumAmount()
    {
        return $this->minimum_amount;
    }

    /**
     * Set closeModifyHoursAfterActivity.
     *
     * @param int $closeModifyHoursAfterActivity
     *
     * @return Activities
     */
    public function setCloseModifyHoursAfterActivity($closeModifyHoursAfterActivity)
    {
        $this->close_modify_hours_after_activity = $closeModifyHoursAfterActivity;

        return $this;
    }

    /**
     * Get closeModifyHoursAfterActivity.
     *
     * @return int
     */
    public function getCloseModifyHoursAfterActivity()
    {
        return $this->close_modify_hours_after_activity;
    }

    /**
     * Set status.
     *
     * @param string $status
     *
     * @return Activities
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set ifShareStore.
     *
     * @param string $ifShareStore
     *
     * @return Activities
     */
    public function setIfShareStore($ifShareStore)
    {
        $this->if_share_store = $ifShareStore;

        return $this;
    }

    /**
     * Get ifShareStore.
     *
     * @return string
     */
    public function getIfShareStore()
    {
        return $this->if_share_store;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return Activities
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
     * @return Activities
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

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return Activities
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set operatorId.
     *
     * @param int $operatorId
     *
     * @return Activities
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId.
     *
     * @return int
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set priceDisplayConfig.
     *
     * @param array|null $priceDisplayConfig
     *
     * @return Activities
     */
    public function setPriceDisplayConfig($priceDisplayConfig = null)
    {
        $this->price_display_config = $priceDisplayConfig;

        return $this;
    }

    /**
     * Get priceDisplayConfig.
     *
     * @return array|null
     */
    public function getPriceDisplayConfig()
    {
        return $this->price_display_config;
    }

    /**
     * Set isDiscountDescriptionEnabled.
     *
     * @param bool $isDiscountDescriptionEnabled
     *
     * @return Activities
     */
    public function setIsDiscountDescriptionEnabled($isDiscountDescriptionEnabled)
    {
        $this->is_discount_description_enabled = $isDiscountDescriptionEnabled;

        return $this;
    }

    /**
     * Get isDiscountDescriptionEnabled.
     *
     * @return bool
     */
    public function getIsDiscountDescriptionEnabled()
    {
        return $this->is_discount_description_enabled;
    }

    /**
     * Set discountDescription.
     *
     * @param string $discountDescription
     *
     * @return Activities
     */
    public function setDiscountDescription($discountDescription)
    {
        $this->discount_description = $discountDescription;

        return $this;
    }

    /**
     * Get discountDescription.
     *
     * @return string
     */
    public function getDiscountDescription()
    {
        return $this->discount_description;
    }
}
