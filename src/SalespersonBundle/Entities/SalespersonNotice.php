<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SalespersonNotice 导购通知
 *
 * @ORM\Table(name="salesperson_notice", options={"comment":"导购通知"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_title", columns={"title"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonNoticeRepository")
 */
class SalespersonNotice
{
    /**
     * @var integer
     *
     * @ORM\Column(name="notice_id", type="bigint", options={"comment":"通知id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $notice_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", options={"comment":"通知标题"})
     */
    private $title;

    /**
     * @var string
     *
     * @ORM\Column(name="content", type="text", options={"comment":"通知内容"})
     */
    private $content;

    /**
     * @var string
     *
     * @ORM\Column(name="distributor_id", nullable=true, type="text", options={"comment":"店铺id"})
     */
    private $distributor_id = '';

    /**
     * @var string
     *
     * @ORM\Column(name="all_distributor", nullable=true, type="text", options={"comment":"店铺id", "default":0})
     */
    private $all_distributor = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="notice_type", type="integer", options={"comment":"通知类型，1系统通知，2总部通知，3其他通知", "default":1})
     */
    private $notice_type = 1;

    /**
     * @var int
     *
     * @ORM\Column(name="sent_times", type="integer", nullable=true, options={"comment":"发送次数", "default":0})
     */
    private $sent_times = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="is_delete", type="integer", nullable=true, options={"comment":"是否已删除", "default":0})
     */
    private $is_delete = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="withdraw", type="integer", nullable=true, options={"comment":"是否撤回", "default":0})
     */
    private $withdraw = 0;

    /**
     * @var int
     *
     * @ORM\Column(name="last_sent_time", type="integer", nullable=true, options={"comment":"最后发送时间", "default":0})
     */
    private $last_sent_time = 0;

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
     * @var int
     *
     * @ORM\Column(name="status", type="integer", nullable=true, options={"comment":"状态，1未发送，2已发送，3已撤回", "default":1})
     */
    private $status = 1;

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
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonNotice
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
     * Set title.
     *
     * @param string $title
     *
     * @return SalespersonNotice
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set content.
     *
     * @param string $content
     *
     * @return SalespersonNotice
     */
    public function setContent($content)
    {
        $this->content = $content;

        return $this;
    }

    /**
     * Get content.
     *
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * Set noticeType.
     *
     * @param int $noticeType
     *
     * @return SalespersonNotice
     */
    public function setNoticeType($noticeType)
    {
        $this->notice_type = $noticeType;

        return $this;
    }

    /**
     * Get noticeType.
     *
     * @return int
     */
    public function getNoticeType()
    {
        return $this->notice_type;
    }

    /**
     * Set sentTimes.
     *
     * @param int|null $sentTimes
     *
     * @return SalespersonNotice
     */
    public function setSentTimes($sentTimes = null)
    {
        $this->sent_times = $sentTimes;

        return $this;
    }

    /**
     * Get sentTimes.
     *
     * @return int|null
     */
    public function getSentTimes()
    {
        return $this->sent_times;
    }

    /**
     * Set isDelete.
     *
     * @param int|null $isDelete
     *
     * @return SalespersonNotice
     */
    public function setIsDelete($isDelete = null)
    {
        $this->is_delete = $isDelete;

        return $this;
    }

    /**
     * Get isDelete.
     *
     * @return int|null
     */
    public function getIsDelete()
    {
        return $this->is_delete;
    }

    /**
     * Set withdraw.
     *
     * @param int|null $withdraw
     *
     * @return SalespersonNotice
     */
    public function setWithdraw($withdraw = null)
    {
        $this->withdraw = $withdraw;

        return $this;
    }

    /**
     * Get withdraw.
     *
     * @return int|null
     */
    public function getWithdraw()
    {
        return $this->withdraw;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonNotice
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
     * @return SalespersonNotice
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

    /**
     * Set distributorId.
     *
     * @param string|null $distributorId
     *
     * @return SalespersonNotice
     */
    public function setDistributorId($distributorId = null)
    {
        $this->distributor_id = $distributorId;

        return $this;
    }

    /**
     * Get distributorId.
     *
     * @return string|null
     */
    public function getDistributorId()
    {
        return $this->distributor_id;
    }

    /**
     * Set lastSentTime.
     *
     * @param int|null $lastSentTime
     *
     * @return SalespersonNotice
     */
    public function setLastSentTime($lastSentTime = null)
    {
        $this->last_sent_time = $lastSentTime;

        return $this;
    }

    /**
     * Get lastSentTime.
     *
     * @return int|null
     */
    public function getLastSentTime()
    {
        return $this->last_sent_time;
    }

    /**
     * Set status.
     *
     * @param int|null $status
     *
     * @return SalespersonNotice
     */
    public function setStatus($status = null)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status.
     *
     * @return int|null
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set allDistributor.
     *
     * @param string|null $allDistributor
     *
     * @return SalespersonNotice
     */
    public function setAllDistributor($allDistributor = null)
    {
        $this->all_distributor = $allDistributor;

        return $this;
    }

    /**
     * Get allDistributor.
     *
     * @return string|null
     */
    public function getAllDistributor()
    {
        return $this->all_distributor;
    }
}
