<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DataCubeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * RelSources 小程序页面与来源关联表
 *
 * @ORM\Table(name="datacube_relsources", options={"comment":"小程序页面与来源关联表"})
 * @ORM\Entity(repositoryClass="DataCubeBundle\Repositories\RelSourcesRepository")
 */
class RelSources
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
}
