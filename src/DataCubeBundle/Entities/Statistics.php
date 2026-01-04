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

namespace DataCubeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Statistics 小程序页面与来源关联表
 *
 * @ORM\Table(name="datacube_statistics", options={"comment":"小程序页面与来源关联表"})
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\StatisticsRepository")
 */
class Statistics
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="monitor_id", type="bigint", options={"comment":"监控id"})
     */
    private $monitor_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="source_id", type="bigint", options={"comment":"来源id"})
     */
    private $source_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="view_num", type="bigint", options={"comment":"浏览人数", "default":0})
     *
     */
    private $view_num;

    /**
      * @var integer
      *
      * @ORM\Column(name="entries_num", type="bigint", options={"comment":"参与人数", "default":0})
      *
      */
    private $entries_num;

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
     * @ORM\Column(type="integer")
     */
    protected $updated;

    /**
     * Set monitorId
     *
     * @param integer $monitorId
     *
     * @return MonitorsSources
     */
    public function setMonitorId($monitorId)
    {
        $this->monitor_id = $monitorId;

        return $this;
    }

    /**
     * Get monitorId
     *
     * @return integer
     */
    public function getMonitorId()
    {
        return $this->monitor_id;
    }

    /**
     * Set sourceId
     *
     * @param integer $sourceId
     *
     * @return MonitorsSources
     */
    public function setSourceId($sourceId)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId
     *
     * @return integer
     */
    public function getSourceId()
    {
        return $this->source_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MonitorsSources
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
     * Set viewNum
     *
     * @param integer $viewNum
     *
     * @return MonitorsSources
     */
    public function setViewNum($viewNum)
    {
        $this->view_num = $viewNum;

        return $this;
    }

    /**
     * Get viewNum
     *
     * @return integer
     */
    public function getViewNum()
    {
        return $this->view_num;
    }

    /**
     * Set entriesNum
     *
     * @param integer $entriesNum
     *
     * @return MonitorsSources
     */
    public function setEntriesNum($entriesNum)
    {
        $this->entries_num = $entriesNum;

        return $this;
    }

    /**
     * Get entriesNum
     *
     * @return integer
     */
    public function getEntriesNum()
    {
        return $this->entries_num;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MonitorsSources
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
     * @return MonitorsSources
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
