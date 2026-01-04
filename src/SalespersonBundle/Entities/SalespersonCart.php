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

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SalespersonCart 导购员购物车
 *
 * @ORM\Table(name="companys_saleperson_cart", options={"comment"="导购员购物车"},
 *     indexes={
 *         @ORM\Index(name="idx_item_id", columns={"item_id"}),
 *         @ORM\Index(name="idx_salesperson_id", columns={"salesperson_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_is_checked", columns={"is_checked"}),
 *     },)
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonCartRepository")
 */
class SalespersonCart
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="cart_id", type="bigint", options={"comment":"购物车ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $cart_id;

    /**
     * @var string
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id"})
     */
    private $salesperson_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="package_items", type="string", options={"comment":"关联商品id集合","default":""})
     */
    private $package_items = "";

    /**
     * @var string
     *
     * @ORM\Column(name="num", type="bigint", options={"comment":"商品数量", "default" : 1})
     */
    private $num = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业id"})
     */
    private $company_id;

    /**
     * @var boolean
     *
     * @orm\column(name="is_checked", type="boolean", options={"comment":"购物车是否选中", "default": true})
     */
    private $is_checked = true;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id",type="bigint", options={"default":0, "comment":"店铺id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="special_type", type="string", options={"comment":"商品特殊类型 drug 处方药 normal 普通商品", "default":"normal"})
     */
    private $special_type = "normal";

    /**
     * Get cartId
     *
     * @return integer
     */
    public function getCartId()
    {
        return $this->cart_id;
    }

    /**
     * Set salespersonId
     *
     * @param integer $salespersonId
     *
     * @return SalespersonCart
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return SalespersonCart
     */
    public function setItemId($itemId)
    {
        $this->item_id = $itemId;

        return $this;
    }

    /**
     * Get itemId
     *
     * @return integer
     */
    public function getItemId()
    {
        return $this->item_id;
    }

    /**
     * Set packageItems
     *
     * @param string $packageItems
     *
     * @return SalespersonCart
     */
    public function setPackageItems($packageItems)
    {
        $this->package_items = $packageItems;

        return $this;
    }

    /**
     * Get packageItems
     *
     * @return string
     */
    public function getPackageItems()
    {
        return $this->package_items;
    }

    /**
     * Set num
     *
     * @param integer $num
     *
     * @return SalespersonCart
     */
    public function setNum($num)
    {
        $this->num = $num;

        return $this;
    }

    /**
     * Get num
     *
     * @return integer
     */
    public function getNum()
    {
        return $this->num;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return SalespersonCart
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
     * Set isChecked
     *
     * @param boolean $isChecked
     *
     * @return SalespersonCart
     */
    public function setIsChecked($isChecked)
    {
        $this->is_checked = $isChecked;

        return $this;
    }

    /**
     * Get isChecked
     *
     * @return boolean
     */
    public function getIsChecked()
    {
        return $this->is_checked;
    }

    /**
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return SalespersonCart
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
     * Set specialType
     *
     * @param string $specialType
     *
     * @return SalespersonCart
     */
    public function setSpecialType($specialType)
    {
        $this->special_type = $specialType;

        return $this;
    }

    /**
     * Get specialType
     *
     * @return string
     */
    public function getSpecialType()
    {
        return $this->special_type;
    }
}
