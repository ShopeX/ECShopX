<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberSegmentRule 人群规则表
 *
 * @ORM\Table(name="member_segment_rules", options={"comment"="人群规则表"}, indexes={
 *    @ORM\Index(name="idx_company_distributor", columns={"company_id", "distributor_id"}),
 *    @ORM\Index(name="idx_status", columns={"status"}),
 *    @ORM\Index(name="idx_created", columns={"created"}),
 * }),
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberSegmentRuleRepository")
 */
class MemberSegmentRule
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="rule_id", type="bigint", options={"comment"="规则id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $rule_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="distributor_id", type="bigint", options={"unsigned":true, "default":0, "comment"="分销商id"})
     */
    private $distributor_id = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_name", type="string", length=100, options={"comment"="规则名称（分群标签名称）"})
     */
    private $rule_name;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true, options={"comment"="人群说明"})
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="rule_config", type="text", options={"comment"="规则配置（层级结构，JSON格式存储）"})
     */
    private $rule_config;

    /**
     * @var string
     *
     * @ORM\Column(name="tag_ids", type="text", nullable=true, options={"comment"="关联的标签ID数组（JSON格式）"})
     */
    private $tag_ids;

    /**
     * @var integer
     *
     * @ORM\Column(name="status", type="smallint", options={"comment"="状态：0=禁用，1=启用", "default": 1})
     */
    private $status = 1;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    protected $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL", nullable=true)
     */
    protected $updated;

    /**
     * Get ruleId.
     *
     * @return int
     */
    public function getRuleId()
    {
        return $this->rule_id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return MemberSegmentRule
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
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return MemberSegmentRule
     */
    public function setDistributorId($distributorId)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return int
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set ruleName.
     *
     * @param string $ruleName
     *
     * @return MemberSegmentRule
     */
    public function setRuleName($ruleName)
    {
        $this->rule_name = $ruleName;

        return $this;
    }

    /**
     * Get ruleName.
     *
     * @return string
     */
    public function getRuleName()
    {
        return $this->rule_name;
    }

    /**
     * Set description.
     *
     * @param string|null $description
     *
     * @return MemberSegmentRule
     */
    public function setDescription($description = null)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set ruleConfig.
     *
     * @param string|array $ruleConfig
     *
     * @return MemberSegmentRule
     */
    public function setRuleConfig($ruleConfig)
    {
        if (is_array($ruleConfig)) {
            $this->rule_config = json_encode($ruleConfig, JSON_UNESCAPED_UNICODE);
        } else {
            $this->rule_config = $ruleConfig;
        }

        return $this;
    }

    /**
     * Get ruleConfig.
     *
     * @return array
     */
    public function getRuleConfig()
    {
        return json_decode($this->rule_config, true) ?: [];
    }

    /**
     * Set tagIds.
     *
     * @param array|string|null $tagIds
     *
     * @return MemberSegmentRule
     */
    public function setTagIds($tagIds)
    {
        if (is_array($tagIds)) {
            $this->tag_ids = json_encode($tagIds, JSON_UNESCAPED_UNICODE);
        } else {
            $this->tag_ids = $tagIds;
        }

        return $this;
    }

    /**
     * Get tagIds.
     *
     * @return array
     */
    public function getTagIds()
    {
        if (empty($this->tag_ids)) {
            return [];
        }
        return json_decode($this->tag_ids, true) ?: [];
    }

    /**
     * Set status.
     *
     * @param int $status
     *
     * @return MemberSegmentRule
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
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
     * Get updated.
     *
     * @return int|null
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
