<?php

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * SeckillRelCategory 秒杀关联分类表
 *
 * @ORM\Table(name="promotions_seckill_rel_category", options={"comment":"秒杀关联分类表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SeckillRelCategoryRepository")
 */
class SeckillRelCategory
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"关联id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="seckill_id", type="bigint", options={"comment":"秒杀活动id"})
     */
    private $seckill_id;
    /**
     * @var integer
     *
     * @ORM\Column(name="category_id", type="bigint", length=64, options={"comment":"分类id"})
     */
    private $category_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", nullable=true, options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="category_level", type="integer", options={"comment":"分类等级"})
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
     * Set seckillId.
     *
     * @param int $seckillId
     *
     * @return SeckillRelCategory
     */
    public function setSeckillId($seckillId)
    {
        $this->seckill_id = $seckillId;

        return $this;
    }

    /**
     * Get seckillId.
     *
     * @return int
     */
    public function getSeckillId()
    {
        return $this->seckill_id;
    }

    /**
     * Set categoryId.
     *
     * @param int $categoryId
     *
     * @return SeckillRelCategory
     */
    public function setCategoryId($categoryId)
    {
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
     * Set companyId.
     *
     * @param int|null $companyId
     *
     * @return SeckillRelCategory
     */
    public function setCompanyId($companyId = null)
    {
        $this->company_id = $companyId;

        return $this;
    }

    /**
     * Get companyId.
     *
     * @return int|null
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
     * @return SeckillRelCategory
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
