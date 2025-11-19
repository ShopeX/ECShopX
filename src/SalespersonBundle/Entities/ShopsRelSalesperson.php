<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Shops 店铺表
 *
 * @ORM\Table(name="shop_rel_salesperson", options={"comment":"店铺管理员关联店铺表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"})
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\ShopsRelSalespersonRepository")
 */
class ShopsRelSalesperson
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="shop_id", type="bigint", options={"comment":"店铺id"})
     */
    private $shop_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"店铺管理员id"})
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司company id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="store_type", type="string", options={"comment":"店铺类型，shop：门店, distributor:店铺", "default": "shop"})
     */
    private $store_type = "shop";

    /**
     * Set shopId
     *
     * @param integer $shopId
     *
     * @return ShopsRelSalesperson
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
     * Set salespersonId
     *
     * @param integer $salespersonId
     *
     * @return ShopsRelSalesperson
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId
     *
     * @return integer
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ShopsRelSalesperson
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
     * Set storeType
     *
     * @param string $storeType
     *
     * @return ShopsRelSalesperson
     */
    public function setStoreType($storeType)
    {
        $this->store_type = $storeType;

        return $this;
    }


    /**
     * Get storeType
     *
     * @return string
     */
    public function getStoreType()
    {
        return $this->store_type;
    }
}
