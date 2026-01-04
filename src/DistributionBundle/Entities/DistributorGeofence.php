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

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DistributorGeofence 店铺围栏信息
 *
 * @ORM\Table(name="distribution_distributor_geofence", options={"comment":"店铺围栏信息"}, indexes={
 *    @ORM\Index(name="ix_company_distributor_service", columns={"company_id","distributor_id","config_service_local_id","status"}),
 * })
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorGeofenceRepository")
 */
class DistributorGeofence
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"自增id"})
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
     * @ORM\Column(name="distributor_id", type="bigint", nullable=false, options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="config_service_local_id", type="bigint", nullable=false, options={"comment":"地图配置的本地主键ID"})
     */
    private $config_service_local_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="geofence_id", type="bigint", nullable=false, options={"comment":"第三方服务的围栏ID"})
     */
    private $geofence_id;

    /**
     * @var string
     *
     * @ORM\Column(name="geofence_data", type="text", options={"comment":"json数据，第三方服务的围栏信息"})
     */
    private $geofence_data;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", options={"comment":"状态【1 启用】【0 禁用】", "default": 1})
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
     * @return DistributorRelGeofence
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
     * @return DistributorRelGeofence
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
     * Set geofenceLocalId.
     *
     * @param int $geofenceLocalId
     *
     * @return DistributorRelGeofence
     */
    public function setGeofenceLocalId($geofenceLocalId)
    {
        $this->geofence_local_id = $geofenceLocalId;

        return $this;
    }

    /**
     * Get geofenceLocalId.
     *
     * @return int
     */
    public function getGeofenceLocalId()
    {
        return $this->geofence_local_id;
    }

    /**
     * Set configServiceLocalId.
     *
     * @param int $configServiceLocalId
     *
     * @return DistributorGeofence
     */
    public function setConfigServiceLocalId($configServiceLocalId)
    {
        $this->config_service_local_id = $configServiceLocalId;

        return $this;
    }

    /**
     * Get configServiceLocalId.
     *
     * @return int
     */
    public function getConfigServiceLocalId()
    {
        return $this->config_service_local_id;
    }

    /**
     * Set geofenceId.
     *
     * @param int $geofenceId
     *
     * @return DistributorGeofence
     */
    public function setGeofenceId($geofenceId)
    {
        $this->geofence_id = $geofenceId;

        return $this;
    }

    /**
     * Get geofenceId.
     *
     * @return int
     */
    public function getGeofenceId()
    {
        return $this->geofence_id;
    }

    /**
     * Set geofenceData.
     *
     * @param string $geofenceData
     *
     * @return DistributorGeofence
     */
    public function setGeofenceData($geofenceData)
    {
        $this->geofence_data = $geofenceData;

        return $this;
    }

    /**
     * Get geofenceData.
     *
     * @return string
     */
    public function getGeofenceData()
    {
        return $this->geofence_data;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return DistributorGeofence
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return DistributorGeofence
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
     * @param int|null $updated
     *
     * @return DistributorGeofence
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
