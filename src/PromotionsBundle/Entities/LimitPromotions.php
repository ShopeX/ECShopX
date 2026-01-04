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
 * LimitPromotions 限购活动规则表
 *
 * @ORM\Table(name="promotions_limit", options={"comment"="限购活动规则表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\LimitRepository")
 */
class LimitPromotions
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="limit_id", type="bigint", options={"comment":"限购活动id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $limit_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_name", type="string", length=50, options={"comment":"限购活动名称"})
     */
    private $limit_name;

    /**
     * @var string
     *
     * @ORM\Column(name="limit_type", type="string", nullable=true, length=50, options={"comment":"限购类型, 全局限购：global, 店铺限购：shop"})
     */
    private $limit_type;

    /**
     * @var string
     *
     * @ORM\Column(name="valid_grade", type="string", options={"comment":"会员级别集合"})
     */
    private $valid_grade;

    /**
     * @var string
     *
     * @ORM\Column(name="rule", type="string", length=255, options={"comment":"限购规则"})
     */
    private $rule;

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
     * @var string
     *
     * @ORM\Column(name="use_bound", type="integer", options={"comment":"适用范围: 1:指定商品可用,2:指定分类可用,3:指定商品标签可用,4:指定商品品牌可用", "default":1})
     */
    private $use_bound = 1;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_ids", type="text", nullable=true, options={"comment":"标签id集合"})
     */
    private $tag_ids;

    /**
     * @var string
     *
     * @ORM\Column(name="brand_ids", type="text", nullable=true, options={"comment":"品牌id集合"})
     */
    private $brand_ids;

    /**
     * @var integer
     *
     * @ORM\Column(name="total_item_num", type="integer", options={"comment":"限购商品总数", "default":0})
     */
    private $total_item_num = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="valid_item_num", type="integer", options={"comment":"已导入的限购商品数", "default":0})
     */
    private $valid_item_num = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="error_desc", type="text", nullable=true, options={"comment":"导入错误描述"})
     */
    private $error_desc;

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
     * @var string
     *
     * @ORM\Column(name="source_type", type="string", length=20, nullable=true, options={"comment":"添加者类型：distributor"})
     */
    private $source_type;

    /**
     * @var integer
     *
     * @ORM\Column(name="source_id", type="bigint", nullable=true, options={"comment":"添加者ID: 如店铺ID", "default":0})
     */
    private $source_id = 0;

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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return LimitPromotions
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
     * Set limitName
     *
     * @param string $limitName
     *
     * @return LimitPromotions
     */
    public function setLimitName($limitName)
    {
        $this->limit_name = $limitName;

        return $this;
    }

    /**
     * Get limitName
     *
     * @return string
     */
    public function getLimitName()
    {
        return $this->limit_name;
    }

    /**
     * Set rule
     *
     * @param string $rule
     *
     * @return LimitPromotions
     */
    public function setRule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * Get rule
     *
     * @return string
     */
    public function getRule()
    {
        return $this->rule;
    }

    /**
     * Set startTime
     *
     * @param integer $startTime
     *
     * @return LimitPromotions
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
     * @return LimitPromotions
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
     * @return LimitPromotions
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
     * @return LimitPromotions
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
     * Set validGrade
     *
     * @param string $validGrade
     *
     * @return LimitPromotions
     */
    public function setValidGrade($validGrade)
    {
        $this->valid_grade = $validGrade;

        return $this;
    }

    /**
     * Get validGrade
     *
     * @return string
     */
    public function getValidGrade()
    {
        return $this->valid_grade;
    }

    /**
     * Set useBound.
     *
     * @param int $useBound
     *
     * @return LimitPromotions
     */
    public function setUseBound($useBound)
    {
        $this->use_bound = $useBound;

        return $this;
    }

    /**
     * Get useBound.
     *
     * @return int
     */
    public function getUseBound()
    {
        return $this->use_bound;
    }

    /**
     * Set tagIds.
     *
     * @param string|null $tagIds
     *
     * @return LimitPromotions
     */
    public function setTagIds($tagIds = null)
    {
        $this->tag_ids = $tagIds;

        return $this;
    }

    /**
     * Get tagIds.
     *
     * @return string|null
     */
    public function getTagIds()
    {
        return $this->tag_ids;
    }

    /**
     * Set brandIds.
     *
     * @param string|null $brandIds
     *
     * @return LimitPromotions
     */
    public function setBrandIds($brandIds = null)
    {
        $this->brand_ids = $brandIds;

        return $this;
    }

    /**
     * Get brandIds.
     *
     * @return string|null
     */
    public function getBrandIds()
    {
        return $this->brand_ids;
    }

    /**
     * Set limitType.
     *
     * @param string|null $limitType
     *
     * @return LimitPromotions
     */
    public function setLimitType($limitType = null)
    {
        $this->limit_type = $limitType;

        return $this;
    }

    /**
     * Get limitType.
     *
     * @return string|null
     */
    public function getLimitType()
    {
        return $this->limit_type;
    }

    /**
     * Set totalItemNum.
     *
     * @param int $totalItemNum
     *
     * @return LimitPromotions
     */
    public function setTotalItemNum($totalItemNum)
    {
        $this->total_item_num = $totalItemNum;

        return $this;
    }

    /**
     * Get totalItemNum.
     *
     * @return int
     */
    public function getTotalItemNum()
    {
        return $this->total_item_num;
    }

    /**
     * Set validItemNum.
     *
     * @param int $validItemNum
     *
     * @return LimitPromotions
     */
    public function setValidItemNum($validItemNum)
    {
        $this->valid_item_num = $validItemNum;

        return $this;
    }

    /**
     * Get validItemNum.
     *
     * @return int
     */
    public function getValidItemNum()
    {
        return $this->valid_item_num;
    }

    /**
     * Set errorDesc.
     *
     * @param string|null $errorDesc
     *
     * @return LimitPromotions
     */
    public function setErrorDesc($errorDesc = null)
    {
        $this->error_desc = $errorDesc;

        return $this;
    }

    /**
     * Get errorDesc.
     *
     * @return string|null
     */
    public function getErrorDesc()
    {
        return $this->error_desc;
    }

    /**
     * Set sourceType.
     *
     * @param string|null $sourceType
     *
     * @return LimitPromotions
     */
    public function setSourceType($sourceType = null)
    {
        $this->source_type = $sourceType;

        return $this;
    }

    /**
     * Get sourceType.
     *
     * @return string|null
     */
    public function getSourceType()
    {
        return $this->source_type;
    }

    /**
     * Set sourceId.
     *
     * @param int|null $sourceId
     *
     * @return LimitPromotions
     */
    public function setSourceId($sourceId = null)
    {
        $this->source_id = $sourceId;

        return $this;
    }

    /**
     * Get sourceId.
     *
     * @return int|null
     */
    public function getSourceId()
    {
        return $this->source_id;
    }
}
