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
 * ReservationSetting(预约配置表)
 *
 * @ORM\Table(name="reservation_setting", options={"comment":"预约配置表"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\SettingRepository")
 */
class ReservationSetting
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="time_interval", type="integer", options={"comment":"预约时间间隔"})
     */
    private $time_interval;

    /**
     * @var string
     *
     * @ORM\Column(name="resource_name", type="string", options={"comment":"资源位名称"})
     */
    private $resource_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="max_limit_day", type="integer", options={"comment":"可提前预约天数"})
     */
    private $max_limit_day;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_limit_hour", type="integer", options={"comment":"可提前预约分钟数"})
     */
    private $min_limit_hour;

    /**
     * @var string
     *
     * @ORM\Column(name="reservation_condition", type="integer", options={"comment":"预约条件"})
     */
    private $reservation_condition;

    /**
     * @var integer
     *
     * @ORM\Column(name="reservation_mode", type="integer", options={"comment":"预约模式"})
     */
    private $reservation_mode;

    /**
     * @var integer
     *
     * @ORM\Column(name="cancel_minute", type="integer", options={"comment":"取消预约最少提前分钟数"})
     */
    private $cancel_minute;

    /**
     * @var integer
     *
     * @ORM\Column(name="reservation_num_limit", type="text", nullable=true,  options={"comment":"预约限制"})
     */
    private $reservation_num_limit;

    /**
     * @var integer
     *
     * @ORM\Column(name="sms_delay", type="string", nullable=true,  options={"comment":"预约提醒通知"})
     */
    private $sms_delay;

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
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ReservationSetting
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
     * Set timeInterval
     *
     * @param integer $timeInterval
     *
     * @return ReservationSetting
     */
    public function setTimeInterval($timeInterval)
    {
        $this->time_interval = $timeInterval;

        return $this;
    }

    /**
     * Get timeInterval
     *
     * @return integer
     */
    public function getTimeInterval()
    {
        return $this->time_interval;
    }

    /**
     * Set resourceName
     *
     * @param string $resourceName
     *
     * @return ReservationSetting
     */
    public function setResourceName($resourceName)
    {
        $this->resource_name = $resourceName;

        return $this;
    }

    /**
     * Get resourceName
     *
     * @return string
     */
    public function getResourceName()
    {
        return $this->resource_name;
    }

    /**
     * Set maxLimitDay
     *
     * @param integer $maxLimitDay
     *
     * @return ReservationSetting
     */
    public function setMaxLimitDay($maxLimitDay)
    {
        $this->max_limit_day = $maxLimitDay;

        return $this;
    }

    /**
     * Get maxLimitDay
     *
     * @return integer
     */
    public function getMaxLimitDay()
    {
        return $this->max_limit_day;
    }

    /**
     * Set minLimitHour
     *
     * @param integer $minLimitHour
     *
     * @return ReservationSetting
     */
    public function setMinLimitHour($minLimitHour)
    {
        $this->min_limit_hour = $minLimitHour;

        return $this;
    }

    /**
     * Get minLimitHour
     *
     * @return integer
     */
    public function getMinLimitHour()
    {
        return $this->min_limit_hour;
    }

    /**
     * Set reservationCondition
     *
     * @param integer $reservationCondition
     *
     * @return ReservationSetting
     */
    public function setReservationCondition($reservationCondition)
    {
        $this->reservation_condition = $reservationCondition;

        return $this;
    }

    /**
     * Get reservationCondition
     *
     * @return integer
     */
    public function getReservationCondition()
    {
        return $this->reservation_condition;
    }

    /**
     * Set reservationMode
     *
     * @param integer $reservationMode
     *
     * @return ReservationSetting
     */
    public function setReservationMode($reservationMode)
    {
        $this->reservation_mode = $reservationMode;

        return $this;
    }

    /**
     * Get reservationMode
     *
     * @return integer
     */
    public function getReservationMode()
    {
        return $this->reservation_mode;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ReservationSetting
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
     * @return ReservationSetting
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
     * Set cancelMinute
     *
     * @param integer $cancelMinute
     *
     * @return ReservationSetting
     */
    public function setCancelMinute($cancelMinute)
    {
        $this->cancel_minute = $cancelMinute;

        return $this;
    }

    /**
     * Get cancelMinute
     *
     * @return integer
     */
    public function getCancelMinute()
    {
        return $this->cancel_minute;
    }

    /**
     * Set reservationNumLimit
     *
     * @param string $reservationNumLimit
     *
     * @return ReservationSetting
     */
    public function setReservationNumLimit($reservationNumLimit)
    {
        $this->reservation_num_limit = $reservationNumLimit;

        return $this;
    }

    /**
     * Get reservationNumLimit
     *
     * @return string
     */
    public function getReservationNumLimit()
    {
        return $this->reservation_num_limit;
    }

    /**
     * Set smsDelay
     *
     * @param string $smsDelay
     *
     * @return ReservationSetting
     */
    public function setSmsDelay($smsDelay)
    {
        $this->sms_delay = $smsDelay;

        return $this;
    }

    /**
     * Get smsDelay
     *
     * @return string
     */
    public function getSmsDelay()
    {
        return $this->sms_delay;
    }
}
