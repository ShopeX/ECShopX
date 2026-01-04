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
 * UserSignInRules 签到规则表
 *
 * @ORM\Table(name="user_signin_rules", options={"comment":"用户签到规则表"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserSigninRulesRepository")
 */
class UserSignInRules
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
     * @var string
     *
     * @ORM\Column(name="rule_name", type="string", options={"comment":"规则名称"})
     */
    private $rule_name;

    /**
     * @var integer
     *
     * @ORM\Column(name="type", type="bigint", options={"comment":"1连续2累计"})
     */
    private $type =1;

    /**
     * @var integer
     *
     * @ORM\Column(name="days_required ", type="bigint", options={"comment":"需要的天数"})
     */
    private $days_required =1;

    /**
     * @var string
     *
     * @ORM\Column(name="reward_text", type="text", options={"comment":"奖励类型 points积分，coupon券，coupons券包"})
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
        $this->company_id = $company_id;
    }

    public function getRuleName()
    {
        return $this->rule_name;
    }

    public function setRuleName($rule_name): void
    {
        $this->rule_name = $rule_name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getDaysRequired()
    {
        return $this->days_required;
    }

    public function setDaysRequired($days_required): void
    {
        $this->days_required = $days_required;
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

    public function getRewardText()
    {
        return $this->reward_text;
    }

    public function setRewardText($reward_text)
    {
        $this->reward_text = $reward_text;
    }


}
