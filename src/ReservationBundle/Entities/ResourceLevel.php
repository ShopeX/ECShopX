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
 * ResourceLevel(资源位)
 *
 * @ORM\Table(name="reservation_resource_level", options={"comment":"资源位表"})
 * @ORM\Entity(repositoryClass="ReservationBundle\Repositories\ResourceLevelRepository")
 */

class ResourceLevel
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="resource_level_id", type="bigint", options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $resource_level_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="string", options={"comment":"企业company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_id", type="string", options={"comment":"门店id"})
     */
    private $shop_id;

    /**
     * @var string
     *
     * @ORM\Column(name="shop_name", type="string", nullable=true, options={"comment":"门店名称"})
     */
    private $shop_name;

    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", options={"comment":"资源位昵称"})
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", nullable=true, length=500, options={"comment":"简单介绍"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", nullable=true, options={"comment":"状态,active:有效，invalid: 失效", "default": "active"})
     */
    private $status = "active";

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", nullable=true, type="string", options={"comment":"图片"})
     */
    private $image_url;

    /**
     * @var string
     *
     * @ORM\Column(name="quantity", type="string", options={"comment":"数量", "default":1})
     */
    private $quantity;

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
     * @param string $companyId
     *
     * @return ResourceLevel
     */
    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId
     *
     * @return string
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set shopId
     *
     * @param string $shopId
     *
     * @return ResourceLevel
     */
    public function setShopId($shopId)
    {
        $this->shop_id = $shopId;

        return $this;
    }

    /**
     * Get shopId
     *
     * @return string
     */
    public function getShopId()
    {
        return $this->shop_id;
    }

    /**
     * Set shopName
     *
     * @param string $shopName
     *
     * @return ResourceLevel
     */
    public function setShopName($shopName)
    {
        $this->shop_name = $shopName;

        return $this;
    }

    /**
     * Get shopName
     *
     * @return string
     */
    public function getShopName()
    {
        return $this->shop_name;
    }

    /**
     * Set name
     *
     * @param string $name
     *
     * @return ResourceLevel
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set description
     *
     * @param string $description
     *
     * @return ResourceLevel
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set status
     *
     * @param string $status
     *
     * @return ResourceLevel
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return ResourceLevel
     */
    public function setImageUrl($imageUrl)
    {
        $this->image_url = $imageUrl;

        return $this;
    }

    /**
     * Get imageUrl
     *
     * @return string
     */
    public function getImageUrl()
    {
        return $this->image_url;
    }

    /**
     * Set quantity
     *
     * @param string $quantity
     *
     * @return ResourceLevel
     */
    public function setQuantity($quantity)
    {
        $this->quantity = $quantity;

        return $this;
    }

    /**
     * Get quantity
     *
     * @return string
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ResourceLevel
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
     * @return ResourceLevel
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
