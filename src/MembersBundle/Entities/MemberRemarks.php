<?php
/**
 * Copyright © ShopeX （http://www.shopex.cn）. All rights reserved.
 * See LICENSE file for license details.
 */

namespace MembersBundle\Entities;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * MemberGroup 用户备注表
 *
 * @ORM\Table(name="member_remarks", options={"comment"="会员备注表"}, indexes={
 *    @ORM\Index(name="idx_company_id", columns={"company_id"}),
 *    @ORM\Index(name="idx_salesperson_id", columns={"salesperson_id"}),
 *    @ORM\Index(name="idx_user_id", columns={"user_id"}),
 * })
 * @ORM\Entity(repositoryClass="MembersBundle\Repositories\MemberRemarksRepository")
 */
class MemberRemarks
{
    /**
     * @var integer
     *
     * @ORM\Id
     * @ORM\Column(name="remarks_id", type="bigint", options={"comment"="主键id"})
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $remarks_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="company_id", type="bigint", options={"comment"="公司id"})
     */
    private $company_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="salesperson_id", type="bigint", options={"comment"="导购员id"})
     */
    private $salesperson_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="user_id", type="bigint", options={"comment"="导购员id"})
     */
    private $user_id;

    /**
     * @var string
     *
     * @ORM\Column(name="remarks", type="string", options={"comment"="导购员会员备注内容"})
     */
    private $remarks;

    /**
     * @var \DateTime $created
     *
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    private $created;

    /**
     * @var \DateTime $updated
     *
     * @Gedmo\Timestampable(on="update")
     * @ORM\Column(type="integer", columnDefinition="bigint NOT NULL")
     */
    private $updated;

    /**
     * Get remarksId
     *
     * @return integer
     */
    public function getRemarksId()
    {
        return $this->remarks_id;
    }

    /**
     * Set companyId
     *
     * @param integer $companyId
     *
     * @return MemberRemarks
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
     * Set salespersonId
     *
     * @param integer $salespersonId
     *
     * @return MemberRemarks
     */
    public function setSalespersonId($salespersonId)
    {
        $this->salesperson_id = $salespersonId;

        return $this;
    }

    /**
     * Get salespersonId
     *
     * @return integer
     */
    public function getSalespersonId()
    {
        return $this->salesperson_id;
    }

    /**
     * Set userId
     *
     * @param integer $userId
     *
     * @return MemberRemarks
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
     * Set remarks
     *
     * @param string $remarks
     *
     * @return MemberRemarks
     */
    public function setRemarks($remarks)
    {
        $this->remarks = $remarks;

        return $this;
    }

    /**
     * Get remarks
     *
     * @return string
     */
    public function getRemarks()
    {
        return $this->remarks;
    }

    /**
     * Set created
     *
     * @param integer $created
     *
     * @return MemberRemarks
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
     * Set updated
     *
     * @param integer $updated
     *
     * @return MemberRemarks
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;

        return $this;
    }

    /**
     * Get updated
     *
     * @return integer
     */
    public function getUpdated()
    {
        return $this->updated;
    }
}
