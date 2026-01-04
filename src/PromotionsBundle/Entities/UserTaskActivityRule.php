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
 * UserTaskActivityRule 用户任务
 *
 * @ORM\Table(name="user_task_activity_rule", options={"comment":"签到活动规则"})
 * @ORM\Entity(repositoryClass="PromotionsBundle\Repositories\UserTaskActivityRuleRepository")
 */
class UserTaskActivityRule
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
     * @var string
     * @ORM\Column(name="rule_type", type="string", options={"comment":"活动类型"})
     */
    private $rule_type = '';

    /**
     * @var string
     * @ORM\Column(name="rule_detail_type", type="string", options={"comment":"活动详细类型，理解为二级类型"})
     */
    private $rule_detail_type = '';

    /**
     * @var string
     * @ORM\Column(name="rule_name", type="string", options={"comment":"规则名称"})
     */
    private $rule_name = '';

    /**
     * @var integer
     * @ORM\Column(name="sign_type", type="bigint", options={"comment":"1连续2累计"})
     */
    private $sign_type = 1;

    /**
     * @var string
     * @ORM\Column(name="rule_desc", type="string", options={"comment":"规则名称"})
     */
    private $rule_desc = '';

    /**
     * @var string
     * @ORM\Column(name="hidde_tag", type="string", options={"comment":"用户标签"})
     */
    private $hidde_tag = '';

    /**
     * @var string
     * @ORM\Column(name="finish_tag", type="string", options={"comment":"完成标签"})
     */
    private $finish_tag = '';

    /**
     * @var string
     * @ORM\Column(name="binding_distributors", type="string", options={"comment":"指定店铺"})
     */
    private $binding_distributors = '';

    /**
     * @var string
     * @ORM\Column(name="binding_category", type="string", options={"comment":"指定管理分类"})
     */
    private $binding_category = '';

    /**
     * @var string
     * @ORM\Column(name="share_pic", type="string", options={"comment":"分享图"})
     */
    private $share_pic = '';

    /**
     * @var string
     * @ORM\Column(name="qr_code", type="string", options={"comment":"企微二维码"})
     */
    private $qr_code = '';

    /**
     * @var integer
     * @ORM\Column(name="frequency", type="bigint", options={"comment":"频次，1每天2每周3每月0为活动周期内"})
     */
    private $frequency = 0;


    /**
     * @var string
     * @ORM\Column(name="common_condition", type="string", options={"comment":"常规的门槛，比如限制金额，限制页面，等等"})
     */
    private $common_condition = '';


    /**
     * @var string
     * @ORM\Column(name="prize_text", type="text", options={"comment":"奖励信息"})
     */
    private $prize_text = '';


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
        // KEY: U2hvcEV4
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
        // KEY: U2hvcEV4
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

    public function getRuleType(): string
    {
        return $this->rule_type;
    }

    public function setRuleType(string $rule_type): void
    {
        $this->rule_type = $rule_type;
    }

    public function getRuleName(): string
    {
        return $this->rule_name;
    }

    public function setRuleName(string $rule_name): void
    {
        $this->rule_name = $rule_name;
    }

    public function getRuleDesc(): string
    {
        return $this->rule_desc;
    }

    public function setRuleDesc(string $rule_desc): void
    {
        $this->rule_desc = $rule_desc;
    }

    public function getPrizeText(): string
    {
        return $this->prize_text;
    }

    public function setPrizeText(string $prize_text): void
    {
        $this->prize_text = $prize_text;
    }

    public function getHiddeTag(): string
    {
        return $this->hidde_tag;
    }

    public function setHiddeTag(string $hidde_tag): void
    {
        $this->hidde_tag = $hidde_tag;
    }

    public function getFinishTag(): string
    {
        return $this->finish_tag;
    }

    public function setFinishTag(string $finish_tag): void
    {
        $this->finish_tag = $finish_tag;
    }

    public function getFrequency(): int
    {
        return $this->frequency;
    }

    public function setFrequency(int $frequency): void
    {
        $this->frequency = $frequency;
    }

    public function getBindingDistributors(): string
    {
        return $this->binding_distributors;
    }

    public function setBindingDistributors(string $binding_distributors): void
    {
        $this->binding_distributors = $binding_distributors;
    }

    public function getBindingCategory(): string
    {
        return $this->binding_category;
    }

    public function setBindingCategory(string $binding_category): void
    {
        $this->binding_category = $binding_category;
    }

    public function getCommonCondition(): string
    {
        return $this->common_condition;
    }

    public function setCommonCondition(string $common_condition): void
    {
        $this->common_condition = $common_condition;
    }

    public function getSharePic(): string
    {
        return $this->share_pic;
    }

    public function setSharePic(string $share_pic): void
    {
        $this->share_pic = $share_pic;
    }

    public function getQrCode(): string
    {
        return $this->qr_code;
    }

    public function setQrCode(string $qr_code): void
    {
        $this->qr_code = $qr_code;
    }

    public function getRuleDetailType(): string
    {
        return $this->rule_detail_type;
    }

    public function setRuleDetailType(string $rule_detail_type): void
    {
        $this->rule_detail_type = $rule_detail_type;
    }

    public function getSignType(): int
    {
        return $this->sign_type;
    }

    public function setSignType(int $sign_type): void
    {
        $this->sign_type = $sign_type;
    }

}
