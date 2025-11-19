<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ArticleCategory 文章类目表
 *
 * @ORM\Table(name="companys_article_category", options={"comment":"文章类目表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\ArticleCategoryRepository")
 */
class ArticleCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", options={"comment":"文章类目id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=50, options={"comment":"类目名称"})
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
     * @ORM\Column(name="category_level", nullable=true, type="integer", options={"comment":"类目等级", "default":1})
     */
    private $category_level;

    /**
     * @var string
     *
     * @ORM\Column(name="path", type="string", nullable=true, length=255, options={"comment":"路径", "default":"0"})
     */
    private $path;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", nullable=true, type="bigint", options={"comment":"排序", "default":"0"})
     */
    private $sort;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_type", nullable=true, type="string", options={"comment":"文章栏目类型，general:普通; bring:带货", "default":"bring"})
     */
    private $category_type = 'bring';

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
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->category_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return ArticleCategory
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
     * Set categoryName
     *
     * @param string $categoryName
     *
     * @return ArticleCategory
     */
    public function setCategoryName($categoryName)
    {
        $this->category_name = $categoryName;

        return $this;
    }

    /**
     * Get categoryName
     *
     * @return string
     */
    public function getCategoryName()
    {
        return $this->category_name;
    }

    /**
     * Set parentId
     *
     * @param integer $parentId
     *
     * @return ArticleCategory
     */
    public function setParentId($parentId)
    {
        $this->parent_id = $parentId;

        return $this;
    }

    /**
     * Get parentId
     *
     * @return integer
     */
    public function getParentId()
    {
        return $this->parent_id;
    }

    /**
     * Set categoryLevel
     *
     * @param integer $categoryLevel
     *
     * @return ArticleCategory
     */
    public function setCategoryLevel($categoryLevel)
    {
        $this->category_level = $categoryLevel;

        return $this;
    }

    /**
     * Get categoryLevel
     *
     * @return integer
     */
    public function getCategoryLevel()
    {
        return $this->category_level;
    }

    /**
     * Set path
     *
     * @param string $path
     *
     * @return ArticleCategory
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * Get path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Set sort
     *
     * @param integer $sort
     *
     * @return ArticleCategory
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Get sort
     *
     * @return integer
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return ArticleCategory
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
     * @return ArticleCategory
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
     * Set categoryType
     *
     * @param string $categoryType
     *
     * @return ArticleCategory
     */
    public function setCategoryType($categoryType)
    {
        $this->category_type = $categoryType;

        return $this;
    }

    /**
     * Get categoryType
     *
     * @return string
     */
    public function getCategoryType()
    {
        return $this->category_type;
    }
}
