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

namespace EspierBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * UploadImagesCat 图片分类表
 *
 * @ORM\Table(name="espier_uploadimages_cat", options={"comment":"图片分类表"})
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\UploadImagesCatRepository")
 */

class UploadImagesCat
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="image_cat_id", type="bigint", options={"comment":"图片分类id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $image_cat_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="image_cat_name", type="string", options={"comment":"图片分类名称"})
     */
    private $image_cat_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="bigint", options={"comment":"父分类id,顶级为0", "default": 0})
     */
    private $parent_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="image_type", type="string", nullable=true, options={"comment":"图片类型,可选值有 item:商品;"})
     */
    private $image_type;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", length=255, options={"comment":"路径"})
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="bigint", options={"comment":"排序", "default":0})
     */
    private $sort = 0;

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
     * @var integer
     *
     * @ORM\Column(name="supplier_id", type="bigint", options={"comment":"供应商id", "default": 0})
     */
    private $supplier_id = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"店铺id", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id = 0;

    /**
     * Get imageCatId
     *
     * @return integer
     */
    public function getImageCatId()
    {
        return $this->image_cat_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UploadImagesCat
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
     * Set imageCatName
     *
     * @param string $imageCatName
     *
     * @return UploadImagesCat
     */
    public function setImageCatName($imageCatName)
    {
        $this->image_cat_name = $imageCatName;

        return $this;
    }

    /**
     * Get imageCatName
     *
     * @return string
     */
    public function getImageCatName()
    {
        return $this->image_cat_name;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return UploadImagesCat
     */
    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Set imageType
     *
     * @param string $imageType
     *
     * @return UploadImagesCat
     */
    public function setImageType($imageType)
    {
        $this->image_type = $imageType;

        return $this;
    }

    /**
     * Get imageType
     *
     * @return string
     */
    public function getImageType()
    {
        return $this->image_type;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return UploadImagesCat
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return UploadImagesCat
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return UploadImagesCat
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
     * @return UploadImagesCat
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

    /**
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return UploadImagesCat
     */
    public function setSupplierId($supplierId = 0)
    {
        $this->supplier_id = $supplierId;

        return $this;
    }

    /**
     * Get supplierId.
     *
     * @return int
     */
    public function getSupplierId()
    {
        return $this->supplier_id;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return UploadImagesCat
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return UploadImagesCat
     */
    public function setMerchantId($merchantId = 0)
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return int
     */
    public function getMerchantId()
    {
        return $this->merchant_id;
    }
}
