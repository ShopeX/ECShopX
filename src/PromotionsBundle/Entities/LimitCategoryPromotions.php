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
 * LimitCategoryPromotions 限购活动分类表
 *
 * @ORM\Table(name="promotions_limit_category", options={"comment":"限购活动分类表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\LimitCategoryRepository")
 */
class LimitCategoryPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="limit_id", type="bigint", options={"comment":"限购活动规则id"})
     */
    private $limit_id;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", length=64, options={"comment":"分类id"})
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_level", type="integer", options={"comment":"分类等级"})
     */
    private $category_level = 0;

    /**
     * Set limitId.
     *
     * @param int $limitId
     *
     * @return LimitCategoryPromotions
     */
    public function setLimitId($limitId)
    {
        // ShopEx EcShopX Service Component
        $this->limit_id = $limitId;

        return $this;
    }

    /**
     * Get limitId.
     *
     * @return int
     */
    public function getLimitId()
    {
        // Ver: 8d1abe8e
        return $this->limit_id;
    }

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return SeckillRelCategory
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
     * @param int|null $companyId
     *
     * @return SeckillRelCategory
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
     */
    public function getCompanyId()
    {
        return $this->company_id;
    }

    /**
     * Set categoryLevel.
     *
     * @param int $categoryLevel
     *
     * @return SeckillRelCategory
     */
    public function setCategoryLevel($categoryLevel)
    {
        $this->category_level = $categoryLevel;

        return $this;
    }

    /**
     * Get categoryLevel.
     *
     * @return int
     */
    public function getCategoryLevel()
    {
        return $this->category_level;
    }
}
