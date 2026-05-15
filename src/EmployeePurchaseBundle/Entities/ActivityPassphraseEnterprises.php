<?php
/**
 * Copyright 2019-2026 ShopeX
 */

namespace EmployeePurchaseBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * 内购活动口令绑定企业
 *
 * @ORM\Table(name="employee_purchase_activity_passphrase_enterprises", options={"comment"="内购活动口令绑定企业"}, indexes={
 *    @ORM\Index(name="idx_ep_ape_company_activity", columns={"company_id","activity_id"}),
 * })
 * @ORM\Entity(repositoryClass="EmployeePurchaseBundle\Repositories\ActivityPassphraseEnterprisesRepository")
 */
class ActivityPassphraseEnterprises
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
     * @ORM\Column(name="participate_quota", type="integer", options={"unsigned":true, "comment":"可参与名额", "default":0})
     */
    private $participate_quota = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="passphrase_limitfee", type="integer", options={"unsigned":true, "comment":"口令通道额度(分)，按企业", "default":0})
     */
    private $passphrase_limitfee = 0;

    /**
     * @var string
     *
     * @ORM\Column(name="passphrase_code", type="string", length=64, options={"comment":"口令编码"})
     */
    private $passphrase_code;

    /**
     * @ORM\Column(type="integer")
     * @Gedmo\Timestampable(on="create")
     */
    protected $created;

    /**
     * @ORM\Column(type="integer", nullable=true)
     * @Gedmo\Timestampable(on="update")
     */
    protected $updated;

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

    public function setParticipateQuota($participateQuota)
    {
        $this->participate_quota = $participateQuota;

        return $this;
    }

    public function getParticipateQuota()
    {
        return $this->participate_quota;
    }

    public function setPassphraseLimitfee($passphraseLimitfee)
    {
        $this->passphrase_limitfee = $passphraseLimitfee;

        return $this;
    }

    public function getPassphraseLimitfee()
    {
        return $this->passphrase_limitfee;
    }

    public function setPassphraseCode($passphraseCode)
    {
        $this->passphrase_code = $passphraseCode;

        return $this;
    }

    public function getPassphraseCode()
    {
        return $this->passphrase_code;
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

    public function setUpdated($updated = null)
    {
        $this->updated = $updated;

        return $this;
    }

    public function getUpdated()
    {
        return $this->updated;
    }
}
