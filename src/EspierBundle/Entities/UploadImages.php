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
 * UploadImages 图片上传表
 *
 * @ORM\Table(name="espier_uploadimages", options={"comment":"图片上传表"})
 * @ORM\Entity(repositoryClass="EspierBundle\Repositories\UploadImagesRepository")
 */

class UploadImages
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="image_id", type="bigint", options={"comment":"图片id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $image_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="storage", type="string", length=50, options={"comment":"存储引擎，可选值有，image/videos","default":"image"})
     */
    private $storage = "image";

    /**
     * @var string
     *
     * @ORM\Column(name="image_name", type="string", options={"comment":"图片名称"})
     */
    private $image_name;

    /**
     * @var string
     *
     * @ORM\Column(name="brief", type="string", nullable=true, options={"comment":"图片简介", "default": ""})
     */
    private $brief;

    /**
     * @var integer
     *
     * @ORM\Column(name="image_cat_id", type="bigint", options={"comment":"图片分类id", "default": 0})
     */
    private $image_cat_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="image_type", type="string", length=50, nullable=true, options={"comment":"图片类型,可选值有 item:商品;aftersales:售后", "default": "item"})
     */
    private $image_type = "item";

    /**
     * @var string
     *
     * @ORM\Column(name="image_full_url", nullable=true, type="string", options={"comment":"图片完成地址"})
     */
    private $image_full_url;

    /**
     * @var string
     *
     * @ORM\Column(name="image_url", type="string", options={"comment":"图片标识, 不包含域名"})
     */
    private $image_url;

    /**
     * @var boolean
     *
     * @ORM\Column(name="disabled", type="boolean", options={"comment":"图片失效", "default": 0})
     */
    private $disabled = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="distributor_id", type="bigint", nullable=true, options={"comment":"店铺id", "default": 0})
     */
    private $distributor_id = 0;

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
     * @var integer
     *
     * @ORM\Column(name="merchant_id", type="bigint", options={"comment":"商户id", "default": 0})
     */
    private $merchant_id = 0;

    /**
     * Get imageId
     *
     * @return integer
     */
    public function getImageId()
    {
        return $this->image_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return UploadImages
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
     * Set storage
     *
     * @param string $storage
     *
     * @return UploadImages
     */
    public function setStorage($storage)
    {
        $this->storage = $storage;

        return $this;
    }

    /**
     * Get storage
     *
     * @return string
     */
    public function getStorage()
    {
        return $this->storage;
    }

    /**
     * Set imageName
     *
     * @param string $imageName
     *
     * @return UploadImages
     */
    public function setImageName($imageName)
    {
        $this->image_name = $imageName;

        return $this;
    }

    /**
     * Get imageName
     *
     * @return string
     */
    public function getImageName()
    {
        return $this->image_name;
    }

    /**
     * Set imageCatId
     *
     * @param integer $imageCatId
     *
     * @return UploadImages
     */
    public function setImageCatId($imageCatId)
    {
        $this->image_cat_id = $imageCatId;

        return $this;
    }

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
     * Set imageType
     *
     * @param string $imageType
     *
     * @return UploadImages
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
     * Set imageFullUrl
     *
     * @param string $imageFullUrl
     *
     * @return UploadImages
     */
    public function setImageFullUrl($imageFullUrl)
    {
        $this->image_full_url = $imageFullUrl;

        return $this;
    }

    /**
     * Get imageFullUrl
     *
     * @return string
     */
    public function getImageFullUrl()
    {
        return $this->image_full_url;
    }

    /**
     * Set imageUrl
     *
     * @param string $imageUrl
     *
     * @return UploadImages
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
     * Set disabled
     *
     * @param boolean $disabled
     *
     * @return UploadImages
     */
    public function setDisabled($disabled)
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * Get disabled
     *
     * @return boolean
     */
    public function getDisabled()
    {
        return $this->disabled;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return UploadImages
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
     * @return UploadImages
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
     * Set brief
     *
     * @param string $brief
     *
     * @return UploadImages
     */
    public function setBrief($brief)
    {
        $this->brief = $brief;

        return $this;
    }

    /**
     * Get brief
     *
     * @return string
     */
    public function getBrief()
    {
        return $this->brief;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return UploadImages
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
     * Set supplierId.
     *
     * @param int $supplierId
     *
     * @return UploadImages
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
     * Set merchantId.
     *
     * @param int $merchantId
     *
     * @return UploadImages
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
