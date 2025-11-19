<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * PackagePromotions 组合促销主商品表
 *
 * @ORM\Table(name="promotions_package_main_item", options={"comment"="组合促销主商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_goods_id", columns={"goods_id"}),
 *    @ORM\Index(name="idx_main_item_id", columns={"main_item_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PackageMainItemRepository")
 */
class PackageMainItemPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="package_id", type="bigint", options={"comment":"组合促销规则id"})
     */
    private $package_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品id"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="main_item_id", type="bigint", options={"comment":"主商品id"})
     */
    private $main_item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="main_item_price", type="bigint", options={"comment":"主商品价格"})
     */
    private $main_item_price;



    /**
     * Set packageId.
     *
     * @param int $packageId
     *
     * @return PackageMainItemPromotions
     */
    public function setPackageId($packageId)
    {
        $this->package_id = $packageId;

        return $this;
    }

    /**
     * Get packageId.
     *
     * @return int
     */
    public function getPackageId()
    {
        return $this->package_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PackageMainItemPromotions
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
     * Set goodsId.
     *
     * @param int $goodsId
     *
     * @return PackageMainItemPromotions
     */
    public function setGoodsId($goodsId)
    {
        $this->goods_id = $goodsId;

        return $this;
    }

    /**
     * Get goodsId.
     *
     * @return int
     */
    public function getGoodsId()
    {
        return $this->goods_id;
    }

    /**
     * Set mainItemId.
     *
     * @param int $mainItemId
     *
     * @return PackageMainItemPromotions
     */
    public function setMainItemId($mainItemId)
    {
        $this->main_item_id = $mainItemId;

        return $this;
    }

    /**
     * Get mainItemId.
     *
     * @return int
     */
    public function getMainItemId()
    {
        return $this->main_item_id;
    }

    /**
     * Set mainItemPrice.
     *
     * @param int $mainItemPrice
     *
     * @return PackageMainItemPromotions
     */
    public function setMainItemPrice($mainItemPrice)
    {
        $this->main_item_price = $mainItemPrice;

        return $this;
    }

    /**
     * Get mainItemPrice.
     *
     * @return int
     */
    public function getMainItemPrice()
    {
        return $this->main_item_price;
    }
}
