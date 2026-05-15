<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;

/**
 * 已占用过口令企业参与名额的用户：同活动+企业维度再次绑单/下单时跳过名额判断与扣减。
 *
 * @ORM\Table(name="employee_purchase_activity_enterprise_participate_user", options={"comment":"内购活动企业参与名额已占用用户"},
 *     uniqueConstraints={
 *         @ORM\UniqueConstraint(name="uk_ep_aepu_scope_user", columns={"company_id","activity_id","enterprise_id","user_id"})
 *     },
 *     indexes={
 *         @ORM\Index(name="idx_ep_aepu_activity_ent", columns={"activity_id","enterprise_id"})
 *     }
 * )
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityEnterpriseParticipateUserRepository")
 */
class ActivityEnterpriseParticipateUser
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(name="id", type="bigint", options={"comment":"主键"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司ID"})
     */
    private $company_id;

    /**
     * @var int
     *
     * @ORM\Column(name="activity_id", type="bigint", options={"comment":"活动ID"})
     */
    private $activity_id;

    /**
     * @var int
     *
     * @ORM\Column(name="enterprise_id", type="bigint", options={"comment":"企业ID"})
     */
    private $enterprise_id;

    /**
     * @var int
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"会员用户ID"})
     */
    private $user_id;

    /**
     * @var int
     *
     * @ORM\Column(name="created", type="integer", options={"comment":"创建时间戳"})
     */
    private $created;

    public function getId()
    {
        return $this->id;
    }

    public function setCompanyId($companyId)
    {
        $this->company_id = $companyId;

        return $this;
    }

    public function getCompanyId()
    {
        return $this->company_id;
    }

    public function setActivityId($activityId)
    {
        $this->activity_id = $activityId;

        return $this;
    }

    public function getActivityId()
    {
        return $this->activity_id;
    }

    public function setEnterpriseId($enterpriseId)
    {
        $this->enterprise_id = $enterpriseId;

        return $this;
    }

    public function getEnterpriseId()
    {
        return $this->enterprise_id;
    }

    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    public function getUserId()
    {
        return $this->user_id;
    }

    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    public function getCreated()
    {
        return $this->created;
    }
}
