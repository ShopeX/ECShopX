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

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Statistics 商城数据统计表
 *
 * @ORM\Table(name="companys_statistics", options={"comment":"商城数据统计表"}, indexes={
 *    @ORM\Index(name="ix_add_date", columns={"add_date"}),
 *    @ORM\Index(name="ix_statistic_type", columns={"statistic_type"}),
 *    @ORM\Index(name="ix_statistic_title", columns={"statistic_title"}),
 *    @ORM\Index(name="ix_company_id", columns={"company_id"})
 * })
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\StatisticsRepository")
 */
class Statistics
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
     * @var string
     *
     * @ORM\Column(name="add_date", type="integer", options={"comment":"统计日期 Ymd"})
     */
    private $add_date;

    /**
     * @var string
     *
     * @ORM\Column(name="statistic_title", type="string", options={"comment":"统计数据描述，order_pay_num:订单支付数量,order_pay_fee:订单支付金额,order_pay_user_num:订单支付会员数,vip_user:新增vip会员数,svip_user:新增svip会员数,add_user:注册会员数"})
     */
    private $statistic_title;

    /**
     * @var string
     *
     * @ORM\Column(name="statistic_type", type="string", options={"comment":"统计类型，normal:实体订单,service:服务订单,member:会员"})
     */
    private $statistic_type;

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
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return Statistics
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
     * Set addDate
     *
     * @param integer $addDate
     *
     * @return Statistics
     */
    public function setAddDate($addDate)
    {
        // 0x456353686f7058
        $this->add_date = $addDate;

        return $this;
    }

    /**
     * Get addDate
     *
     * @return integer
     */
    public function getAddDate()
    {
        return $this->add_date;
    }

    /**
     * Set statisticTitle
     *
     * @param string $statisticTitle
     *
     * @return Statistics
     */
    public function setStatisticTitle($statisticTitle)
    {
        $this->statistic_title = $statisticTitle;

        return $this;
    }

    /**
     * Get statisticTitle
     *
     * @return string
     */
    public function getStatisticTitle()
    {
        return $this->statistic_title;
    }

    /**
     * Set statisticType
     *
     * @param string $statisticType
     *
     * @return Statistics
     */
    public function setStatisticType($statisticType)
    {
        $this->statistic_type = $statisticType;

        return $this;
    }

    /**
     * Get statisticType
     *
     * @return string
     */
    public function getStatisticType()
    {
        return $this->statistic_type;
    }

    /**
     * Set dataValue
     *
     * @param integer $dataValue
     *
     * @return Statistics
     */
    public function setDataValue($dataValue)
    {
        $this->data_value = $dataValue;

        return $this;
    }

    /**
     * Get dataValue
     *
     * @return integer
     */
    public function getDataValue()
    {
        return $this->data_value;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return Statistics
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
     * @return Statistics
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
