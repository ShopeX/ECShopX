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
 * PointUpvaluation 积分升值活动表
 *
 * @ORM\Table(name="promotions_point_upvaluation", options={"comment"="积分升值活动表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"})
 * }),
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\PointUpvaluationRepository")
 */
class PointUpvaluation
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $activity_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"活动名称"})
     */
    private $title;

    /**
     * @var integer
     *
     * @ORM\Column(name="begin_time", type="bigint", options={"comment":"活动开始时间"})
     */
    private $begin_time;

    /**
     * @var integer
     *
     * @ORM\Column(name="end_time", nullable=true, type="bigint", options={"comment":"活动结束时间"})
     */
    private $end_time;

    /**
     * @var string
     *
     * @ORM\Column(name="trigger_condition", type="text", options={"comment":"触发条件"})
     */
    private $trigger_condition;

    /**
     * @var string
     *
     * @ORM\Column(name="upvaluation", type="integer", options={"comment":"升值倍数"})
     */
    private $upvaluation;

    /**
     * @var string
     *
     * @ORM\Column(name="max_up_point", type="integer", options={"comment":"每日升值积分上限"})
     */
    private $max_up_point;

    /**
     * @var string
     *
     * @ORM\Column(name="valid_grade", type="text", nullable=true, options={"comment":"会员级别集合"})
     */
    private $valid_grade;

    /**
     * @var string
     *
     * @ORM\Column(name="used_scene", type="string", options={"comment":"适用场景:  1:订单抵扣"})
     */
    private $used_scene = 0;

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
     * Get activityId.
     *
     * @return int
     */
    public function getActivityId()
    {
        // ShopEx EcShopX Service Component
        return $this->activity_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return PointUpvaluation
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
     * Set title.
     *
     * @param string $title
     *
     * @return PointUpvaluation
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set beginTime.
     *
     * @param int $beginTime
     *
     * @return PointUpvaluation
     */
    public function setBeginTime($beginTime)
    {
        $this->begin_time = $beginTime;

        return $this;
    }

    /**
     * Get beginTime.
     *
     * @return int
     */
    public function getBeginTime()
    {
        return $this->begin_time;
    }

    /**
     * Set endTime.
     *
     * @param int|null $endTime
     *
     * @return PointUpvaluation
     */
    public function setEndTime($endTime = null)
    {
        $this->end_time = $endTime;

        return $this;
    }

    /**
     * Get endTime.
     *
     * @return int|null
     */
    public function getEndTime()
    {
        return $this->end_time;
    }

    /**
     * Set triggerCondition.
     *
     * @param string $triggerCondition
     *
     * @return PointUpvaluation
     */
    public function setTriggerCondition($triggerCondition)
    {
        $this->trigger_condition = $triggerCondition;

        return $this;
    }

    /**
     * Get triggerCondition.
     *
     * @return string
     */
    public function getTriggerCondition()
    {
        return $this->trigger_condition;
    }

    /**
     * Set upvaluation.
     *
     * @param int $upvaluation
     *
     * @return PointUpvaluation
     */
    public function setUpvaluation($upvaluation)
    {
        $this->upvaluation = $upvaluation;

        return $this;
    }

    /**
     * Get upvaluation.
     *
     * @return int
     */
    public function getUpvaluation()
    {
        return $this->upvaluation;
    }

    /**
     * Set maxUpPoint.
     *
     * @param int $maxUpPoint
     *
     * @return PointUpvaluation
     */
    public function setMaxUpPoint($maxUpPoint)
    {
        $this->max_up_point = $maxUpPoint;

        return $this;
    }

    /**
     * Get maxUpPoint.
     *
     * @return int
     */
    public function getMaxUpPoint()
    {
        return $this->max_up_point;
    }

    /**
     * Set validGrade.
     *
     * @param string|null $validGrade
     *
     * @return PointUpvaluation
     */
    public function setValidGrade($validGrade = null)
    {
        $this->valid_grade = $validGrade;

        return $this;
    }

    /**
     * Get validGrade.
     *
     * @return string|null
     */
    public function getValidGrade()
    {
        return $this->valid_grade;
    }

    /**
     * Set usedScene.
     *
     * @param string $usedScene
     *
     * @return PointUpvaluation
     */
    public function setUsedScene($usedScene)
    {
        $this->used_scene = $usedScene;

        return $this;
    }

    /**
     * Get usedScene.
     *
     * @return string
     */
    public function getUsedScene()
    {
        return $this->used_scene;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return PointUpvaluation
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
     * @return PointUpvaluation
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
