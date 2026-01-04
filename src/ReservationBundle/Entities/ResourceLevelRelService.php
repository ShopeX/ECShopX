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

namespace ReservationBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ResourceLevelRelService(资源位服务项目关联表)
 *
 * @ORM\Table(name="reservation_level_rel_service", options={"comment":"资源位服务项目关联表"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\ResourceLevelRelServiceRepository")
 */
class ResourceLevelRelService
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="resource_level_id", type="bigint", options={"comment":"资源位id"})
     */
    private $resource_level_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司 company id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="shop_id", type="bigint", nullable=true, options={"comment":"门店id"})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="material_id", type="bigint", options={"comment":"服务项目id"})
     */
    private $material_id;

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
     * Set resourceLevelId
     *
     * @param integer $resourceLevelId
     *
     * @return ResourceLevelRelService
     */
    public function setResourceLevelId($resourceLevelId)
    {
        $this->resource_level_id = $resourceLevelId;

        return $this;
    }

    /**
     * Get resourceLevelId
     *
     * @return integer
     */
    public function getResourceLevelId()
    {
        return $this->resource_level_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ResourceLevelRelService
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
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ResourceLevelRelService
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return integer
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set materialId
     *
     * @param integer $materialId
     *
     * @return ResourceLevelRelService
     */
    public function setMaterialId($materialId)
    {
        $this->material_id = $materialId;

        return $this;
    }

    /**
     * Get materialId
     *
     * @return integer
     */
    public function getMaterialId()
    {
        return $this->material_id;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ResourceLevelRelService
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
     * @return ResourceLevelRelService
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
