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

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * ItemsCategoryProfit 商品分润配置表
 *
 * @ORM\Table(name="items_category_profit", options={"comment"="商品分润配置表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\ItemsCategoryProfitRepository")
 */
class ItemsCategoryProfit
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", options={"comment":"商品ID"})
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="profit_type", nullable=true, type="string", options={"comment":"分佣计算方式", "default":"default"})
     */
    private $profit_type;
    /**
     * @var integer
     *
     * @ORM\Column(name="profit_conf", nullable=true, type="json_array", options={"comment":"分销配置"})
     */
    private $profit_conf;

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return ItemsCategoryProfit
     */
    public function setCategoryId($categoryId)
    {
        $this->category_id = $categoryId;

        return $this;
    }

    /**
     * Get categoryId.
     *
     * @return int
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return ItemsCategoryProfit
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
     * Set profitType.
     *
     * @param string|null $profitType
     *
     * @return ItemsCategoryProfit
     */
    public function setProfitType($profitType = null)
    {
        $this->profit_type = $profitType;

        return $this;
    }

    /**
     * Get profitType.
     *
     * @return string|null
     */
    public function getProfitType()
    {
        return $this->profit_type;
    }

    /**
     * Set profitConf.
     *
     * @param array|null $profitConf
     *
     * @return ItemsCategoryProfit
     */
    public function setProfitConf($profitConf = null)
    {
        $this->profit_conf = $profitConf;

        return $this;
    }

    /**
     * Get profitConf.
     *
     * @return array|null
     */
    public function getProfitConf()
    {
        return $this->profit_conf;
    }
}
