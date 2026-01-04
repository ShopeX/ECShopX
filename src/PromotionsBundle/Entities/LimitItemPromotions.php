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
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * LimitItemPromotions 限购活动商品表
 *
 * @ORM\Table(name="promotions_limit_item", options={"comment"="限购活动商品表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="idx_item_id", columns={"item_id"}),
 *    @ORM\Index(name="idx_unique_item", columns={"company_id","distributor_id","item_id"})
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\LimitItemRepository")
 */
class LimitItemPromotions
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
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment":"店铺id"})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="item_id", type="bigint", options={"comment":"商品"})
     */
    private $item_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_num", type="bigint", options={"comment":"限购数量"})
     */
    private $limit_num;

    /**
     * @var string
     *  normal 实体类
     *  services 服务类
     *  tag 标签
     *  category 商品主类目
     *  brand 品牌
     *
     * @ORM\Column(name="item_type", type="string", options={"comment":"活动商品类型: normal:实体类商品,service:服务类商品,tag:标签,category:商品主类目,brand:品牌", "default":"normal"})
     */
    private $item_type = 'normal';

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="item_name", type="string", options={"comment":"商品名称"})
     */
    private $item_name;

    /**
     * @var string
     *
     * @ORM\Column(name="pics", type="text", options={"comment":"商品图片"})
     */
    private $pics;

    /**
     * @var integer
     *
     * @ORM\Column(name="price", type="integer", options={"comment":"商品原价"})
     */
    private $price;

    /**
     * @var string
     *
     * @ORM\Column(name="item_spec_desc", nullable=true, type="string", options={"comment":"产品规格描述", "default":""})
     */
    private $item_spec_desc;

    /**
     * @var integer
     *
     * @ORM\Column(name="start_time", type="integer", options={"comment":"起始时间"})
     */
    private $start_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="integer", options={"comment":"截止时间"})
     */
    private $end_time;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * Set limitId
     *
     * @param integer $limitId
     *
     * @return LimitItemPromotions
     */
    public function setLimitId($limitId)
    {
        $this->limit_id = $limitId;

        return $this;
    }

    /**
     * Get limitId
     *
     * @return integer
     */
    public function getLimitId()
    {
        return $this->limit_id;
    }

    /**
     * Set itemId
     *
     * @param integer $itemId
     *
     * @return LimitItemPromotions
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
     * Set itemType
     *
     * @param string $itemType
     *
     * @return LimitItemPromotions
     */
    public function setItemType($itemType)
    {
        $this->item_type = $itemType;

        return $this;
    }

    /**
     * Get itemType
     *
     * @return string
     */
    public function getItemType()
    {
        return $this->item_type;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return LimitItemPromotions
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
     * Set itemName
     *
     * @param string $itemName
     *
     * @return LimitItemPromotions
     */
    public function setItemName($itemName)
    {
        $this->item_name = $itemName;

        return $this;
    }

    /**
     * Get itemName
     *
     * @return string
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Set pics
     *
     * @param string $pics
     *
     * @return LimitItemPromotions
     */
    public function setPics($pics)
    {
        $this->pics = $pics;

        return $this;
    }

    /**
     * Get pics
     *
     * @return string
     */
    public function getPics()
    {
        return $this->pics;
    }

    /**
     * Set price
     *
     * @param integer $price
     *
     * @return LimitItemPromotions
     */
    public function setPrice($price)
    {
        $this->price = $price;

        return $this;
    }

    /**
     * Get price
     *
     * @return integer
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * Set itemSpecDesc
     *
     * @param string $itemSpecDesc
     *
     * @return LimitItemPromotions
     */
    public function setItemSpecDesc($itemSpecDesc)
    {
        $this->item_spec_desc = $itemSpecDesc;

        return $this;
    }

    /**
     * Get itemSpecDesc
     *
     * @return string
     */
    public function getItemSpecDesc()
    {
        return $this->item_spec_desc;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return LimitItemPromotions
     */
    public function setStartTime($startTime)
    {
        $this->start_time = $startTime;

        return $this;
    }

    /**
     * Get startTime
     *
     * @return integer
     */
    public function getStartTime()
    {
        return $this->start_time;
    }

    /**
     * Set endTime
     *
     * @param integer $endTime
     *
     * @return LimitItemPromotions
     */
    public function setEndTime($endTime)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime
     *
     * @return integer
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return LimitItemPromotions
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param integer $updated
     *
     * @return LimitItemPromotions
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return LimitItemPromotions
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

    /**
     * Set limitNum.
     *
     * @param int $limitNum
     *
     * @return LimitItemPromotions
     */
    public function setLimitNum($limitNum)
    {
        $this->limit_num = $limitNum;

        return $this;
    }

    /**
     * Get limitNum.
     *
     * @return int
     */
    public function getLimitNum()
    {
        return $this->limit_num;
    }
}
