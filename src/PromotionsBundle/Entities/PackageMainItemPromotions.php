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
