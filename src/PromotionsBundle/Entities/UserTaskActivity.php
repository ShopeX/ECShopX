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

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UserTask 用户任务
 *
 * @ORM\Table(name="user_task_activity", options={"comment":"签到日志"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserTaskActivityRepository")
 */
class UserTaskActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     *
     * @ORM\Column(name="area_id", type="bigint", options={"comment":"区域id", "default": 0})
     */
    private $area_id = 0;

    /**
     * @var string
     * @ORM\Column(name="title", type="string", options={"comment":"活动标题"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="bigint", options={"comment":"开始时间", "default": 0})
     */
    private $begin_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", options={"comment":"结束时间", "default": 0})
     */
    private $end_time = 0;



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
    private $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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

    public function getAreaId()
    {
        return $this->area_id;
    }

    public function setAreaId($area_id)
    {
        $this->area_id = $area_id;
    }



    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated( $created): void
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated( $updated)
    {
        $this->updated = $updated;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getBeginTime(): int
    {
        return $this->begin_time;
    }

    public function setBeginTime(int $begin_time): void
    {
        $this->begin_time = $begin_time;
    }

    public function getEndTime(): int
    {
        return $this->end_time;
    }

    public function setEndTime(int $end_time): void
    {
        $this->end_time = $end_time;
    }


}
