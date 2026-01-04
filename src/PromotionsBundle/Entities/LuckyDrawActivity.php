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
 * lucky_draw_activity 抽奖表
 *
 * @ORM\Table(name="lucky_draw_activity", options={"comment":"抽奖表", "collate"="utf8mb4_unicode_ci", "charset"="utf8mb4"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\LuckyDrawActivityRepository")
 */
class LuckyDrawActivity
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"抽奖id"})
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
     * @ORM\Column(name="area_id", type="bigint", options={"comment":"区域id"})
     */
    private $area_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="bigint", options={"comment":"开始时间"})
     */
    private $begin_time = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", type="bigint", options={"comment":"结束时间"})
     */
    private $end_time = 0;


    /**
     * @var integer
     *
     * @ORM\Column(name="cost_type", type="bigint", options={"comment":"消耗类型，1互动分2积分"})
     */
    private $cost_type = 1;

    /**
     * @var integer
     *
     * @ORM\Column(name="cost_value", type="bigint", options={"comment":"具体的消耗值"})
     */
    private $cost_value = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_total", type="bigint", options={"comment":"活动期限内总限制"})
     */
    private $limit_total = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="limit_day", type="bigint", options={"comment":"按天限制"})
     */
    private $limit_day = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="activity_type", type="string", length=64, nullable=true, options={"comment":"活动类型，wheel为大转盘"})
     */
    private $activity_type = 'wheel';

    /**
     * @var string
     *
     * @ORM\Column(name="activity_name", type="string", length=255, nullable=true, options={"comment":"活动名称"})
     */
    private $activity_name = '';

    /**
     * @var string
     *
     * @ORM\Column(name="activity_template_config", nullable=true, type="text", options={"comment":"活动模板配置"})
     */
    private $activity_template_config;

    /**
     * @var string
     *
     * @ORM\Column(name="prize_data", nullable=true, type="text", options={"comment":"奖项配置"})
     */
    private $prize_data;

    /**
     * @var string
     *
     * @ORM\Column(name="intro", nullable=true, type="text", options={"comment":"活动说明"})
     */
    private $intro;



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

    public function getId(): int
    {
        // ShopEx EcShopX Business Logic Layer
        return $this->id;
    }

    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id)
    {
        $this->company_id = $company_id;
    }

    public function getAreaId()
    {
        return $this->area_id;
    }

    public function setAreaId( $area_id)
    {
        $this->area_id = $area_id;
    }

    public function getBeginTime()
    {
        return $this->begin_time;
    }

    public function setBeginTime( $begin_time)
    {
        $this->begin_time = $begin_time;
    }

    public function getEndTime()
    {
        return $this->end_time;
    }

    public function setEndTime($end_time)
    {
        $this->end_time = $end_time;
    }

    public function getCostType()
    {
        return $this->cost_type;
    }

    public function setCostType($cost_type)
    {
        $this->cost_type = $cost_type;
    }

    public function getActivityType()
    {
        return $this->activity_type;
    }

    public function setActivityType($activity_type)
    {
        $this->activity_type = $activity_type;
    }

    public function getActivityName()
    {
        return $this->activity_name;
    }

    public function setActivityName($activity_name)
    {
        $this->activity_name = $activity_name;
    }

    public function getActivityTemplateConfig()
    {
        return $this->activity_template_config;
    }

    public function setActivityTemplateConfig($activity_template_config)
    {
        $this->activity_template_config = $activity_template_config;
    }

    public function getPrizeData()
    {
        return $this->prize_data;
    }

    public function setPrizeData($prize_data)
    {
        $this->prize_data = $prize_data;
    }

    public function getIntro()
    {
        return $this->intro;
    }

    public function setIntro($intro)
    {
        $this->intro = $intro;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated( $created)
    {
        $this->created = $created;
    }

    public function getUpdated()
    {
        return $this->updated;
    }

    public function setUpdated( $updated)
    {
        $this->updated = $updated;
    }

    public function getCostValue()
    {
        return $this->cost_value;
    }

    public function setCostValue($cost_value)
    {
        $this->cost_value = $cost_value;
    }

    public function getLimitTotal()
    {
        return $this->limit_total;
    }

    public function setLimitTotal($limit_total)
    {
        $this->limit_total = $limit_total;
    }

    public function getLimitDay()
    {
        return $this->limit_day;
    }

    public function setLimitDay(int $limit_day)
    {
        $this->limit_day = $limit_day;
    }


}
