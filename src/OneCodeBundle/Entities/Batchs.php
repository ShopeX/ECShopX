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

namespace OneCodeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Batchs
 *
 * @ORM\Table(name="onecode_batchs", options={"comment"="物品批次表"})
 * @ORM\Entity(repositoryClass="OneCodeBundle\Repositories\BatchsRepository")
 */
class Batchs
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="batch_id", type="bigint", options={"comment":"批次ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $batch_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="thing_id", type="bigint", options={"comment":"物品ID"})
     */
    private $thing_id;

    /**
     * @var string
     *
     * @ORM\Column(name="batch_number", type="string", length=255, options={"comment":"批次编号"})
     */
    private $batch_number;

    /**
     * @var string
     *
     * @ORM\Column(name="batch_name", type="string", options={"comment":"批次名称"})
     */
    private $batch_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="batch_quantity", type="integer", options={"comment":"批次件数"})
     */
    private $batch_quantity;

    /**
     * @var boolean
     *
     * @ORM\Column(name="show_trace", type="boolean", options={"default":true, "comment":"前台是否可以查看流通信息"})
     */
    private $show_trace = true;

    /**
     * @var string
     *
     * @ORM\Column(name="trace_info", type="json_array", nullable=true, options={"comment":"流通信息"})
     */
    private $trace_info;

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
     * Get batchId
     *
     * @return integer
     */
    public function getBatchId()
    {
        return $this->batch_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Batchs
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
     * Set thingId
     *
     * @param integer $thingId
     *
     * @return Batchs
     */
    public function setThingId($thingId)
    {
        $this->thing_id = $thingId;

        return $this;
    }

    /**
     * Get thingId
     *
     * @return integer
     */
    public function getThingId()
    {
        return $this->thing_id;
    }

    /**
     * Set batchNumber
     *
     * @param string $batchNumber
     *
     * @return Batchs
     */
    public function setBatchNumber($batchNumber)
    {
        $this->batch_number = $batchNumber;

        return $this;
    }

    /**
     * Get batchNumber
     *
     * @return string
     */
    public function getBatchNumber()
    {
        return $this->batch_number;
    }

    /**
     * Set batchName
     *
     * @param string $batchName
     *
     * @return Batchs
     */
    public function setBatchName($batchName)
    {
        $this->batch_name = $batchName;

        return $this;
    }

    /**
     * Get batchName
     *
     * @return string
     */
    public function getBatchName()
    {
        return $this->batch_name;
    }

    /**
     * Set batchQuantity
     *
     * @param integer $batchQuantity
     *
     * @return Batchs
     */
    public function setBatchQuantity($batchQuantity)
    {
        $this->batch_quantity = $batchQuantity;

        return $this;
    }

    /**
     * Get batchQuantity
     *
     * @return integer
     */
    public function getBatchQuantity()
    {
        return $this->batch_quantity;
    }

    /**
     * Set showTrace
     *
     * @param boolean $showTrace
     *
     * @return Batchs
     */
    public function setShowTrace($showTrace)
    {
        $this->show_trace = $showTrace;

        return $this;
    }

    /**
     * Get showTrace
     *
     * @return boolean
     */
    public function getShowTrace()
    {
        return $this->show_trace;
    }

    /**
     * Set traceInfo
     *
     * @param string $traceInfo
     *
     * @return Batchs
     */
    public function setTraceInfo($traceInfo)
    {
        $this->trace_info = $traceInfo;

        return $this;
    }

    /**
     * Get traceInfo
     *
     * @return string
     */
    public function getTraceInfo()
    {
        return $this->trace_info;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Batchs
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
     * @return Batchs
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
