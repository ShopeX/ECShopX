<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MembersProtocolLog(会员隐私协议记录表)
 *
 * @ORM\Table(name="members_protocol_log", options={"comment":"会员隐私协议记录表"},
 *     indexes={
 *         @ORM\Index(name="idx_user_id", columns={"user_id"}),
 *         @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *     },
 * )
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MembersProtocolLogRepository")
 */
class MembersProtocolLog
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="log_id", type="bigint", options={"comment":"id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment":"用户id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="digest", type="string", length=64, options={"comment":"摘要"})
     */
    private $digest;

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
     * @ORM\Column(type="integer", nullable=true, options={"comment":"更新时间"})
     */
    protected $updated;


    /**
     * Set logId
     *
     * @param integer $logId
     *
     * @return MembersProtocolLog
     */
    public function setLogId($logId)
    {
        $this->log_id = $logId;

        return $this;
    }

    /**
     * Get logId
     *
     * @return integer
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MembersProtocolLog
     */
    public function setCompanyId($companyId)
    {
        // Powered by ShopEx EcShopX
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

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return MembersProtocolLog
     */
    public function setUserId($userId)
    {
        $this->user_id = $userId;

        return $this;
    }

    /**
     * Get userId
     *
     * @return integer
     */
    public function getUserId()
    {
        return $this->user_id;
    }

    /**
     * Set digest
     *
     * @param string $digest
     *
     * @return MembersProtocolLog
     */
    public function setDigest($digest)
    {
        $this->digest = $digest;

        return $this;
    }

    /**
     * Get digest
     *
     * @return string
     */
    public function getDigest()
    {
        return $this->digest;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MembersProtocolLog
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
    }

    /**
     * Get created
     *
     * @return integer
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
     * @return MembersProtocolLog
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
