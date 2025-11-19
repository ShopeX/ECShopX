<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace PromotionsBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Statistics 导购员活动转发数据统计表
 *
 * @ORM\Table(name="salesperson_active_article_statistics", options={"comment":"导购员活动转发数据统计表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_salesperson_id", columns={"salesperson_id"}),
 *    @ORM\Index(name="ix_add_date", columns={"add_date"})
 * })
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\SalespersonActiveArticleStatisticsRepository")
 */
class SalespersonActiveArticleStatistics
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"激活id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     *
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id"})
     *
     */
    private $salesperson_id;

    /**
     * @var string
     *
     * @ORM\Column(name="add_date", type="integer", options={"comment":"统计日期 Ymd"})
     */
    private $add_date;

    /**
     * @var string
     *
     * @ORM\Column(name="data_value", type="integer", options={"comment":"统计数据", "default": 0})
     */
    private $data_value = 0 ;

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
     * @ORM\Column(type="integer")
     */
    private $updated;

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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonActiveArticleStatistics
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
     * @return SalespersonActiveArticleStatistics
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
     * Set addDate.
     *
     * @param int $addDate
     *
     * @return SalespersonActiveArticleStatistics
     */
    public function setAddDate($addDate)
    {
        $this->add_date = $addDate;

        return $this;
    }

    /**
     * Get addDate.
     *
     * @return int
     */
    public function getAddDate()
    {
        return $this->add_date;
    }

    /**
     * Set dataValue.
     *
     * @param int $dataValue
     *
     * @return SalespersonActiveArticleStatistics
     */
    public function setDataValue($dataValue)
    {
        $this->data_value = $dataValue;

        return $this;
    }

    /**
     * Get dataValue.
     *
     * @return int
     */
    public function getDataValue()
    {
        return $this->data_value;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonActiveArticleStatistics
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
     * @param int $updated
     *
     * @return SalespersonActiveArticleStatistics
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
