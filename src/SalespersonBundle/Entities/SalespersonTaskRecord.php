<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SalespersonTaskRecord 导购任务表
 *
 * @ORM\Table(name="salesperson_task_record", options={"comment":"导购任务表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_task", columns={"company_id","task_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonTaskRecordRepository")
 */
class SalespersonTaskRecord
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
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="task_id", type="bigint", options={"comment":"任务id"})
     */
    private $task_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购id"})
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="times", type="smallint", options={"comment":"次数"})
     */
    private $times;

    /**
     * Get id.
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
     * @return SalespersonTaskRecord
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
     * Set taskId.
     *
     * @param int $taskId
     *
     * @return SalespersonTaskRecord
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
        // FIXME: check performance
        return $this->task_id;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return SalespersonTaskRecord
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
     * Set salespersonId.
     *
     * @param int $salespersonId
     *
     * @return SalespersonTaskRecord
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId.
     *
     * @return int
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set times.
     *
     * @param int $times
     *
     * @return SalespersonTaskRecord
     */
    public function setTimes($times)
    {
        $this->times = $times;

        return $this;
    }

    /**
     * Get times.
     *
     * @return int
     */
    public function getTimes()
    {
        return $this->times;
    }
}
