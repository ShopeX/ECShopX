<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ActiveArticles 活动文章表
 *
 * @ORM\Table(name="salesperson_active_article_share_log", options={"comment":"活动文章表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SalespersonActiveArticleShareLogRepository")
 */
class SalespersonActiveArticlesShareLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员ID"})
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="article_id", type="bigint", options={"comment":"活动文章ID"})
     */
    private $article_id;

    /**
     * @var string
     *
     * @ORM\Column(name="article_title", type="string", options={"comment":"活动文章标题"})
     */
    private $article_title;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    private $updated;

    /**
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonActiveArticlesShareLog
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
     * Set salespersonId.
     *
     * @param int $salespersonId
     *
     * @return SalespersonActiveArticlesShareLog
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
     * Set articleId.
     *
     * @param int $articleId
     *
     * @return SalespersonActiveArticlesShareLog
     */
    public function setArticleId($articleId)
    {
        $this->article_id = $articleId;

        return $this;
    }

    /**
     * Get articleId.
     *
     * @return int
     */
    public function getArticleId()
    {
        return $this->article_id;
    }

    /**
     * Set articleTitle.
     *
     * @param string $articleTitle
     *
     * @return SalespersonActiveArticlesShareLog
     */
    public function setArticleTitle($articleTitle)
    {
        $this->article_title = $articleTitle;

        return $this;
    }

    /**
     * Get articleTitle.
     *
     * @return string
     */
    public function getArticleTitle()
    {
        return $this->article_title;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonActiveArticlesShareLog
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created.
     *
     * @return int
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated.
     *
     * @param int|null $updated
     *
     * @return SalespersonActiveArticlesShareLog
     */
    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
