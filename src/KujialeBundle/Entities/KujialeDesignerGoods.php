<?php

namespace KujialeBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * KujialeDesignerGoods
 *
 * @ORM\Table(name="kujiale_designer_goods", indexes={@ORM\Index(name="idx_good_id", columns={"good_id"})})
 * @ORM\Entity(repositoryClass="KujialeBundle\Repositories\KujialeDesignerGoodsRepository")
 */
class KujialeDesignerGoods
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="bigint", nullable=false, options={"comment":"id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="good_id", type="string", length=128, nullable=false, options={"comment":"渲染图ID"})
     */
    private $good_id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="dimensions", type="string", length=32, nullable=false, options={"comment":"尺寸"})
     */
    private $dimensions = '';

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=false, options={"comment":"描述"})
     */
    private $description = '';

    /**
     * @var string
     *
     * @ORM\Column(name="brand_good_code", type="string", length=128, nullable=false, options={"comment":"商品编码"})
     */
    private $brand_good_code = '';

    /**
     * @var string
     *
     * @ORM\Column(name="brand_good_name", type="string", length=128, nullable=false, options={"comment":"商品名称"})
     */
    private $brand_good_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="brand_id", type="string", length=32, nullable=false, options={"comment":"品牌id"})
     */
    private $brand_id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="brand_name", type="string", length=128, nullable=false, options={"comment":"品牌名称"})
     */
    private $brand_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="series_tag_id", type="string", length=32, nullable=false, options={"comment":"系列id"})
     */
    private $series_tag_id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="series_tag_name", type="string", length=128, nullable=false, options={"comment":"系列名称"})
     */
    private $series_tag_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="product_number", type="string", length=32, nullable=false, options={"comment":"型号"})
     */
    private $product_number = '';

    /**
     * @var string
     *
     * @ORM\Column(name="customer_texture", type="string", length=64, nullable=false, options={"comment":"材质"})
     */
    private $customer_texture = '';

    /**
     * @var string
     *
     * @ORM\Column(name="buy_link", type="string", length=255, nullable=false, options={"comment":"购买链接"})
     */
    private $buy_link = '';

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", nullable=false, options={"comment":"创建时间"})
     */
    private $created;

    /**
     * @var int|null
     *
     * @ORM\Column(name="updated", type="integer", nullable=true, options={"comment":"更新时间"})
     */
    private $updated;



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
     * Set goodId.
     *
     * @param string $goodId
     *
     * @return KujialeDesignerGoods
     */
    public function setGoodId($goodId)
    {
        $this->good_id = $goodId;

        return $this;
    }

    /**
     * Get goodId.
     *
     * @return string
     */
    public function getGoodId()
    {
        return $this->good_id;
    }

    /**
     * Set dimensions.
     *
     * @param string $dimensions
     *
     * @return KujialeDesignerGoods
     */
    public function setDimensions($dimensions)
    {
        $this->dimensions = $dimensions;

        return $this;
    }

    /**
     * Get dimensions.
     *
     * @return string
     */
    public function getDimensions()
    {
        return $this->dimensions;
    }

    /**
     * Set description.
     *
     * @param string $description
     *
     * @return KujialeDesignerGoods
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set brandGoodCode.
     *
     * @param string $brandGoodCode
     *
     * @return KujialeDesignerGoods
     */
    public function setBrandGoodCode($brandGoodCode)
    {
        $this->brand_good_code = $brandGoodCode;

        return $this;
    }

    /**
     * Get brandGoodCode.
     *
     * @return string
     */
    public function getBrandGoodCode()
    {
        return $this->brand_good_code;
    }

    /**
     * Set brandGoodName.
     *
     * @param string $brandGoodName
     *
     * @return KujialeDesignerGoods
     */
    public function setBrandGoodName($brandGoodName)
    {
        $this->brand_good_name = $brandGoodName;

        return $this;
    }

    /**
     * Get brandGoodName.
     *
     * @return string
     */
    public function getBrandGoodName()
    {
        return $this->brand_good_name;
    }

    /**
     * Set brandId.
     *
     * @param string $brandId
     *
     * @return KujialeDesignerGoods
     */
    public function setBrandId($brandId)
    {
        $this->brand_id = $brandId;

        return $this;
    }

    /**
     * Get brandId.
     *
     * @return string
     */
    public function getBrandId()
    {
        return $this->brand_id;
    }

    /**
     * Set brandName.
     *
     * @param string $brandName
     *
     * @return KujialeDesignerGoods
     */
    public function setBrandName($brandName)
    {
        $this->brand_name = $brandName;

        return $this;
    }

    /**
     * Get brandName.
     *
     * @return string
     */
    public function getBrandName()
    {
        return $this->brand_name;
    }

    /**
     * Set seriesTagId.
     *
     * @param string $seriesTagId
     *
     * @return KujialeDesignerGoods
     */
    public function setSeriesTagId($seriesTagId)
    {
        $this->series_tag_id = $seriesTagId;

        return $this;
    }

    /**
     * Get seriesTagId.
     *
     * @return string
     */
    public function getSeriesTagId()
    {
        return $this->series_tag_id;
    }

    /**
     * Set seriesTagName.
     *
     * @param string $seriesTagName
     *
     * @return KujialeDesignerGoods
     */
    public function setSeriesTagName($seriesTagName)
    {
        $this->series_tag_name = $seriesTagName;

        return $this;
    }

    /**
     * Get seriesTagName.
     *
     * @return string
     */
    public function getSeriesTagName()
    {
        return $this->series_tag_name;
    }

    /**
     * Set productNumber.
     *
     * @param string $productNumber
     *
     * @return KujialeDesignerGoods
     */
    public function setProductNumber($productNumber)
    {
        $this->product_number = $productNumber;

        return $this;
    }

    /**
     * Get productNumber.
     *
     * @return string
     */
    public function getProductNumber()
    {
        return $this->product_number;
    }

    /**
     * Set customerTexture.
     *
     * @param string $customerTexture
     *
     * @return KujialeDesignerGoods
     */
    public function setCustomerTexture($customerTexture)
    {
        $this->customer_texture = $customerTexture;

        return $this;
    }

    /**
     * Get customerTexture.
     *
     * @return string
     */
    public function getCustomerTexture()
    {
        return $this->customer_texture;
    }

    /**
     * Set buyLink.
     *
     * @param string $buyLink
     *
     * @return KujialeDesignerGoods
     */
    public function setBuyLink($buyLink)
    {
        $this->buy_link = $buyLink;

        return $this;
    }

    /**
     * Get buyLink.
     *
     * @return string
     */
    public function getBuyLink()
    {
        return $this->buy_link;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return KujialeDesignerGoods
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
     * @return KujialeDesignerGoods
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
