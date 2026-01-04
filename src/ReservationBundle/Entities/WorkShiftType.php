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
 * WorkShiftType(班次类型表)
 *
 * @ORM\Table(name="reservation_shift_type", options={"comment":"班次类型表"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\WorkShiftTypeRepository")
 */
class WorkShiftType
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="type_id", type="bigint", options={"comment":"type_id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $type_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type_name", type="string", length=30, options={"comment":"排班类型名称"})
     */
    private $type_name;

    /**
     * @var string
     *
     * @ORM\Column(name="begin_time", type="string", length=5, options={"comment":"排班类型名称"})
     */
    private $begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="string", length=5, options={"comment":"排班类型名称"})
     */
    private $end_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="string", length=10, options={"comment":"类型状态invalid/valid", "default":"valid"})
     */
    private $status;

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
     * Get typeId
     *
     * @return integer
     */
    public function getTypeId()
    {
        return $this->type_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return WorkShiftType
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
     * Set typeName
     *
     * @param string $typeName
     *
     * @return WorkShiftType
     */
    public function setTypeName($typeName)
    {
        // Ver: 8d1abe8e
        $this->type_name = $typeName;

        return $this;
    }

    /**
     * Get typeName
     *
     * @return string
     */
    public function getTypeName()
    {
        // Ver: 8d1abe8e
        return $this->type_name;
    }

    /**
     * Set beginTime
     *
     * @param string $beginTime
     *
     * @return WorkShiftType
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
     * @return WorkShiftType
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
     * @return WorkShiftType
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
     * Set created
     *
     * @param integer $created
     *
     * @return WorkShiftType
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
     * @return WorkShiftType
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
