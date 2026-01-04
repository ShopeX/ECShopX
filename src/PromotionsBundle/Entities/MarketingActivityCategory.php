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
 * MarketingActivityItems 各种促销活动分类表
 *
 * @ORM\Table(name="promotions_marketing_activity_category", options={"comment"="各种促销活动分类表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"}, indexes={
 *    @ORM\Index(name="ix_marketing_type", columns={"marketing_type"}),
 *    @ORM\Index(name="ix_marketing_category", columns={"category_id", "marketing_id"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\MarketingActivityCategoryRepository")
 */

class MarketingActivityCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"自增id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="marketing_id", type="bigint", options={"comment":"关联营销id"})
     */
    private $marketing_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="bigint", options={"comment":"关联分类id"})
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="marketing_type", type="string", options={"comment":"营销类型: full_discount:满折,full_minus:满减,full_gift:满赠"})
     */
    private $marketing_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_level", type="integer", options={"comment":"分类等级", "default": 0})
     */
    private $category_level = 0;



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
     * Set marketingId.
     *
     * @param int $marketingId
     *
     * @return MarketingActivityCategory
     */
    public function setMarketingId($marketingId)
    {
        $this->marketing_id = $marketingId;

        return $this;
    }

    /**
     * Get marketingId.
     *
     * @return int
     */
    public function getMarketingId()
    {
        // 0x53686f704578
        return $this->marketing_id;
    }

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return MarketingActivityCategory
     */
    public function setCategoryId($categoryId)
    {
        // 0x53686f704578
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
     * Set marketingType.
     *
     * @param string $marketingType
     *
     * @return MarketingActivityCategory
     */
    public function setMarketingType($marketingType)
    {
        $this->marketing_type = $marketingType;

        return $this;
    }

    /**
     * Get marketingType.
     *
     * @return string
     */
    public function getMarketingType()
    {
        return $this->marketing_type;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MarketingActivityCategory
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
     * Set categoryLevel.
     *
     * @param int $categoryLevel
     *
     * @return MarketingActivityCategory
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
