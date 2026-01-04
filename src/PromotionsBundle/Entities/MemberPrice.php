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
 * MemberPrice 商品会员价
 *
 * @ORM\Table(name="promotions_member_price", options={"comment"="商品会员价表"}, indexes={
 *    @ORM\Index(name="ix_item_id", columns={"item_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\MemberPriceRepository")
 */
class MemberPrice
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="price_id", type="bigint", options={"comment":"会员价ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $price_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品id"})
     */
    private $item_id;

    /**
     * @var string
     *
     * @ORM\Column(name="price", type="json_array", options={"comment":"商品价格"})
     */
    private $mprice;

    /**
     * Get priceId
     *
     * @return integer
     */
    public function getPriceId()
    {
        return $this->price_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberPrice
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
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return MemberPrice
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
     * Set mprice
     *
     * @param array $mprice
     *
     * @return MemberPrice
     */
    public function setMprice($mprice)
    {
        $this->mprice = $mprice;

        return $this;
    }

    /**
     * Get mprice
     *
     * @return array
     */
    public function getMprice()
    {
        return $this->mprice;
    }
}
