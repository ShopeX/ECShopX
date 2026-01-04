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
