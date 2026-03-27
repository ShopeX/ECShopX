<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace DistributionBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * DistributorCategory 店铺分类表
 *
 * @ORM\Table(
 *     name="distribution_distributor_category",
 *     options={"comment"="店铺分类表"},
 *     indexes={
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *         @ORM\Index(name="idx_category_name", columns={"category_name"}),
 *         @ORM\Index(name="idx_category_code", columns={"category_code"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="DistributionBundle\Repositories\DistributorCategoryRepository")
 */
class DistributorCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="category_id", type="bigint", options={"comment"="分类ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="category_name", type="string", length=50, options={"comment"="店铺分类名称"})
     */
    private $category_name;

    /**
     * @var string
     *
     * @ORM\Column(name="category_code", type="string", length=50, options={"comment"="分类编号"})
     */
    private $category_code;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
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
     * @return DistributorCategory
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
     * @return DistributorCategory
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
     * Set categoryCode
     *
     * @param string $categoryCode
     *
     * @return DistributorCategory
     */
    public function setCategoryCode($categoryCode)
    {
        $this->category_code = $categoryCode;

        return $this;
    }

    /**
     * Get categoryCode
     *
     * @return string
     */
    public function getCategoryCode()
    {
        return $this->category_code;
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
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
