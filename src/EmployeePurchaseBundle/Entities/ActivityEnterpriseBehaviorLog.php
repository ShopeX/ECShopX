<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 内购活动企业行为流水（一条一行事件，管理端实时聚合）
 *
 * @ORM\Table(name="employee_purchase_activity_enterprise_behavior_log", options={"comment"="内购活动企业行为流水"}, indexes={
 *    @ORM\Index(name="idx_ep_aebl_company_activity", columns={"company_id","activity_id"}),
 *    @ORM\Index(name="idx_ep_aebl_act_ent_type", columns={"activity_id","enterprise_id","behavior_type"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityEnterpriseBehaviorLogRepository")
 */
class ActivityEnterpriseBehaviorLog
{
    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"主键"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业ID"})
     */
    private $enterprise_id;

    /**
     * @ORM\Column(name="user_id", type="bigint", nullable=true, options={"comment":"会员用户ID"})
     */
    private $user_id;

    /**
     * @ORM\Column(name="behavior_type", type="string", length=32, options={"comment":"行为类型"})
     */
    private $behavior_type;

    /**
     * @ORM\Column(name="result_status", type="string", length=16, nullable=true, options={"comment":"行为结果，仅口令验证: success|fail"})
     */
    private $result_status;

    /**
     * @ORM\Column(name="visitor_key", type="string", length=64, nullable=true, options={"comment":"未登录去重键"})
     */
    private $visitor_key;

    /**
     * @ORM\Column(name="ref_id", type="bigint", nullable=true, options={"comment":"关联业务ID"})
     */
    private $ref_id;

    /**
     * @ORM\Column(name="extra", type="json_array", nullable=true, options={"comment":"扩展"})
     */
    private $extra;

    /**
     * @ORM\Column(type="integer", options={"comment":"创建时间戳"})
     * @Gedmo\Timestampable(on="create")
     */
    protected $created;

    public function getId()
    {
        return $this->id;
    }

    public function setCompanyId($v)
    {
        $this->company_id = $v;

        return $this;
    }

    public function setActivityId($v)
    {
        $this->activity_id = $v;

        return $this;
    }

    public function setEnterpriseId($v)
    {
        $this->enterprise_id = $v;

        return $this;
    }

    public function setUserId($v)
    {
        $this->user_id = $v;

        return $this;
    }

    public function setBehaviorType($v)
    {
        $this->behavior_type = $v;

        return $this;
    }

    public function setResultStatus($v)
    {
        $this->result_status = $v;

        return $this;
    }

    public function setVisitorKey($v)
    {
        $this->visitor_key = $v;

        return $this;
    }

    public function setRefId($v)
    {
        $this->ref_id = $v;

        return $this;
    }

    public function setExtra($v)
    {
        $this->extra = $v;

        return $this;
    }

    public function setCreated($v)
    {
        $this->created = $v;

        return $this;
    }
}
