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

/**
 * SalespersonTaskRelDistributor 导购任务表
 *
 * @ORM\Table(name="salesperson_task_rel_distributor", options={"comment":"导购任务表"})
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonTaskRelDistributorRepository")
 */
class SalespersonTaskRelDistributor
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="task_id", type="bigint", options={"comment":"ID"})
     */
    private $task_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"门店id"})
     */
    private $distributor_id;


    /**
     * Set taskId.
     *
     * @param int $taskId
     *
     * @return SalespersonTaskRelDistributor
     */
    public function setTaskId($taskId)
    {
        $this->task_id = $taskId;

        return $this;
    }

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
     * @return SalespersonTaskRelDistributor
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return SalespersonTaskRelDistributor
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
}
