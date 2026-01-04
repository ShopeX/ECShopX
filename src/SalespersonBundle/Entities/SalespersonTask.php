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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SalespersonTask 导购任务表
 *
 * @ORM\Table(name="salesperson_task", options={"comment":"导购任务表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonTaskRepository")
 */
class SalespersonTask
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="task_id", type="bigint", options={"comment":"ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $task_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="bigint", options={"comment":"开始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", options={"comment":"结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="task_name", type="string", options={"comment":"任务名称"})
     */
    private $task_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="task_type", type="smallint", options={"comment":"任务类型 1 转发分享 2 获取新客 3 客户下单 4 会员福利"})
     */
    private $task_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="task_quota", type="smallint", options={"comment":"任务指标"})
     */
    private $task_quota;

    /**
     * @var json_array
     *
     * @ORM\Column(name="pics", nullable=true, type="json_array", options={"comment":"任务指标"})
     */
    private $pics;

    /**
     * @var string
     *
     * @ORM\Column(name="task_content", type="text", options={"comment":"任务内容"})
     */
    private $task_content;

    /**
     * @var boolean
     *
     * @ORM\Column(name="use_all_distributor", type="boolean", nullable=true, options={"comment":"是否是全部店铺","default":false})
     */
    private $use_all_distributor;

    /**
     * @var string
     *
     * @ORM\Column(name="disabled", type="string", length=30, options={"comment":"任务指标"})
     */
    private $disabled;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer")
     */
    private $updated;

    /**
     * Get taskId.
     *
     * @return int
     */
    public function getTaskId()
    {
        return $this->task_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonTask
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
     * Set startTime.
     *
     * @param int $startTime
     *
     * @return SalespersonTask
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime.
     *
     * @return int
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime.
     *
     * @param int $endTime
     *
     * @return SalespersonTask
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set taskName.
     *
     * @param string $taskName
     *
     * @return SalespersonTask
     */
    public function setTaskName($taskName)
    {
        $this->task_name = $taskName;

        return $this;
    }

    /**
     * Get taskName.
     *
     * @return string
     */
    public function getTaskName()
    {
        return $this->task_name;
    }

    /**
     * Set taskType.
     *
     * @param int $taskType
     *
     * @return SalespersonTask
     */
    public function setTaskType($taskType)
    {
        $this->task_type = $taskType;

        return $this;
    }

    /**
     * Get taskType.
     *
     * @return int
     */
    public function getTaskType()
    {
        return $this->task_type;
    }

    /**
     * Set taskQuota.
     *
     * @param int $taskQuota
     *
     * @return SalespersonTask
     */
    public function setTaskQuota($taskQuota)
    {
        $this->task_quota = $taskQuota;

        return $this;
    }

    /**
     * Get taskQuota.
     *
     * @return int
     */
    public function getTaskQuota()
    {
        return $this->task_quota;
    }

    /**
     * Set taskContent.
     *
     * @param string $taskContent
     *
     * @return SalespersonTask
     */
    public function setTaskContent($taskContent)
    {
        $this->task_content = $taskContent;

        return $this;
    }

    /**
     * Get taskContent.
     *
     * @return string
     */
    public function getTaskContent()
    {
        return $this->task_content;
    }

    /**
     * Set useAllDistributor.
     *
     * @param bool|null $useAllDistributor
     *
     * @return SalespersonTask
     */
    public function setUseAllDistributor($useAllDistributor = null)
    {
        $this->use_all_distributor = $useAllDistributor;

        return $this;
    }

    /**
     * Get useAllDistributor.
     *
     * @return bool|null
     */
    public function getUseAllDistributor()
    {
        return $this->use_all_distributor;
    }

    /**
     * Set disabled.
     *
     * @param string $disabled
     *
     * @return SalespersonTask
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled.
     *
     * @return string
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonTask
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
     * @return SalespersonTask
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
     * Set pics.
     *
     * @param array|null $pics
     *
     * @return SalespersonTask
     */
    public function setPics($pics = null)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics.
     *
     * @return array|null
     */
    public function getPics()
    {
        return $this->pics;
    }
}
