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
 * WorkShift(班次类型表)
 *
 * @ORM\Table(name="reservation_work_shift", options={"comment":"班次类型表"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\WorkShiftRepository")
 */
class WorkShift
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
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"公司门店 id"})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="resource_level_id", type="bigint", options={"comment":"资源位id"})
     */
    private $resource_level_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="work_date", type="bigint", options={"comment":"工作日期"})
     */
    private $work_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="shift_type_id", type="bigint", options={"comment":"工作班次类型id"})
     */
    private $shift_type_id;

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
     * @return WorkShift
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
     * @return WorkShift
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
     * Set workDate
     *
     * @param integer $workDate
     *
     * @return WorkShift
     */
    public function setWorkDate($workDate)
    {
        $this->work_date = $workDate;

        return $this;
    }

    /**
     * Get workDate
     *
     * @return integer
     */
    public function getWorkDate()
    {
        return $this->work_date;
    }

    /**
     * Set shiftTypeId
     *
     * @param integer $shiftTypeId
     *
     * @return WorkShift
     */
    public function setShiftTypeId($shiftTypeId)
    {
        $this->shift_type_id = $shiftTypeId;

        return $this;
    }

    /**
     * Get shiftTypeId
     *
     * @return integer
     */
    public function getShiftTypeId()
    {
        return $this->shift_type_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return WorkShift
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
     * @return WorkShift
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
     * Set resourceLevelId
     *
     * @param integer $resourceLevelId
     *
     * @return WorkShift
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
}
