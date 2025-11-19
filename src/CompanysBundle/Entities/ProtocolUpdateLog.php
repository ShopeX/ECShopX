<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace CompanysBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * ProtocolUpdateLog 协议更新日志
 *
 * @ORM\Table(name="companys_protocol_update_log", options={"comment":"协议更新日志表"})
 * @ORM\Entity(repositoryClass="CompanysBundle\Repositories\ProtocolUpdateLogRepository")
 */
class ProtocolUpdateLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="log_id", type="bigint", options={"comment":"日志id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var string
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=30, options={"comment":"协议类型,privacy:隐私政策,member_register:注册协议"})
     */
    private $type;

    /**
      * @var string
      *
      * @ORM\Column(name="content", type="text", nullable=true, options={"comment":"协议详细内容"})
      */
    private $content;

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
     * Set logId
     *
     * @param integer $logId
     *
     * @return ProtocolUpdateLog
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
     * @return ProtocolUpdateLog
     */
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

    /**
     * Set type
     *
     * @param string $type
     *
     * @return ProtocolUpdateLog
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set content.
     *
     * @param string|null $content
     *
     * @return ProtocolUpdateLog
     */
    public function setContent($content = null)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set digest
     *
     * @param string $digest
     *
     * @return ProtocolUpdateLog
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
     * @return ProtocolUpdateLog
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
     * @return ProtocolUpdateLog
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
