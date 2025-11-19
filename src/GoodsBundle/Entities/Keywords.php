<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace GoodsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * Keywords 店铺关联热门关键词
 *
 * @ORM\Table(name="item_keywords", options={"comment"="商品热门关键词表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 * }),
 * @ORM\Entity(repositoryClass="GoodsBundle\Repositories\KeywordsRepository")
 */
class Keywords
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment"="id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"comment":"店铺id", "default": 0})
     */
    private $distributor_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="content", type="string", options={"comment"="内容"})
     */
    private $content;

    /**
     * Set Id
     *
     * @param integer $id
     *
     * @return Keywords
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get Id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Keywords
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
     * Set distributorId
     *
     * @param integer $distributorId
     *
     * @return Keywords
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId
     *
     * @return integer
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set content
     *
     * @param integer $distributorId
     *
     * @return Keywords
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content
     *
     * @return integer
     */
    public function getContent()
    {
        return $this->content;
    }
}
