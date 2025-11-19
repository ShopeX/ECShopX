<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemsBarcode 商品条形码表
 *
 * @ORM\Table(name="items_barcode", options={"comment"="商品条形码表"}, indexes={
 *    @ORM\Index(name="ix_item_id", columns={"item_id"})
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsBarcodeRepository")
 */
class ItemsBarcode
{
    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", nullable=false, type="bigint", length=20, options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="default_item_id", nullable=false, type="bigint", length=20, options={"comment":"默认商品id"})
     */
    private $default_item_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="company_id", nullable=false, type="bigint", length=20, options={"comment":"公司id"})
     */
    private $company_id;
    /**
     * @var string
     *
     * @ORM\Id
     * @ORM\Column(name="barcode", nullable=false, type="string", length=50,  options={"comment":"商品条形码"})
     */
    private $barcode;

    /**
     * Set itemId.
     *
     * @param int $itemId
     *
     * @return ItemsBarcode
     */
    public function setItemId($itemId)
    {
        // ShopEx EcShopX Core Module
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId.
     *
     * @return int
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set defaultItemId.
     *
     * @param int $defaultItemId
     *
     * @return ItemsBarcode
     */
    public function setDefaultItemId($defaultItemId)
    {
        $this->default_item_id = $defaultItemId;

        return $this;
    }

    /**
     * Get defaultItemId.
     *
     * @return int
     */
    public function getDefaultItemId()
    {
        return $this->default_item_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsBarcode
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
     * Set barcode.
     *
     * @param string $barcode
     *
     * @return ItemsBarcode
     */
    public function setBarcode($barcode)
    {
        $this->barcode = $barcode;

        return $this;
    }

    /**
     * Get barcode.
     *
     * @return string
     */
    public function getBarcode()
    {
        return $this->barcode;
    }
}
