<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * TagsCategory 标签分类
 *
 * @ORM\Table(name="members_tags_category", options={"comment"="标签分类"})
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\TagsCategoryRepository")
 */
class TagsCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", options={"comment":"标签分类id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $category_id;

    /**
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=50, options={"comment":"标签分类名称"})
     */
    private $category_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="sort", type="bigint", options={"comment":"排序", "default":1})
     */
    private $sort = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

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
     * Set categoryName
     *
     * @param string $categoryName
     *
     * @return TagsCategory
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
     * Set sort
     *
     * @param integer $sort
     *
     * @return TagsCategory
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
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return TagsCategory
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
     * Set created
     *
     * @param integer $created
     *
     * @return TagsCategory
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
     * @return TagsCategory
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
}
