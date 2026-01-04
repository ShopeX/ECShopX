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
 * Members 会员主表，其他平台的会员本地化表
 *
 * @ORM\Table(name="members", options={"comment"="会员主表，其他平台的会员本地化表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_mobile",     columns={"mobile"}, options={"lengths": {64}}),
 *    @ORM\Index(name="idx_grade_id",   columns={"grade_id"}),
 *    @ORM\Index(name="idx_company_id_user_card_code",  columns={"company_id", "user_card_code"})
 * },uniqueConstraints={
 *    @ORM\UniqueConstraint(name="mobile_company", columns={"mobile", "company_id"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersRepository")
 */
class Members
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="用户id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="grade_id", type="bigint", options={"comment"="等级id"})
     */
    private $grade_id;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", length=255, options={"comment"="手机号"})
     */
    private $mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="region_mobile", type="string", length=50, options={"comment":"带区号的手机号"})
     */
    private $region_mobile;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile_country_code", type="string", length=50, options={"comment":"手机号的区号"})
     */
    private $mobile_country_code;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, options={"comment":"密码"})
     */
    private $password;

    /**
     * @var string
     *
     * @ORM\Column(name="user_card_code", type="string", options={"comment"="会员卡号"})
     */
    private $user_card_code;

    /**
     * @var string
     *
     * @ORM\Column(name="offline_card_code", nullable=true, type="string", options={"comment"="线下会员卡号"})
     */
    private $offline_card_code;

    /**
     * @var string
     *
     * @ORM\Column(name="authorizer_appid", nullable=true, type="string", length=64, options={"comment"="公众号的appid"})
     */
    private $authorizer_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="wxa_appid", nullable=true, type="string", length=64, options={"comment"="小程序的appid"})
     */
    private $wxa_appid;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_appid", nullable=true, type="string", length=64, options={"comment"="支付宝小程序appid"})
     */
    private $alipay_appid;

    /**
     * @var integer
     *
     * @ORM\Column(name="inviter_id", type="bigint", nullable=true, options={"comment":"推荐人id"})
     */
    private $inviter_id = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="source_from", type="string", nullable=true, options={"comment":"来源类型 default默认", "default":"default"})
     */
    private $source_from = 'default';

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"来源id"})
     */
    private $source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="monitor_id", type="bigint", nullable=true, options={"comment":"监控页面id"})
     */
    private $monitor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="latest_source_id", type="bigint", nullable=true, options={"comment":"最近来源id"})
     */
    private $latest_source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="latest_monitor_id", type="bigint", nullable=true, options={"comment":"最近监控页面id"})
     */
    private $latest_monitor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="remarks", type="string", nullable=true, options={"comment":"会员备注"})
     */
    private $remarks;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_year", type="integer", nullable=true, options={"comment":"创建年份","default":0})
     */
    private $created_year;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_month", type="integer", nullable=true, options={"comment":"创建月份","default":0})
     */
    private $created_month;

    /**
     * @var integer
     *
     * @ORM\Column(name="created_day", type="integer", nullable=true, options={"comment":"创建日期","default":0})
     */
    private $created_day;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $updated;

    /**
     * @var bool
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"是否禁用。0:可用；1:禁用", "default": 0})
     */
    private $disabled = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_point", type="boolean", options={"comment": "是否可以使用积分", "default": 0})
     */
    private $use_point = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="third_data", type="string", nullable=true, options={"comment":"第三方数据"})
     */
    private $third_data;

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        // CRC: 2367340174
        return $this->user_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Members
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
     * Set gradeId
     *
     * @param integer $gradeId
     *
     * @return Members
     */
    public function setGradeId($gradeId)
    {
        $this->grade_id = $gradeId;

        return $this;
    }

    /**
     * Get gradeId
     *
     * @return integer
     */
    public function getGradeId()
    {
        return $this->grade_id;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return Members
     */
    public function setMobile($mobile)
    {
        $this->mobile = fixedencrypt($mobile);

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return fixeddecrypt($this->mobile);
    }

    /**
     * Set userCardCode
     *
     * @param string $userCardCode
     *
     * @return Members
     */
    public function setUserCardCode($userCardCode)
    {
        $this->user_card_code = $userCardCode;

        return $this;
    }

    /**
     * Get userCardCode
     *
     * @return string
     */
    public function getUserCardCode()
    {
        return $this->user_card_code;
    }

    /**
     * Set authorizerAppid
     *
     * @param string $authorizerAppid
     *
     * @return Members
     */
    public function setAuthorizerAppid($authorizerAppid)
    {
        $this->authorizer_appid = $authorizerAppid;

        return $this;
    }

    /**
     * Get authorizerAppid
     *
     * @return string
     */
    public function getAuthorizerAppid()
    {
        return $this->authorizer_appid;
    }

    /**
     * Set wxaAppid
     *
     * @param string $wxaAppid
     *
     * @return Members
     */
    public function setWxaAppid($wxaAppid)
    {
        $this->wxa_appid = $wxaAppid;

        return $this;
    }

    /**
     * Get wxaAppid
     *
     * @return string
     */
    public function getWxaAppid()
    {
        return $this->wxa_appid;
    }

    /**
     * Set alipayAppid
     *
     * @param string $alipayAppid
     *
     * @return Members
     */
    public function setAlipayAppid($alipayAppid)
    {
        $this->alipay_appid = $alipayAppid;

        return $this;
    }

    /**
     * Get alipayAppid
     *
     * @return string
     */
    public function getAlipayAppid()
    {
        return $this->alipay_appid;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Members
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
     * @return Members
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
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return Users
     */
    public function setSourceId($sourceId)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set monitorId
     *
     * @param integer $monitorId
     *
     * @return Users
     */
    public function setMonitorId($monitorId)
    {
        $this->monitor_id = $monitorId;

        return $this;
    }

    /**
     * Get monitorId
     *
     * @return integer
     */
    public function getMonitorId()
    {
        return $this->monitor_id;
    }

    /**
     * Set latestSourceId
     *
     * @param integer $latestSourceId
     *
     * @return Users
     */
    public function setLatestSourceId($latestSourceId)
    {
        $this->latest_source_id = $latestSourceId;

        return $this;
    }

    /**
     * Get latestSourceId
     *
     * @return integer
     */
    public function getLatestSourceId()
    {
        return $this->latest_source_id;
    }

    /**
     * Set latestMonitorId
     *
     * @param integer $latestMonitorId
     *
     * @return Users
     */
    public function setLatestMonitorId($latestMonitorId)
    {
        $this->latest_monitor_id = $latestMonitorId;

        return $this;
    }

    /**
     * Get latestMonitorId
     *
     * @return integer
     */
    public function getLatestMonitorId()
    {
        return $this->latest_monitor_id;
    }

    /**
     * Set createdYear
     *
     * @param integer $createdYear
     *
     * @return Members
     */
    public function setCreatedYear($createdYear)
    {
        $this->created_year = $createdYear;

        return $this;
    }

    /**
     * Get createdYear
     *
     * @return integer
     */
    public function getCreatedYear()
    {
        return $this->created_year;
    }

    /**
     * Set createdMonth
     *
     * @param integer $createdMonth
     *
     * @return Members
     */
    public function setCreatedMonth($createdMonth)
    {
        $this->created_month = $createdMonth;

        return $this;
    }

    /**
     * Get createdMonth
     *
     * @return integer
     */
    public function getCreatedMonth()
    {
        return $this->created_month;
    }

    /**
     * Set createdDay
     *
     * @param integer $createdDay
     *
     * @return Members
     */
    public function setCreatedDay($createdDay)
    {
        $this->created_day = $createdDay;

        return $this;
    }

    /**
     * Get createdDay
     *
     * @return integer
     */
    public function getCreatedDay()
    {
        return $this->created_day;
    }

    /**
     * Set offlineCardCode
     *
     * @param string $offlineCardCode
     *
     * @return Members
     */
    public function setOfflineCardCode($offlineCardCode)
    {
        $this->offline_card_code = $offlineCardCode;

        return $this;
    }

    /**
     * Get offlineCardCode
     *
     * @return string
     */
    public function getOfflineCardCode()
    {
        return $this->offline_card_code;
    }

    /**
     * Set inviterId
     *
     * @param integer $inviterId
     *
     * @return Members
     */
    public function setInviterId($inviterId)
    {
        $this->inviter_id = $inviterId;

        return $this;
    }

    /**
     * Get inviterId
     *
     * @return integer
     */
    public function getInviterId()
    {
        return $this->inviter_id;
    }

    /**
     * Set password
     *
     * @param string $password
     *
     * @return Members
     */
    public function setPassword($password)
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Get password
     *
     * @return string
     */
    public function getPassword()
    {
        return $this->password;
    }

    /**
     * Set sourceFrom
     *
     * @param string $sourceFrom
     *
     * @return Members
     */
    public function setSourceFrom($sourceFrom)
    {
        $this->source_from = $sourceFrom;

        return $this;
    }

    /**
     * Get sourceFrom
     *
     * @return string
     */
    public function getSourceFrom()
    {
        return $this->source_from;
    }

    /**
     * Set usePoint
     *
     * @param boolean $usePoint
     *
     * @return Members
     */
    public function setUsePoint($usePoint)
    {
        $this->use_point = $usePoint;

        return $this;
    }

    /**
     * Get usePoint
     *
     * @return boolean
     */
    public function getUsePoint()
    {
        return $this->use_point;
    }

    /**
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return Members
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set remarks
     *
     * @param string $remarks
     *
     * @return Members
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
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
     * Set regionMobile.
     *
     * @param string $regionMobile
     *
     * @return Members
     */
    public function setRegionMobile($regionMobile)
    {
        $this->region_mobile = $regionMobile;

        return $this;
    }

    /**
     * Get regionMobile.
     *
     * @return string
     */
    public function getRegionMobile()
    {
        return $this->region_mobile;
    }

    /**
     * Set mobileCountryCode.
     *
     * @param string $mobileCountryCode
     *
     * @return Members
     */
    public function setMobileCountryCode($mobileCountryCode)
    {
        $this->mobile_country_code = $mobileCountryCode;

        return $this;
    }

    /**
     * Get mobileCountryCode.
     *
     * @return string
     */
    public function getMobileCountryCode()
    {
        return $this->mobile_country_code;
    }
}
