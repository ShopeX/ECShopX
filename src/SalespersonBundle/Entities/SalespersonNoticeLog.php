<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SalespersonNoticeLog 导购通知发送记录表
 *
 * @ORM\Table(name="salesperson_notice_log", options={"comment":"导购通知"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_distributor_id", columns={"distributor_id"}),
 *    @ORM\Index(name="ix_notice_id", columns={"notice_id"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonNoticesLogRepository")
 */
class SalespersonNoticeLog
{
    /**
     * @var integer
     *
     * @ORM\Column(name="log_id", type="bigint", options={"comment":"主键id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $log_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="notice_id", type="bigint", options={"comment":"通知id"})
     */
    private $notice_id;

    /**
     * @var int
     *
     * @ORM\Column(name="distributor_id", type="integer", options={"comment":"店铺id"})
     */
    private $distributor_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer")
     */
    private $updated;

    /**
     * Get logId.
     *
     * @return int
     */
    public function getLogId()
    {
        return $this->log_id;
    }

    /**
     * Set noticeId.
     *
     * @param int $noticeId
     *
     * @return SalespersonNoticeLog
     */
    public function setNoticeId($noticeId)
    {
        $this->notice_id = $noticeId;

        return $this;
    }

    /**
     * Get noticeId.
     *
     * @return int
     */
    public function getNoticeId()
    {
        return $this->notice_id;
    }

    /**
     * Set distributorId.
     *
     * @param int $distributorId
     *
     * @return SalespersonNoticeLog
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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonNoticeLog
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
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonNoticeLog
     */
    public function setCreated($created)
    {
        $this->created = $created;

        return $this;
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
     * Set updated.
     *
     * @param int $updated
     *
     * @return SalespersonNoticeLog
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated.
     *
     * @return int
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
