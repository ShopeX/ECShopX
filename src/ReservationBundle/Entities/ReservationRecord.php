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

namespace ReservationBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ReservationRecord(预约记录表)
 *
 * @ORM\Table(name="reservation_record", options={"comment":"预约记录表"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\ReservationRecordRepository")
 */

class ReservationRecord
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="record_id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $record_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"门店id"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_name", type="string", nullable=true, length=100, options={"comment":"门店名称"})
     */
    private $shop_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="agreement_date", type="integer", options={"comment":"约定日期"})
     */
    private $agreement_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="to_shop_time", type="integer", nullable=true, options={"comment":"到店时间(时间戳)"})
     */
    private $to_shop_time;

    /**
     * @var string
     *
     * @ORM\Column(name="begin_time", type="string", length=5, options={"comment":"到店时间(时刻字符串)"})
     */
    private $begin_time;

    /**
     * @var string
     *
     * @ORM\Column(name="end_time", type="string", length=5, nullable=true, options={"comment":"约定结束时刻"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", options={"comment":"预约状态。可选值有 cancel-取消;-to_the_shop-已到店;-not_to_shop-未到店;-success-预约成功;-system-系统占位;"})
     */
    private $status;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="integer", options={"comment":"预约数量", "default":1})
     */
    private $num;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"用户user id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="user_name", type="string", length=100, nullable=true, options={"comment":"用户名称"})
     */
    private $user_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="sex", type="integer", nullable=true, options={"comment":"用户性别"})
     */
    private $sex;

    /**
     * @var string
     *
     * @ORM\Column(name="mobile", type="string", nullable=true, options={"comment":"预约人手机号"})
     */
    private $mobile;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_level_id", type="bigint", nullable=true, options={"comment":"资源位id"})
     */
    private $resource_level_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_level_name", type="string", length=100, nullable=true, options={"comment":"资源位名称"})
     */
    private $resource_level_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="rights_id", type="bigint", nullable=true, options={"comment":"服务商品id"})
     */
    private $rights_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="rights_name", type="string", length=100, nullable=true, options={"comment":"服务商品名称"})
     */
    private $rights_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_id", type="bigint", nullable=true, options={"comment":"服务商品id"})
     */
    private $label_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="label_name", type="string", length=100, nullable=true, options={"comment":"服务商品名称"})
     */
    private $label_name;

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
     * Get recordId
     *
     * @return integer
     */
    public function getRecordId()
    {
        return $this->record_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ReservationRecord
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ReservationRecord
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set shopName
     *
     * @param string $shopName
     *
     * @return ReservationRecord
     */
    public function setShopName($shopName)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName
     *
     * @return string
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set agreementDate
     *
     * @param integer $agreementDate
     *
     * @return ReservationRecord
     */
    public function setAgreementDate($agreementDate)
    {
        $this->agreement_date = $agreementDate;

        return $this;
    }

    /**
     * Get agreementDate
     *
     * @return integer
     */
    public function getAgreementDate()
    {
        return $this->agreement_date;
    }

    /**
     * Set toShopTime
     *
     * @param integer $toShopTime
     *
     * @return ReservationRecord
     */
    public function setToShopTime($toShopTime)
    {
        $this->to_shop_time = $toShopTime;

        return $this;
    }

    /**
     * Get toShopTime
     *
     * @return integer
     */
    public function getToShopTime()
    {
        return $this->to_shop_time;
    }

    /**
     * Set beginTime
     *
     * @param string $beginTime
     *
     * @return ReservationRecord
     */
    public function setBeginTime($beginTime)
    {
        $this->begin_time = $beginTime;

        return $this;
    }

    /**
     * Get beginTime
     *
     * @return string
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }

    /**
     * Set endTime
     *
     * @param string $endTime
     *
     * @return ReservationRecord
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return string
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return ReservationRecord
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
     * Set num
     *
     * @param integer $num
     *
     * @return ReservationRecord
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return ReservationRecord
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
     * Set userName
     *
     * @param string $userName
     *
     * @return ReservationRecord
     */
    public function setUserName($userName)
    {
        $this->user_name = $userName;

        return $this;
    }

    /**
     * Get userName
     *
     * @return string
     */
    public function getUserName()
    {
        return $this->user_name;
    }

    /**
     * Set sex
     *
     * @param integer $sex
     *
     * @return ReservationRecord
     */
    public function setSex($sex)
    {
        $this->sex = $sex;

        return $this;
    }

    /**
     * Get sex
     *
     * @return integer
     */
    public function getSex()
    {
        return $this->sex;
    }

    /**
     * Set mobile
     *
     * @param string $mobile
     *
     * @return ReservationRecord
     */
    public function setMobile($mobile)
    {
        $this->mobile = $mobile;

        return $this;
    }

    /**
     * Get mobile
     *
     * @return string
     */
    public function getMobile()
    {
        return $this->mobile;
    }

    /**
     * Set resourceLevelId
     *
     * @param integer $resourceLevelId
     *
     * @return ReservationRecord
     */
    public function setResourceLevelId($resourceLevelId)
    {
        $this->resource_level_id = $resourceLevelId;

        return $this;
    }

    /**
     * Get resourceLevelId
     *
     * @return integer
     */
    public function getResourceLevelId()
    {
        return $this->resource_level_id;
    }

    /**
     * Set resourceLevelName
     *
     * @param string $resourceLevelName
     *
     * @return ReservationRecord
     */
    public function setResourceLevelName($resourceLevelName)
    {
        $this->resource_level_name = $resourceLevelName;

        return $this;
    }

    /**
     * Get resourceLevelName
     *
     * @return string
     */
    public function getResourceLevelName()
    {
        return $this->resource_level_name;
    }

    /**
     * Set rightsId
     *
     * @param integer $rightsId
     *
     * @return ReservationRecord
     */
    public function setRightsId($rightsId)
    {
        $this->rights_id = $rightsId;

        return $this;
    }

    /**
     * Get rightsId
     *
     * @return integer
     */
    public function getRightsId()
    {
        return $this->rights_id;
    }

    /**
     * Set rightsName
     *
     * @param string $rightsName
     *
     * @return ReservationRecord
     */
    public function setRightsName($rightsName)
    {
        $this->rights_name = $rightsName;

        return $this;
    }

    /**
     * Get rightsName
     *
     * @return string
     */
    public function getRightsName()
    {
        return $this->rights_name;
    }

    /**
     * Set labelId
     *
     * @param integer $labelId
     *
     * @return ReservationRecord
     */
    public function setLabelId($labelId)
    {
        $this->label_id = $labelId;

        return $this;
    }

    /**
     * Get labelId
     *
     * @return integer
     */
    public function getLabelId()
    {
        return $this->label_id;
    }

    /**
     * Set labelName
     *
     * @param string $labelName
     *
     * @return ReservationRecord
     */
    public function setLabelName($labelName)
    {
        $this->label_name = $labelName;

        return $this;
    }

    /**
     * Get labelName
     *
     * @return string
     */
    public function getLabelName()
    {
        return $this->label_name;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ReservationRecord
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
     * @return ReservationRecord
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
}
