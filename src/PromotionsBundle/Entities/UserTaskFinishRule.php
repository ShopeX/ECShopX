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
 * UserTaskFinishRule 用户任务
 *
 * @ORM\Table(name="user_task_finish_rule", options={"comment":"活动规则完成任务"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserTaskFinishRuleRepository")
 */
class UserTaskFinishRule
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"ID"})
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
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id", "default": 0})
     */
    private $activity_id = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id", "default": 0})
     */
    private $user_id = 0;

    /**
     * @var integer
     * @ORM\Column(name="rule_id", type="string", options={"comment":"规则"})
     */
    private $rule_id = 0;

    /**
     * @var integer
     * @ORM\Column(name="finish_status", type="integer", options={"comment":"1完成，2未完成"})
     */
    private $finish_status = 0;


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
    private $updated;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

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




    public function getCreated()
    {
        return $this->created;
    }

    public function setCreated( $created): void
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

    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    public function setActivityId(int $activity_id): void
    {
        $this->activity_id = $activity_id;
    }

    public function getRuleId(): int
    {
        return $this->rule_id;
    }

    public function setRuleId(int $rule_id): void
    {
        $this->rule_id = $rule_id;
    }

    public function getFinishStatus(): int
    {
        return $this->finish_status;
    }

    public function setFinishStatus(int $finish_status): void
    {
        $this->finish_status = $finish_status;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }


}
