<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
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
