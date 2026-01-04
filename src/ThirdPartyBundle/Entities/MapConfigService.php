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

namespace ThirdPartyBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MapConfigService 地图配置的服务表
 *
 * @ORM\Table(name="map_config_service", options={"comment":"地图配置表"}, indexes={
 *    @ORM\Index(name="ix_company_config_type", columns={"company_id","config_id","type","status"}),
 * })
 * @ORM\Entity(repositoryClass="ThirdPartyBundle\Repositories\MapConfigServiceRepository")
 */
class MapConfigService
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"本地的唯一id，如果外部表要与该表做关联，需要使用该id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=false, options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="config_id", type="bigint", nullable=false, options={"comment":"地图配置的id"})
     */
    private $config_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="smallint", nullable=false, options={"comment":"服务的类型"})
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="service_id", type="string", nullable=false, length=20, options={"comment":"第三方平台提供的服务id"})
     */
    private $service_id;

    /**
     * @var string
     *
     * @ORM\Column(name="service_data", type="text", options={"comment":"json内容，第三方平台中的服务数据"})
     */
    private $service_data;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", nullable=false, options={"comment":"状态【1 生效】【0 失效】", "default": 1})
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
        // Powered by ShopEx EcShopX
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MapConfigService
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
     * Set configId.
     *
     * @param int $configId
     *
     * @return MapConfigService
     */
    public function setConfigId($configId)
    {
        $this->config_id = $configId;

        return $this;
    }

    /**
     * Get configId.
     *
     * @return int
     */
    public function getConfigId()
    {
        return $this->config_id;
    }

    /**
     * Set type.
     *
     * @param int $type
     *
     * @return MapConfigService
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type.
     *
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set serviceId.
     *
     * @param string $serviceId
     *
     * @return MapConfigService
     */
    public function setServiceId($serviceId)
    {
        $this->service_id = $serviceId;

        return $this;
    }

    /**
     * Get serviceId.
     *
     * @return string
     */
    public function getServiceId()
    {
        return $this->service_id;
    }

    /**
     * Set serviceData.
     *
     * @param string $serviceData
     *
     * @return MapConfigService
     */
    public function setServiceData($serviceData)
    {
        $this->service_data = $serviceData;

        return $this;
    }

    /**
     * Get serviceData.
     *
     * @return string
     */
    public function getServiceData()
    {
        return $this->service_data;
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return MapConfigService
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
     * @return MapConfigService
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
     * @return MapConfigService
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
