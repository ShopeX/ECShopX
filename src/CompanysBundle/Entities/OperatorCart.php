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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * OperatorCart 店务管理员购物车
 *
 * @ORM\Table(name="companys_operator_cart", options={"comment"="导购员购物车"},
 *     indexes={
 *         @ORM\Index(name="idx_item_id", columns={"item_id"}),
 *         @ORM\Index(name="idx_operator_id", columns={"operator_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },)
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\OperatorCartRepository")
 */
class OperatorCart
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
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"企业id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id",type="bigint", options={"default":0, "comment":"店铺id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="operator_id", type="bigint", options={"comment":"管理员id"})
     */
    private $operator_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="num", type="bigint", options={"comment":"商品数量", "default" : 1})
     */
    private $num = 1;

    /**
     * @var boolean
     *
     * @orm\column(name="is_checked", type="boolean", options={"comment":"购物车是否选中", "default": true})
     */
    private $is_checked = true;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return OperatorCart
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return OperatorCart
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
     * Set operatorId
     *
     * @param integer $operatorId
     *
     * @return OperatorCart
     */
    public function setOperatorId($operatorId)
    {
        $this->operator_id = $operatorId;

        return $this;
    }

    /**
     * Get operatorId
     *
     * @return integer
     */
    public function getOperatorId()
    {
        return $this->operator_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return OperatorCart
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
     * Set num
     *
     * @param integer $num
     *
     * @return OperatorCart
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
     * Set isChecked
     *
     * @param boolean $isChecked
     *
     * @return OperatorCart
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
     * Set specialType
     *
     * @param string $specialType
     *
     * @return OperatorCart
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
