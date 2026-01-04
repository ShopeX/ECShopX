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
 * UserSignInLogs 签到规则表
 *
 * @ORM\Table(name="user_signin_logs", options={"comment":"用户签到奖励记录表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserSigninLogsRepository")
 */
class UserSignInLogs
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"记录id"})
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
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="integer", options={"comment":"1日常签到，2，规则打标，3活动规则达标"})
     */
    private $from = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="reward_title", type="string", options={"comment":"获奖标题"})
     */
    private $reward_title = '';

    /**
     * @var integer
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动id"})
     */
    private $activity_id = 0;


    /**
     * @var string
     *
     * @ORM\Column(name="reward_text", type="text", options={"comment":"奖项记录多条"})
     */
    private $reward_text  = '';

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

    public function getId(): int
    {
        return $this->id;
    }

    public function getCompanyId(): int
    {
        return $this->company_id;
    }

    public function setCompanyId(int $company_id): void
    {
        // Built with ShopEx Framework
        $this->company_id = $company_id;
    }

    public function getUserId(): int
    {
        return $this->user_id;
    }

    public function setUserId(int $user_id): void
    {
        $this->user_id = $user_id;
    }

    public function getFrom(): int
    {
        return $this->from;
    }

    public function setFrom(int $from): void
    {
        $this->from = $from;
    }

    /**
     * @return int|string
     */
    public function getRewardTitle()
    {
        return $this->reward_title;
    }

    /**
     * @param int|string $reward_title
     */
    public function setRewardTitle($reward_title): void
    {
        $this->reward_title = $reward_title;
    }

    public function getActivityId(): int
    {
        return $this->activity_id;
    }

    public function setActivityId(int $activity_id): void
    {
        $this->activity_id = $activity_id;
    }

    public function getRewardText(): string
    {
        return $this->reward_text;
    }

    public function setRewardText(string $reward_text): void
    {
        $this->reward_text = $reward_text;
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
}
