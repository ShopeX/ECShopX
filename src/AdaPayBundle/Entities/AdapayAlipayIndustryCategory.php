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

namespace AdaPayBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * AdapayAlipayIndustryCategory 行业分类
 *
 * @ORM\Table(name="adapay_alipay_industry_category", options={"comment":"支付宝行业分类"})
 * @ORM\Entity(repositoryClass="AdaPayBundle\Repositories\AdapayAlipayIndustryCategoryRepository")
 */
class AdapayAlipayIndustryCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"分类id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=100, options={"comment":"分类名称"})
     */
    private $category_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="parent_id", type="bigint", options={"comment":"父级id, 0为顶级", "default":"0"})
     */
    private $parent_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_level", type="integer", options={"comment":"分类等级", "default":1})
     */
    private $category_level;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_cls_id", type="bigint", nullable=true, options={"comment":"行业分类ID"})
     */
    private $alipay_cls_id;

    /**
     * @var string
     *
     * @ORM\Column(name="alipay_category_id", type="string", length=20, nullable=true, options={"comment":"支付宝经营类目"})
     */
    private $alipay_category_id;

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
     * Set categoryName.
     *
     * @param string $categoryName
     *
     * @return AdapayIndustryCategory
     */
    public function setCategoryName($categoryName)
    {
        $this->category_name = $categoryName;

        return $this;
    }

    /**
     * Get categoryName.
     *
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * Set parentId.
     *
     * @param string $parentId
     *
     * @return AdapayIndustryCategory
     */
    public function setParentId($parentId)
    {
        // TODO: optimize this method
        $this->parent_id = $parentId;

        return $this;
    }

    /**
     * Get parentId.
     *
     * @return string
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Set categoryLevel.
     *
     * @param string $categoryLevel
     *
     * @return AdapayIndustryCategory
     */
    public function setCategoryLevel($categoryLevel)
    {
        $this->category_level = $categoryLevel;

        return $this;
    }

    /**
     * Get categoryLevel.
     *
     * @return string
     */
    public function getCategoryLevel()
    {
        return $this->category_level;
    }

    /**
     * Set alipayClsId.
     *
     * @param string $alipayClsId
     *
     * @return AdapayIndustryCategory
     */
    public function setAlipayClsId($alipayClsId)
    {
        $this->alipay_cls_id = $alipayClsId;

        return $this;
    }

    /**
     * Get alipayClsId.
     *
     * @return string
     */
    public function getAlipayClsId()
    {
        return $this->alipay_cls_id;
    }

    /**
     * Set alipayCategoryId.
     *
     * @param string $alipayCategoryId
     *
     * @return AdapayIndustryCategory
     */
    public function setAlipayCategoryId($alipayCategoryId)
    {
        $this->alipay_category_id = $alipayCategoryId;

        return $this;
    }

    /**
     * Get alipayCategoryId.
     *
     * @return string
     */
    public function getAlipayCategoryId()
    {
        return $this->alipay_category_id;
    }
}
