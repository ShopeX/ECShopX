<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace SalespersonBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * SalespersonRelNotice 导购通知关联表
 *
 * @ORM\Table(name="salesperson_rel_notice", options={"comment":"导购通知关联表"}, indexes={
 *    @ORM\Index(name="ix_company_id", columns={"company_id"}),
 *    @ORM\Index(name="ix_notice_id", columns={"notice_id"}),
 *    @ORM\Index(name="ix_salesperson_id", columns={"salesperson_id"}),
 * })
 * @ORM\Entity(repositoryClass="SalespersonBundle\Repositories\SalespersonRelNoticeRepository")
 */
class SalespersonRelNotice
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="bigint", options={"comment":"id"})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment":"公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="notice_id", type="bigint", options={"comment":"通知id"})
     */
    private $notice_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment":"导购员id"})
     */
    private $salesperson_id;

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
     * Get id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set companyId.
     *
     * @param int $companyId
     *
     * @return SalespersonRelNotice
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
     * Set noticeId.
     *
     * @param int $noticeId
     *
     * @return SalespersonRelNotice
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
     * Set salespersonId.
     *
     * @param int $salespersonId
     *
     * @return SalespersonRelNotice
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId.
     *
     * @return int
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set created.
     *
     * @param int $created
     *
     * @return SalespersonRelNotice
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
     * @return SalespersonRelNotice
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
