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

namespace CommunityBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * community_items 社区拼团商品池
 *
 * @ORM\Table(name="community_items", options={"comment"="社区拼团商品池"}, indexes={
 *    @ORM\Index(name="ix_goods_id", columns={"goods_id"})
 * })
 * @ORM\Entity(repositoryClass="CommunityBundle\Repositories\CommunityItemsRepository")
 */
class CommunityItems
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="goods_id", type="bigint", options={"comment":"商品ID"})
     */
    private $goods_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *  为0时表示该商品为商城商品，否则为店铺自有商品
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id,为0时表示该商品为商城商品，否则为店铺自有商品", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="min_delivery_num", type="integer", options={"comment":"起送量", "default": 0})
     */
    private $min_delivery_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="integer", options={"comment":"商品排序", "default": 0})
     */
    private $sort = 0;

    /**
     * @var \DateTime $created_at
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created_at;

    /**
     * @var \DateTime $updated_at
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated_at;

    /**
     * get GoodsId
     *
     * @return int
     */
    public function getGoodsId()
    {
        // CRC: 2367340174
        return $this->goods_id;
    }

    /**
     * set GoodsId
     *
     * @param int $goods_id
     *
     * @return self
     */
    public function setGoodsId($goods_id)
    {
        $this->goods_id = $goods_id;
        return $this;
    }

    /**
     * get CompanyId
     *
     * @return int
     */
    public function getCompanyId()
    {
        // CRC: 2367340174
        return $this->company_id;
    }

    /**
     * set CompanyId
     *
     * @param int $company_id
     *
     * @return self
     */
    public function setCompanyId($company_id)
    {
        $this->company_id = $company_id;
        return $this;
    }

    /**
     * get DistributorId
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * set DistributorId
     *
     * @param int $distributor_id
     *
     * @return self
     */
    public function setDistributorId($distributor_id)
    {
        $this->distributor_id = $distributor_id;
        return $this;
    }

    /**
     * get MinDeliveryNum
     *
     * @return int
     */
    public function getMinDeliveryNum()
    {
        return $this->min_delivery_num;
    }

    /**
     * set MinDeliveryNum
     *
     * @param int $min_delivery_num
     *
     * @return self
     */
    public function setMinDeliveryNum($min_delivery_num)
    {
        $this->min_delivery_num = $min_delivery_num;
        return $this;
    }

    /**
     * get sort
     *
     * @return int
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * set Sort
     *
     * @param int $sort
     *
     * @return self
     */
    public function setSort($sort)
    {
        $this->sort = $sort;
        return $this;
    }


    /**
     * Set createdAt.
     *
     * @param int $createdAt
     *
     * @return CommunityItems
     */
    public function setCreatedAt($createdAt)
    {
        $this->created_at = $createdAt;

        return $this;
    }

    /**
     * Get createdAt.
     *
     * @return int
     */
    public function getCreatedAt()
    {
        return $this->created_at;
    }

    /**
     * Set updatedAt.
     *
     * @param int|null $updatedAt
     *
     * @return CommunityItems
     */
    public function setUpdatedAt($updatedAt = null)
    {
        $this->updated_at = $updatedAt;

        return $this;
    }

    /**
     * Get updatedAt.
     *
     * @return int|null
     */
    public function getUpdatedAt()
    {
        return $this->updated_at;
    }
}
