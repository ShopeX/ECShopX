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
 * SalesPromotions 导购促销单
 *
 * @ORM\Table(name="companys_sales_promotions", options={"comment"="导购促销单"},
 *     indexes={
 *         @ORM\Index(name="idx_salesperson_id", columns={"salesperson_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_unique_key", columns={"unique_key"}),
 *     },)
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalesPromotionsRepository")
 */
class SalesPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="sales_promotion_id", type="bigint", options={"comment":"促销单ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $sales_promotion_id;

    /**
     * @var string
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id"})
     */
    private $salesperson_id;

    /**
     * @var string
     *
     * @ORM\Column(name="unique_key", type="string", options={"comment":"促销单唯一key"})
     */
    private $unique_key;

    /**
     * @var string
     *
     * @ORM\Column(name="promotion_items", type="text", options={"comment":"促销单内容"})
     */
    private $promotion_items;

    /**
     * @var string
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
     * Get salesPromotionId.
     *
     * @return int
     */
    public function getSalesPromotionId()
    {
        return $this->sales_promotion_id;
    }

    /**
     * Set salespersonId.
     *
     * @param int $salespersonId
     *
     * @return SalesPromotions
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId.
     *
     * @return int
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set uniqueKey.
     *
     * @param string $uniqueKey
     *
     * @return SalesPromotions
     */
    public function setUniqueKey($uniqueKey)
    {
        $this->unique_key = $uniqueKey;

        return $this;
    }

    /**
     * Get uniqueKey.
     *
     * @return string
     */
    public function getUniqueKey()
    {
        return $this->unique_key;
    }

    /**
     * Set promotionItems.
     *
     * @param string $promotionItems
     *
     * @return SalesPromotions
     */
    public function setPromotionItems($promotionItems)
    {
        $this->promotion_items = $promotionItems;

        return $this;
    }

    /**
     * Get promotionItems.
     *
     * @return string
     */
    public function getPromotionItems()
    {
        return $this->promotion_items;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalesPromotions
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return SalesPromotions
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }
}
